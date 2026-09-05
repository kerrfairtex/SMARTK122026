<?php
/**
 * BBNIHS IconMap
 *
 * Maps semantic actions / entities to BBNIHS-owned icon identifiers.
 * Replaces ad-hoc icon usage from the legacy FlatSIS theme.
 *
 * Icons are referenced by semantic name in the menu / dashboard definitions.
 * The actual rendering (CSS class, SVG, font) is selected by the consumer
 * (e.g. BBNIHS\Menu\MenuService, BBNIHS\Dashboard\DashboardService) and is
 * free to be implemented in any BBNIHS-owned way.
 *
 * Source-grounded facts:
 *   - 13 module menu icons
 *   - CRUD action icons (add/edit/remove/save/print/search)
 *   - State icons (active, error, success, warning)
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Icons;

final class IconMap
{
    /** @var array<string,string> semantic-name => icon-id */
    private array $icons;

    public function __construct()
    {
        // The icon-id values are BBNIHS-owned placeholders. Replace with the
        // BBNIHS icon catalog (CSS class, SVG sprite, or font glyph) at
        // implementation time. The mapping here is the contract.
        $this->icons = [
            // Module icons
            'module.school_setup'    => 'icon-school',
            'module.students'       => 'icon-students',
            'module.users'          => 'icon-users',
            'module.scheduling'     => 'icon-schedule',
            'module.grades'         => 'icon-grades',
            'module.attendance'     => 'icon-attendance',
            'module.eligibility'    => 'icon-eligibility',
            'module.discipline'     => 'icon-discipline',
            'module.accounting'     => 'icon-accounting',
            'module.student_billing'=> 'icon-billing',
            'module.food_service'   => 'icon-food',
            'module.resources'      => 'icon-resources',
            'module.custom'         => 'icon-custom',
            'module.smartcampus'    => 'icon-smartcampus',

            // CRUD action icons
            'action.add'            => 'icon-plus',
            'action.edit'           => 'icon-edit',
            'action.remove'         => 'icon-trash',
            'action.save'           => 'icon-save',
            'action.search'         => 'icon-search',
            'action.filter'         => 'icon-filter',
            'action.print'          => 'icon-print',
            'action.export'         => 'icon-export',
            'action.back'           => 'icon-back',
            'action.view'           => 'icon-view',
            'action.cancel'         => 'icon-cancel',
            'action.confirm'        => 'icon-check',

            // State icons
            'state.success'         => 'icon-check-circle',
            'state.warning'         => 'icon-alert',
            'state.error'           => 'icon-alert-circle',
            'state.loading'         => 'icon-spinner',
            'state.locked'          => 'icon-lock',
            'state.unlocked'        => 'icon-unlock',

            // Auth
            'auth.login'            => 'icon-sign-in',
            'auth.logout'           => 'icon-sign-out',
            'auth.user'             => 'icon-user',
            'auth.csrf'             => 'icon-shield',

            // Dashboard
            'dashboard.kpi'         => 'icon-kpi',
            'dashboard.chart'       => 'icon-chart',
            'dashboard.list'        => 'icon-list',
            'dashboard.notice'      => 'icon-notice',
        ];
    }

    public function get(string $semantic): string
    {
        return $this->icons[$semantic] ?? 'icon-missing';
    }

    public function has(string $semantic): bool
    {
        return isset($this->icons[$semantic]);
    }

    /**
     * @return array<string,string>
     */
    public function all(): array
    {
        return $this->icons;
    }
}
