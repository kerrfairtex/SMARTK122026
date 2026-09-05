<?php
/**
 * BBNIHS Bootstrap
 *
 * Wires all foundation services into the ServiceLocator. Called once per
 * request. Phase 1 is a no-network bootstrap: it does not call out to the
 * database; it only sets up the in-memory object graph.
 *
 * Source-grounded wiring:
 *   - Config fromEnv()      bbnihs/Config/Config.php
 *   - Session build         Warehouse.php:220-267
 *   - CSRF                  Warehouse.php:275-340
 *   - Authz exceptions      modules slash Menu.php (13 entries)
 *   - Audit session         index.php:182-298
 *
 * Phase 1 build — additive. No existing file is included or required.
 */

declare(strict_types=1);

namespace BBNIHS;

use BBNIHS\Config\Config;
use BBNIHS\Session\Session;
use BBNIHS\Csrf\Csrf;
use BBNIHS\Authz\Authz;
use BBNIHS\Auth\Auth;
use BBNIHS\Router\Router;
use BBNIHS\Context\Context;
use BBNIHS\DataAccess\DataAccess;
use BBNIHS\Audit\Audit;
use BBNIHS\Error\ErrorHandler;
use BBNIHS\Menu\MenuService;
use BBNIHS\Dashboard\DashboardService;
use BBNIHS\Icons\IconMap;

final class Bootstrap
{
    public static function init(): void
    {
        if (ServiceLocator::has(Config::class)) {
            return; // already booted
        }

        $config = Config::fromEnv();
        ServiceLocator::register(Config::class, fn() => $config);

        $errors = new ErrorHandler();
        ServiceLocator::register(ErrorHandler::class, fn() => $errors);

        $session = new Session($config);
        ServiceLocator::register(Session::class, fn() => $session);

        $audit = new Audit($session);
        ServiceLocator::register(Audit::class, fn() => $audit);

        $csrf = new Csrf($session, $audit);
        ServiceLocator::register(Csrf::class, fn() => $csrf);

        $context = new Context($session, $config);
        ServiceLocator::register(Context::class, fn() => $context);

        $authz = new Authz($context, Authz::defaultExceptions());
        ServiceLocator::register(Authz::class, fn() => $authz);

        $data = new DataAccess($config, $errors);
        ServiceLocator::register(DataAccess::class, fn() => $data);

        $auth = new Auth($session, $authz, $context, $audit, $config, $data);
        ServiceLocator::register(Auth::class, fn() => $auth);

        $icons = new IconMap();
        ServiceLocator::register(IconMap::class, fn() => $icons);

        $menu = new MenuService($authz, $icons);
        ServiceLocator::register(MenuService::class, fn() => $menu);

        $dashboard = new DashboardService($context, $authz, $data);
        ServiceLocator::register(DashboardService::class, fn() => $dashboard);

        $router = new Router($menu, $authz, $csrf);
        ServiceLocator::register(Router::class, fn() => $router);
    }
}
