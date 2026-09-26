<?php
/**
 * Idempotent database migration runner.
 * Runs all SQL migrations in rosariosis-spec/0*.sql in order.
 * Safe to run on every container start.
 */

$config = __DIR__ . '/config.inc.php';
if (!file_exists($config)) {
    fwrite(STDERR, "[MIGRATION] config.inc.php not found, skipping\n");
    exit(0);
}

require_once $config;
require_once __DIR__ . '/database.inc.php';

$conn = db_start(false);
if (!$conn) {
    fwrite(STDERR, "[MIGRATION] Database not available, skipping\n");
    exit(0);
}

$migration_dir = __DIR__ . '/rosariosis-spec';
$migrations = glob($migration_dir . '/[0-9][0-9][0-9]_*.sql');
sort($migrations);

foreach ($migrations as $migration_file) {
    $name = basename($migration_file);
    echo "[MIGRATION] Applying: $name\n";

    $sql = file_get_contents($migration_file);
    if (!$sql) {
        fwrite(STDERR, "[MIGRATION] ERROR: Could not read $name\n");
        continue;
    }

    $result = pg_query($conn, $sql);
    if ($result === false) {
        fwrite(STDERR, "[MIGRATION] ERROR in $name: " . pg_last_error($conn) . "\n");
    } else {
        echo "[MIGRATION] Applied: $name\n";
    }
}

echo "[MIGRATION] All migrations applied.\n";
