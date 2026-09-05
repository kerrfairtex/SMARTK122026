<?php
/**
 * BBNIHS Authz tests
 *
 * Source-grounded: 13 exception entries from per-module Menu.php files
 * extracted during Phase 30 menu validation.
 */

declare(strict_types=1);

use BBNIHS\Authz\Authz;
use BBNIHS\Context\Context;
use BBNIHS\Session\Session;
use BBNIHS\Config\Config;
use PHPUnit\Framework\TestCase;

final class AuthzTest extends TestCase
{
    private function makeAuthz(string $profile, ?int $staffId = 1, ?int $studentId = null): Authz
    {
        // Seed session directly without actually starting a session in CLI.
        $_SESSION = [
            'STAFF_ID'  => $staffId,
            'STUDENT_ID' => $studentId,
            'PROFILE'    => $profile,
        ];
        $config = Config::fromEnv();
        $session = new Session($config);
        $ctx = new Context($session, $config);
        return new Authz($ctx, Authz::defaultExceptions());
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testExceptionGatesDenyLowerProfiles(string $program, array $deniedProfiles): void
    {
        foreach ($deniedProfiles as $profile) {
            $authz = $this->makeAuthz($profile);
            $this->assertFalse(
                $authz->allowUse($program),
                "Expected $profile to be DENIED on $program"
            );
        }
    }

    /**
     * @dataProvider exceptionProvider
     */
    public function testAdminAlwaysAllowedOnExceptionalPrograms(string $program): void
    {
        $authz = $this->makeAuthz('admin');
        $this->assertTrue(
            $authz->allowUse($program),
            "Expected admin to be ALLOWED on $program"
        );
    }

    public static function exceptionProvider(): array
    {
        return [
            ['School_Setup/PortalNotes.php',           ['teacher','parent','student']],
            ['School_Setup/Rollover.php',              ['teacher','parent','student']],
            ['Students/Student.php&include=General_Info&student_id=new', ['teacher','parent','student']],
            ['Students/AssignOtherInfo.php',           ['teacher','parent','student']],
            ['Users/User.php&staff_id=new',            ['teacher','parent','student']],
            ['Custom/CreateParents.php',               ['teacher','parent','student']],
            ['Custom/NotifyParents.php',               ['teacher','parent','student']],
            ['Scheduling/Requests.php',                ['teacher','parent','student']],
            ['Scheduling/MassRequests.php',            ['teacher','parent','student']],
            ['Scheduling/Scheduler.php',               ['teacher','parent','student']],
            ['Attendance/AddAbsences.php',             ['teacher','parent','student']],
            ['Eligibility/AddActivity.php',            ['teacher','parent','student']],
            ['Food_Service/ServeMenus.php',            ['teacher','parent','student']],
        ];
    }

    public function testUnauthenticatedIsDenied(): void
    {
        $_SESSION = [];
        $config = Config::fromEnv();
        $session = new Session($config);
        $ctx = new Context($session, $config);
        $authz = new Authz($ctx, Authz::defaultExceptions());
        $this->assertFalse($authz->isAuthenticated());
        $this->assertFalse($authz->allowUse('any/program.php'));
    }

    public function testAllowByDefaultForUnlistedPrograms(): void
    {
        $authz = $this->makeAuthz('teacher');
        $this->assertTrue($authz->allowUse('Grades/SomeFutureProgram.php'));
    }

    public function testAllowEditImpliesAllowUse(): void
    {
        $authz = $this->makeAuthz('admin');
        $this->assertTrue($authz->allowEdit('School_Setup/PortalNotes.php'));
    }

    public function testCheckActionRejectsUnknown(): void
    {
        $authz = $this->makeAuthz('admin');
        $this->assertFalse($authz->checkAction('School_Setup/PortalNotes.php', 'purge'));
    }

    public function testStudentProfile(): void
    {
        $authz = $this->makeAuthz('student', staffId: null, studentId: 99);
        $this->assertSame('student', $authz->profile());
    }

    public function testResourceOwnership(): void
    {
        $authz = $this->makeAuthz('student', staffId: null, studentId: 99);
        $this->assertTrue($authz->checkResource('any', 99));
        $this->assertFalse($authz->checkResource('any', 100));
    }
}
