<?php
/**
 * BBNIHS CSRF Service
 *
 * Replaces Warehouse.php:275-340 CSRF implementation.
 *
 * Source-grounded facts:
 *   - Token seed on session start                   Warehouse.php:284-287
 *   - Token check: $_REQUEST['token']              Warehouse.php:325-326
 *   - AJAX: $_SERVER['HTTP_X_CSRF_TOKEN']          Warehouse.php:329-330
 *   - On mismatch: Hacking::log()                   Warehouse.php:335
 *   - Enforced only for modfunc requests            Warehouse.php:323-340
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Csrf;

use BBNIHS\Session\Session;
use BBNIHS\Audit\Audit;

final class Csrf
{
    public const REQUEST_KEY    = 'token';
    public const HEADER_KEY     = 'HTTP_X_CSRF_TOKEN';
    public const META_NAME      = 'token';

    /** Request methods that require CSRF validation */
    public const ENFORCED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly Session $session,
        private readonly Audit $audit,
    ) {}

    public function token(): string
    {
        $existing = $this->session->get('token');
        if (!is_string($existing) || $existing === '') {
            $existing = $this->session->generateToken();
            $this->session->set('token', $existing);
        }
        return $existing;
    }

    public function shouldEnforce(array $server, array $request): bool
    {
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, self::ENFORCED_METHODS, true)) {
            return false;
        }
        // DELETE/PUT/PATCH must always carry a token (REST semantics).
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }
        // Source: Warehouse.php:323-340 — POST is only enforced when a
        // modfunc/save/delete trigger is present in the request body.
        return isset($request['modfunc']) || isset($request['save']) || isset($request['delete']);
    }

    public function validate(array $server, array $request): bool
    {
        $expected = $this->token();
        $supplied = (string)($request[self::REQUEST_KEY] ?? '');
        if ($supplied === '' && isset($server[self::HEADER_KEY])) {
            $supplied = (string)$server[self::HEADER_KEY];
        }
        if ($supplied === '' || $expected === '') {
            return false;
        }
        return hash_equals($expected, $supplied);
    }

    public function emitMetaTag(): string
    {
        $tok = htmlspecialchars($this->token(), ENT_QUOTES, 'UTF-8');
        return '<meta name="' . self::META_NAME . '" content="' . $tok . '">';
    }

    public function enforce(array $server, array $request): void
    {
        if (!$this->shouldEnforce($server, $request)) {
            return;
        }
        if ($this->validate($server, $request)) {
            return;
        }
        $this->audit->recordCSRFViolation([
            'request_uri' => $server['REQUEST_URI'] ?? '',
            'method'       => $server['REQUEST_METHOD'] ?? '',
            'remote_addr' => $server['REMOTE_ADDR'] ?? '',
            'user_agent'  => $server['HTTP_USER_AGENT'] ?? '',
        ]);
        http_response_code(403);
        echo '<h1>Forbidden</h1><p>CSRF token mismatch.</p>';
        exit;
    }
}
