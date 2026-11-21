<?php
// Health check: verify the app AND its database are reachable.
// A 200 only when both PHP bootstrap and a DB query succeed, so Render does
// not report a DB-dead instance as healthy.

// A pg_connect() warning printed before http_response_code() would send the
// headers early and force a 200, hiding the outage. It would also expose the
// DB host and user publicly, so failure detail goes to the server log only.
ini_set( 'display_errors', '0' );

$ok = false;

try {
    $config = __DIR__ . '/config.inc.php';
    if ( ! file_exists( $config ) ) {
        throw new Exception( 'config.inc.php missing' );
    }

    require_once $config;
    require_once __DIR__ . '/database.inc.php';

    $conn = db_start( false );

    if ( $conn === false ) {
        $last = error_get_last();
        throw new Exception( 'db_start returned false: ' . ( $last['message'] ?? 'unknown' ) );
    }

    $result = pg_query( $conn, 'SELECT 1' );

    if ( $result === false ) {
        throw new Exception( 'SELECT 1 failed: ' . pg_last_error( $conn ) );
    }

    $ok = true;
} catch ( Throwable $e ) {
    error_log( '[healthz] ' . $e->getMessage() );
}

header( 'Content-Type: text/plain; charset=utf-8' );
header( 'Cache-Control: no-store' );

if ( $ok ) {
    http_response_code( 200 );
    echo 'OK';
} else {
    http_response_code( 503 );
    echo 'DB UNHEALTHY';
}
