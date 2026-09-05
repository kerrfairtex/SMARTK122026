<?php
/**
 * BBNIHS Authorization Service
 *
 * Replaces AllowUse() / AllowEdit() from AllowEdit.fnc.php and the
 * profile/exception tables.
 *
 * Source-grounded facts:
 *   - Profiles: admin, teacher, parent, student
 *     (index.php:200-259; Menu.php:43 — student forced to parent for menu)
 *   - Exception tables: profile_exceptions, staff_exceptions
 *     (Warehouse.php:524-539; menu/Menu.php lines 51-63)
 *   - AllowUse gating: Menu.php:69, Modules.php:36
 *   - Exception arrays per module: modules slash Menu.php (13 explicit
 *     exceptions extracted in Phase 30 menu validation)
 *
 * The BBNIHS implementation distinguishes:
 *   - Profile permission (RBAC)
 *   - Resource ownership (ABAC)
 *   - Action (view/create/update/delete)
 *   - Scope (school / syear)
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Authz;

use BBNIHS\Context\Context;

final class Authz
{
    public const PROFILES = ['admin', 'teacher', 'parent', 'student'];
    public const ACTIONS  = ['view', 'create', 'update', 'delete'];

    /** @var array<string,array<string,array<int,string>>> */
    private array $exceptions;

    /**
     * @param array<string,array<string,array<int,string>>> $exceptions
     *        e.g. ['Users' => ['allow' => ['admin'], 'deny' => ['teacher','parent','student']]]
     */
    public function __construct(
        private readonly Context $ctx,
        array $exceptions = [],
    ) {
        // Source: 13 explicit exception entries from Phase 30 menu validation
        $this->exceptions = $exceptions ?: self::defaultExceptions();
    }

    public function profile(): ?string
    {
        return $this->ctx->profile();
    }

    public function isAuthenticated(): bool
    {
        return $this->ctx->staffId() !== null || $this->ctx->studentId() !== null;
    }

    public function allowUse(string $program): bool
    {
        if (!$this->isAuthenticated()) { return false; }
        $profile = $this->profile();

        // Source: Warehouse.php:69 — allowByDefault, with deny list
        $entry = $this->exceptions[$program] ?? null;
        if ($entry === null) {
            return true; // allowByDefault
        }
        $allow = $entry['allow'] ?? self::PROFILES;
        $deny  = $entry['deny']  ?? [];
        if (in_array($profile, $deny, true))  { return false; }
        if (in_array($profile, $allow, true)) { return true; }
        return false;
    }

    public function allowEdit(string $program): bool
    {
        if (!$this->allowUse($program)) { return false; }
        $profile = $this->profile();
        // Source: Menu.php:69-72 — AllowEdit for admin unless exception
        if ($profile === 'admin') { return true; }
        return $this->allowUse($program);
    }

    public function allowEditTemporary(string $action): void
    {
        // Source: AllowEdit.fnc.php AllowEditTemporary('start'|'stop')
        $this->session->set('AllowEditTemporary', $action);
    }

    public function checkResource(string $program, int $resourceOwnerId): bool
    {
        $profile = $this->profile();
        $sid = $this->ctx->staffId();
        $tid = $this->ctx->studentId();
        if ($profile === 'admin') { return true; }
        if ($profile === 'student' && $tid === $resourceOwnerId) { return true; }
        if ($profile === 'parent')  { return true; } // parent ownership resolved by EnrollmentRepository in a later phase
        if ($profile === 'teacher') { return true; } // teacher class assignment resolved by ClassAssignmentRepository in a later phase
        return false;
    }

    public function checkAction(string $program, string $action): bool
    {
        if (!in_array($action, self::ACTIONS, true)) { return false; }
        return $this->allowUse($program);
    }

    public function checkScope(): bool
    {
        return $this->ctx->school() !== null && $this->ctx->syear() !== null;
    }

    private function session(): \BBNIHS\Session\Session
    {
        return \BBNIHS\ServiceLocator::get(\BBNIHS\Session\Session::class);
    }

    /**
     * Source: modules slash Menu.php exception arrays — 13 explicit entries
     * extracted during Phase 30 menu validation.
     *
     * @return array<string,array<string,array<int,string>>>
     */
    public static function defaultExceptions(): array
    {
        return [
            'School_Setup/PortalNotes.php'          => ['deny' => ['teacher','parent','student']],
            'School_Setup/Rollover.php'             => ['deny' => ['teacher','parent','student']],
            'Students/Student.php&include=General_Info&student_id=new' => ['deny' => ['teacher','parent','student']],
            'Students/AssignOtherInfo.php'          => ['deny' => ['teacher','parent','student']],
            'Users/User.php&staff_id=new'           => ['deny' => ['teacher','parent','student']],
            'Custom/CreateParents.php'              => ['deny' => ['teacher','parent','student']],
            'Custom/NotifyParents.php'              => ['deny' => ['teacher','parent','student']],
            'Scheduling/Requests.php'               => ['deny' => ['teacher','parent','student']],
            'Scheduling/MassRequests.php'           => ['deny' => ['teacher','parent','student']],
            'Scheduling/Scheduler.php'              => ['deny' => ['teacher','parent','student']],
            'Attendance/AddAbsences.php'            => ['deny' => ['teacher','parent','student']],
            'Eligibility/AddActivity.php'           => ['deny' => ['teacher','parent','student']],
            'Food_Service/ServeMenus.php'           => ['deny' => ['teacher','parent','student']],
        ];
    }
}
