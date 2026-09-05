<?php
/**
 * BBNIHS Router
 *
 * Replaces Modules.php. The BBNIHS router does NOT import or require the
 * legacy Modules.php; it parses BBNIHS routes from the request and applies
 * authentication, authorization, and CSRF.
 *
 * Source-grounded facts (from Modules.php):
 *   - Empty modname redirects to index.php    Modules.php:15-19
 *   - AllowUse() check before dispatch         Modules.php:36
 *   - $modname ends with .php and contains no ..   Modules.php:48-50
 *   - On AllowUse failure with username -> hacking log  Modules.php:61-64
 *   - Warehouse('header') / footer wrap unless SmartCampus/Ajax  Modules.php:31-69
 *
 * Route parameters preserved from RosarioSIS (Phase 30 audit):
 *   modname, modfunc, include, student_id, staff_id, redirect_to, token, locale,
 *   sidefunc, search_modfunc, category_id, _ROSARIO_PDF
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Router;

use BBNIHS\Menu\MenuService;
use BBNIHS\Authz\Authz;
use BBNIHS\Csrf\Csrf;
use BBNIHS\Context\Context;

final class Router
{
    public const ROUTE_HOME        = 'home';
    public const ROUTE_DASHBOARD   = 'dashboard';
    public const ROUTE_MODULE      = 'module';
    public const ROUTE_LOGIN       = 'login';
    public const ROUTE_LOGOUT      = 'logout';
    public const ROUTE_FIRSTLOGIN  = 'first-login';
    public const ROUTE_PASSWORD    = 'password-reset';
    public const ROUTE_HELP        = 'help';
    public const ROUTE_NOT_FOUND   = 'not-found';

    public function __construct(
        private readonly MenuService $menu,
        private readonly Authz $authz,
        private readonly Csrf $csrf,
        private readonly Context $context,
    ) {}

    public function dispatch(array $get, array $post, array $server): RoutingResult
    {
        $this->csrf->enforce($server, $post + $get);

        $modname = (string)($get['modname'] ?? '');
        $modfunc = (string)($get['modfunc'] ?? '');

        if ($modfunc === 'logout') {
            return new RoutingResult(self::ROUTE_LOGOUT, ['reason' => $get['reason'] ?? null]);
        }
        if ($modfunc === 'first-login') {
            return new RoutingResult(self::ROUTE_FIRSTLOGIN, ['token' => $get['token'] ?? null]);
        }
        if ($modname === '') {
            if ($this->authz->isAuthenticated()) {
                return new RoutingResult(self::ROUTE_DASHBOARD, []);
            }
            return new RoutingResult(self::ROUTE_LOGIN, []);
        }
        if (!$this->authz->isAuthenticated()) {
            return new RoutingResult(self::ROUTE_LOGIN, ['redirect_to' => $modname]);
        }
        if (!$this->authz->allowUse($modname)) {
            return new RoutingResult(self::ROUTE_NOT_FOUND, ['reason' => 'forbidden', 'modname' => $modname]);
        }
        return new RoutingResult(self::ROUTE_MODULE, [
            'modname' => $modname,
            'modfunc' => $modfunc,
            'include' => $get['include'] ?? null,
            'student_id' => $get['student_id'] ?? null,
            'staff_id' => $get['staff_id'] ?? null,
            'category_id' => $get['category_id'] ?? null,
            'search_modfunc' => $get['search_modfunc'] ?? null,
            'sidefunc' => $get['sidefunc'] ?? null,
        ]);
    }

    public function csrfToken(): string
    {
        return $this->csrf->token();
    }
}
