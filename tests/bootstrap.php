<?php
/**
 * BBNIHS Test Bootstrap
 *
 * Loads Composer autoloader and bootstraps the BBNIHS service graph.
 * Phase 1 tests run without a database; they exercise in-memory contracts.
 *
 * Source-grounded: every test below uses real RosarioSIS source as the oracle.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bbnihs/vendor/autoload.php';

use BBNIHS\Config\Config;
use BBNIHS\ServiceLocator;

$config = Config::fromEnv();
ServiceLocator::register(\BBNIHS\Config\Config::class, fn() => $config);

// Build the foundation graph in dependency order. Each test is free to
// use the ServiceLocator or to instantiate services directly. We do NOT
// require DashboardService / MenuService / IconMap / Router / Auth in the
// bootstrap, because some of those depend on full session/DB wiring that
// belongs to later phases.
$errorHandler = new \BBNIHS\Error\ErrorHandler();
ServiceLocator::register(\BBNIHS\Error\ErrorHandler::class, fn() => $errorHandler);

$session = new \BBNIHS\Session\Session($config);
ServiceLocator::register(\BBNIHS\Session\Session::class, fn() => $session);

$audit = new \BBNIHS\Audit\Audit($session);
ServiceLocator::register(\BBNIHS\Audit\Audit::class, fn() => $audit);

$csrf = new \BBNIHS\Csrf\Csrf($session, $audit);
ServiceLocator::register(\BBNIHS\Csrf\Csrf::class, fn() => $csrf);

$context = new \BBNIHS\Context\Context($session, $config);
ServiceLocator::register(\BBNIHS\Context\Context::class, fn() => $context);

$authz = new \BBNIHS\Authz\Authz($context, \BBNIHS\Authz\Authz::defaultExceptions());
ServiceLocator::register(\BBNIHS\Authz\Authz::class, fn() => $authz);
