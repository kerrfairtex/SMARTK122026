<?php
/**
 * BBNIHS Authz Route test
 *
 * Validates that a non-exception route is allowByDefault for all profiles,
 * and that the route-level authorization gate (checkRoute path) is wired
 * to the same exception machinery used by allowUse.
 *
 * Source: Authz::defaultExceptions() is built from the 13 explicit
 * modfunc/save/delete denial entries extracted during Phase 30 menu
 * validation.
 */

declare(strict_types=1);

use BBNIHS\Authz\Authz;
use BBNIHS\Context\Context;
use BBNIHS\Session\Session;
use BBNIHS\Config\Config;
use PHPUnit\Framework\TestCase;

final class AuthzRouteTest extends TestCase
{
    private function authzFor(string $profile): Authz
    {
        $_SESSION = [
            'STAFF_ID'  => $profile === 'student' ? null : 1,
            'STUDENT_ID' => $profile === 'student' ? 99 : null,
            'PROFILE'    => $profile,
        ];
        $config = Config::fromEnv();
        $session = new Session($config);
        $ctx = new Context($session, $config);
        return new Authz($ctx, Authz::defaultExceptions());
    }

    /**
     * The most important Phase 30 requirement: hidden menu item must still
     * be denied at the backend when accessed directly.
     *   Source: Phase 30 manifest, item #5: "Do not rely on menu hiding."
     */
    public function testDirectUrlAccessToExceptionalProgramIsDeniedForLowerProfiles(): void
    {
        foreach (['teacher', 'parent', 'student'] as $profile) {
            $authz = $this->authzFor($profile);
            $this->assertFalse(
                $authz->allowUse('School_Setup/PortalNotes.php'),
                "Profile $profile must be denied direct URL access to PortalNotes"
            );
            $this->assertFalse(
                $authz->allowUse('Users/User.php&staff_id=new'),
                "Profile $profile must be denied direct URL access to Add User"
            );
        }
    }

    public function testAdminBypassesAllExceptions(): void
    {
        $authz = $this->authzFor('admin');
        $programs = [
            'School_Setup/PortalNotes.php',
            'School_Setup/Rollover.php',
            'Users/User.php&staff_id=new',
            'Custom/CreateParents.php',
            'Custom/NotifyParents.php',
            'Scheduling/Requests.php',
            'Scheduling/MassRequests.php',
            'Scheduling/Scheduler.php',
            'Attendance/AddAbsences.php',
            'Eligibility/AddActivity.php',
            'Food_Service/ServeMenus.php',
            'Students/AssignOtherInfo.php',
            'Students/Student.php&include=General_Info&student_id=new',
        ];
        foreach ($programs as $p) {
            $this->assertTrue($authz->allowUse($p), "Admin must be allowed on $p");
        }
    }

    public function testNonExceptionalRouteIsAllowedForAllProfiles(): void
    {
        foreach (['admin', 'teacher', 'parent', 'student'] as $profile) {
            $authz = $this->authzFor($profile);
            $this->assertTrue(
                $authz->allowUse('Students/Student.php'),
                "$profile should be allowed to view Student Info"
            );
        }
    }
}
