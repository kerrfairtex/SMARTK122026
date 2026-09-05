<?php
/**
 * BBNIHS Session Service
 *
 * Replaces Warehouse.php:220-267 session bootstrap.
 *
 * Source-grounded facts:
 *   - session_name('RosarioSIS')                   Warehouse.php:226
 *   - cookie_path derived from SCRIPT_NAME         Warehouse.php:229-230
 *   - SESSION_COOKIE_SAMESITE env, default 'Lax'   Warehouse.php:236
 *   - cookie https-only conditional on             Warehouse.php:239-240
 *     $_SERVER['HTTPS'] / SERVER_PORT==443
 *   - session_set_cookie_params(secure,httponly,   Warehouse.php:255-262
 *     samesite)
 *   - session_cache_limiter('nocache')             Warehouse.php:265
 *   - session_start()                              Warehouse.php:267
 *   - CSRF token generated via                     Warehouse.php:284-287
 *     openssl_random_pseudo_bytes(16)
 *   - DefaultSyear copy to session                 Warehouse.php:269-273
 *   - auto-logout redirect for                     Warehouse.php:290-321
 *     Modules/Side/Bottom without STAFF_ID/STUDENT_ID
 *
 * TRANSITIONAL note: session_name is kept as 'RosarioSIS' ONLY for the
 * duration of the cutover, so existing browser sessions are not invalidated.
 * The canonical BBNIHS session identity ('BBNIHS_SESSID') is documented and
 * will be activated after the post-cutover validation period.
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Session;

use BBNIHS\Config\Config;

final class Session
{
    public const TRANSITIONAL_NAME = 'RosarioSIS'; // cutover only
    public const CANONICAL_NAME    = 'BBNIHS_SESSID'; // post-cutover

    private bool $started = false;

    public function __construct(private readonly Config $config) {}

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        $params = $this->buildCookieParams();
        session_name(self::TRANSITIONAL_NAME);
        session_set_cookie_params($params);
        session_cache_limiter('nocache');
        session_start();
        $this->started = true;

        // DefaultSyear seed (Warehouse.php:269-273)
        if (empty($_SESSION['DefaultSyear']) && $this->config->get('app.default_syear')) {
            $_SESSION['DefaultSyear'] = $this->config->get('app.default_syear');
        }

        // CSRF token seed (Warehouse.php:284-287)
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = $this->generateToken();
        }
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(), '',
                    ['expires' => time() - 42000,
                     'path'     => $params['path'],
                     'domain'   => $params['domain'],
                     'secure'   => $params['secure'],
                     'httponly' => $params['httponly'],
                     'samesite' => $params['samesite'] ?? 'Lax',]
                );
            }
            session_destroy();
        }
        $this->started = false;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function unset(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    public function isStarted(): bool
    {
        return $this->started && session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Generate CSRF token (Warehouse.php:284-287)
     */
    public function generateToken(): string
    {
        $raw = function_exists('openssl_random_pseudo_bytes')
            ? openssl_random_pseudo_bytes(16)
            : (function_exists('random_bytes') ? random_bytes(16) : sha1((string)random_int(999999999, 9999999999), true));
        return bin2hex($raw);
    }

    /**
     * @return array<string,mixed>
     */
    private function buildCookieParams(): array
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
        $base = dirname($scriptName);
        $path = ($base === DIRECTORY_SEPARATOR || $base === '.') ? '/' : $base . '/';

        // Source: Warehouse.php:239-240 — HTTPS detection
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        return [
            'lifetime' => 0,
            'path'     => $path,
            'domain'   => '',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => (string)$this->config->get('session.samesite', 'Lax'),
        ];
    }
}
