<?php
/**
 * Integration test bootstrap for BBNIHS SmartCampus.
 *
 * Loads Composer autoloader but skips BBNIHS service-graph wiring because
 * integration tests connect directly to the database.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../bbnihs/vendor/autoload.php';
