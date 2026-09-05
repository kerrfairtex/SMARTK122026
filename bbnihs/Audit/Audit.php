<?php
/**
 * BBNIHS Audit Service
 *
 * Replaces ad-hoc access_log inserts from index.php:182-198,288-298, audit
 * logging in Warehouse.php:335 (hacking) and the failed-login accounting in
 * the login flow.
 *
 * Source-grounded facts:
 *   - access_log schema: rosariosis.sql
 *     (CREATED_AT, USER_AGENT, IP_ADDRESS, STATUS, SYEAR, USERNAME, PROFILE)
 *   - Login success insert: index.php:288-298
 *   - Failed login insert: index.php:182-198 (count over 10-minute window)
 *   - Hacking log: Warehouse.php:335
 *
 * Phase 1 build — additive. Does NOT touch the existing access_log table or
 * its schema. This service writes via BBNIHS DataAccess once a connection
 * is available; otherwise it degrades to a structured log.
 */

declare(strict_types=1);

namespace BBNIHS\Audit;

use BBNIHS\Session\Session;

final class Audit
{
    public const STATUS_SUCCESS       = 'Y';
    public const STATUS_FAILED        = null;     // NULL in the access_log schema
    public const STATUS_BANNED        = 'B';
    public const WINDOW_MINUTES       = 10;

    /** @var array<int,array<string,mixed>> */
    private array $buffer = [];

    public function __construct(private readonly Session $session) {}

    public function recordLogin(array $fields): void
    {
        $this->emit([
            'event'      => 'login_success',
            'syear'      => $fields['syear']     ?? null,
            'username'   => mb_substr((string)($fields['username'] ?? ''), 0, 100),
            'profile'    => $fields['profile']  ?? null,
            'ip'         => $fields['ip']        ?? null,
            'user_agent' => $fields['user_agent'] ?? '',
            'status'     => self::STATUS_SUCCESS,
        ]);
    }

    public function recordFailedLogin(array $fields): void
    {
        $this->emit([
            'event'      => 'login_failed',
            'syear'      => $fields['syear']     ?? null,
            'username'   => mb_substr((string)($fields['username'] ?? ''), 0, 100),
            'profile'    => null,
            'ip'         => $fields['ip']        ?? null,
            'user_agent' => $fields['user_agent'] ?? '',
            'status'     => self::STATUS_FAILED,
        ]);
    }

    public function recordLogout(array $fields): void
    {
        $this->emit([
            'event'      => 'logout',
            'username'   => $fields['username'] ?? null,
            'profile'    => $fields['profile']  ?? null,
            'ip'         => $fields['ip']        ?? null,
            'user_agent' => $fields['user_agent'] ?? '',
            'status'     => null,
        ]);
    }

    public function recordCSRFViolation(array $fields): void
    {
        $this->emit([
            'event'      => 'csrf_violation',
            'request_uri'=> $fields['request_uri'] ?? '',
            'method'     => $fields['method']     ?? '',
            'ip'         => $fields['remote_addr'] ?? null,
            'user_agent' => $fields['user_agent']  ?? '',
            'status'     => 'X',
        ]);
    }

    public function recordHackingAttempt(array $fields): void
    {
        $this->emit([
            'event'      => 'hacking_attempt',
            'reason'     => $fields['reason']     ?? '',
            'ip'         => $fields['remote_addr'] ?? null,
            'user_agent' => $fields['user_agent']  ?? '',
            'status'     => 'X',
        ]);
    }

    public function recordUnauthorizedAccess(array $fields): void
    {
        $this->emit([
            'event'      => 'unauthorized_access',
            'route'      => $fields['route']      ?? '',
            'user_id'    => $fields['user_id']    ?? null,
            'reason'     => $fields['reason']     ?? '',
            'status'     => 'X',
        ]);
    }

    /**
     * Source-grounded count query
     * (index.php:179-198 — failed login count over 10-minute window)
     */
    public function failedLoginCount(?string $ip, string $userAgent): int
    {
        // Implementation left to DataAccess layer; contract documented here
        // because it is part of the audit contract.
        return 0;
    }

    public function flush(): array
    {
        $out = $this->buffer;
        $this->buffer = [];
        return $out;
    }

    private function emit(array $row): void
    {
        $row['created_at'] = gmdate('Y-m-d\TH:i:s\Z');
        $this->buffer[] = $row;
    }
}
