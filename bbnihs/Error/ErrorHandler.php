<?php
/**
 * BBNIHS Error Handler
 *
 * Replaces db_show_error() (database.inc.php:91-100) and ErrorMessage()
 * (from the legacy Warehouse output). Real implementation will display and
 * optionally email admin; for Phase 1 it just records structured errors.
 *
 * Source-grounded facts:
 *   - db_show_error signature:
 *     ($sql, $message, $db_error)  database.inc.php:91-100
 *   - ErrorMessage used everywhere via Warehouse header/footer
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Error;

final class ErrorHandler
{
    /** @var array<int,array<string,mixed>> */
    private array $errors = [];

    public function handle(string $code, string $message, array $context = []): void
    {
        $this->errors[] = [
            'code'    => $code,
            'message' => $message,
            'context' => $context,
            'at'      => gmdate('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * Mirrors db_show_error( $sql, 'DB Execute Failed.', $pg_last_error )
     */
    public function dbError(string $sql, string $driverError): void
    {
        $this->handle('DB_EXECUTE_FAILED', 'DB Execute Failed.', [
            'sql'          => $sql,
            'driver_error' => $driverError,
        ]);
    }

    public function all(): array
    {
        return $this->errors;
    }

    public function renderHtml(string $message, string $type = 'error'): string
    {
        $cls = htmlspecialchars('bbnihs-message bbnihs-message-' . $type, ENT_QUOTES, 'UTF-8');
        $msg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        return '<div class="' . $cls . '">' . $msg . '</div>';
    }
}
