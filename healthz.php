<?php
// Health check: verify the app AND its database are reachable.
// A 200 only when both PHP bootstrap and a DB query succeed, so Render does
// not report a DB-dead instance as healthy.

$ok = false;
$detail = '';

try {
    $config = __DIR__ . '/config.inc.php';
    if ( ! file_exists( $config ) ) {
        throw new Exception( 'config.inc.php missing' );
    }

    require_once $config;
    require_once __DIR__ . '/database.inc.php';

    $conn = db_start( false );

    if ( $conn === false ) {
        throw new Exception( 'db_start returned false' );
    }

    $result = pg_query( $conn, 'SELECT 1' );

    if ( $result === false ) {
        throw new Exception( 'SELECT 1 failed: ' . pg_last_error( $conn ) );
    }

    $ok = true;
} catch ( Throwable $e ) {
    $detail = $e->getMessage();
}

if ( $ok ) {
    http_response_code( 200 );
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo 'OK';
} else {
    http_response_code( 503 );
    header( 'Content-Type: text/plain; charset=utf-8' );
    echo 'DB UNHEALTHY: ' . $detail;
}
