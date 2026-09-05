<?php
/**
 * BBNIHS MenuService
 *
 * Replaces Menu.php and the per-module Menu.php files. Generates a BBNIHS-owned menu
 * definition from a structured tree.
 *
 * Source-grounded facts:
 *   - Menu.php:25-36  iterates active modules, includes Menu.php
 *   - Menu.php:38-44  student forced to parent for menu filtering
 *   - Menu.php:50-96  filters by profile, AllowUse, AllowEdit
 *   - modules slash Menu.php  15 module menu files, 4 profiles
 *   - 13 explicit exception entries (Phase 30 menu validation)
 *
 * The menu structure here is the canonical BBNIHS menu. It mirrors the
 * RosarioSIS tree but does NOT import or require any RosarioSIS globals.
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Menu;

use BBNIHS\Authz\Authz;
use BBNIHS\Icons\IconMap;

final class MenuService
{
    public const PROFILES = ['admin', 'teacher', 'parent', 'student'];

    public function __construct(
        private readonly Authz $authz,
        private readonly IconMap $icons,
    ) {}

    /**
     * Returns the menu definition, filtered by current profile + authorization.
     * Source: Phase 30 menu extraction (all 15 modules, all 4 profiles).
     *
     * @return array<int,array<string,mixed>>
     */
    public function definition(): array
    {
        $profile = $this->authz->profile();
        if ($profile === null) {
            return [];
        }
        if ($profile === 'student') {
            $profile = 'parent'; // Menu.php:43 — student forced to parent
        }
        $tree = $this->rawTree();
        return $this->filterFor($tree, $profile);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function rawTree(): array
    {
        // Source: Phase 30 menu extraction. Each node:
        //   id, label, icon, route, profiles, children
        return [
            ['id' => 'school_setup', 'label' => 'School', 'icon' => 'module.school_setup', 'route' => 'School_Setup/Calendar.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'school_setup.calendar',   'label' => 'Calendars',       'route' => 'School_Setup/Calendar.php',   'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'school_setup.marking',    'label' => 'Marking Periods', 'route' => 'School_Setup/MarkingPeriods.php','profiles' => ['admin','teacher','parent','student']],
                ['id' => 'school_setup.periods',    'label' => 'Periods',         'route' => 'School_Setup/Periods.php',   'profiles' => ['admin','teacher']],
                ['id' => 'school_setup.schools',    'label' => 'School Information','route' => 'School_Setup/Schools.php',  'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'school_setup.grade',      'label' => 'Grade Levels',    'route' => 'School_Setup/GradeLevels.php','profiles' => ['admin']],
                ['id' => 'school_setup.notes',      'label' => 'Portal Notes',    'route' => 'School_Setup/PortalNotes.php','profiles' => ['admin']],
                ['id' => 'school_setup.polls',      'label' => 'Portal Polls',    'route' => 'School_Setup/PortalPolls.php','profiles' => ['admin']],
                ['id' => 'school_setup.copy',       'label' => 'Copy School',     'route' => 'School_Setup/CopySchool.php','profiles' => ['admin']],
                ['id' => 'school_setup.fields',     'label' => 'School Fields',   'route' => 'School_Setup/SchoolFields.php','profiles' => ['admin']],
                ['id' => 'school_setup.config',     'label' => 'Configuration',   'route' => 'School_Setup/Configuration.php','profiles' => ['admin']],
                ['id' => 'school_setup.rollover',   'label' => 'Rollover',        'route' => 'School_Setup/Rollover.php','profiles' => ['admin']],
                ['id' => 'school_setup.accesslog',  'label' => 'Access Log',      'route' => 'School_Setup/AccessLog.php','profiles' => ['admin']],
            ]],
            ['id' => 'students', 'label' => 'Students', 'icon' => 'module.students', 'route' => 'Students/Student.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'students.info',       'label' => 'Student Info',          'route' => 'Students/Student.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'students.add',        'label' => 'Add a Student',         'route' => 'Students/Student.php&include=General_Info&student_id=new', 'profiles' => ['admin']],
                ['id' => 'students.group',      'label' => 'Group Assign Student Info','route' => 'Students/AssignOtherInfo.php', 'profiles' => ['admin']],
                ['id' => 'students.associate',  'label' => 'Associate Parents',     'route' => 'Students/AddUsers.php', 'profiles' => ['admin','teacher']],
                ['id' => 'students.adv_report', 'label' => 'Advanced Report',       'route' => 'Students/AdvancedReport.php', 'profiles' => ['admin','teacher']],
                ['id' => 'students.add_drop',   'label' => 'Add / Drop Report',     'route' => 'Students/AddDrop.php', 'profiles' => ['admin']],
                ['id' => 'students.breakdown',  'label' => 'Student Breakdown',     'route' => 'Students/StudentBreakdown.php', 'profiles' => ['admin']],
                ['id' => 'students.letters',    'label' => 'Print Letters',         'route' => 'Students/Letters.php', 'profiles' => ['admin','teacher']],
                ['id' => 'students.labels',     'label' => 'Print Student Labels',  'route' => 'Students/StudentLabels.php', 'profiles' => ['admin','teacher']],
                ['id' => 'students.print_info', 'label' => 'Print Student Info',    'route' => 'Students/PrintStudentInfo.php', 'profiles' => ['admin']],
                ['id' => 'students.fields',     'label' => 'Student Fields',        'route' => 'Students/StudentFields.php', 'profiles' => ['admin']],
                ['id' => 'students.enroll_codes','label' => 'Enrollment Codes',     'route' => 'Students/EnrollmentCodes.php', 'profiles' => ['admin']],
                ['id' => 'students.registration','label' => 'Registration',         'route' => 'Custom/Registration.php', 'profiles' => ['admin','parent','student']],
            ]],
            ['id' => 'users', 'label' => 'Users', 'icon' => 'module.users', 'route' => 'Users/User.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'users.info',          'label' => 'User Info',       'route' => 'Users/User.php', 'profiles' => ['admin','teacher','parent']],
                ['id' => 'users.add',           'label' => 'Add a User',      'route' => 'Users/User.php&staff_id=new', 'profiles' => ['admin']],
                ['id' => 'users.associate',     'label' => 'Associate Students with Parents','route' => 'Users/AddStudents.php', 'profiles' => ['admin']],
                ['id' => 'users.prefs',         'label' => 'My Preferences',  'route' => 'Users/Preferences.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'users.profiles',      'label' => 'User Profiles',   'route' => 'Users/Profiles.php', 'profiles' => ['admin']],
                ['id' => 'users.exceptions',    'label' => 'User Permissions','route' => 'Users/Exceptions.php', 'profiles' => ['admin']],
                ['id' => 'users.fields',        'label' => 'User Fields',     'route' => 'Users/UserFields.php', 'profiles' => ['admin']],
                ['id' => 'users.notify',        'label' => 'Notify Parents',  'route' => 'Custom/NotifyParents.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'scheduling', 'label' => 'Scheduling', 'icon' => 'module.scheduling', 'route' => 'Scheduling/Schedule.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'scheduling.schedule',   'label' => 'Schedule',          'route' => 'Scheduling/Schedule.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'scheduling.requests',   'label' => 'Student Requests',  'route' => 'Scheduling/Requests.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'scheduling.mass',       'label' => 'Group Schedule',    'route' => 'Scheduling/MassSchedule.php', 'profiles' => ['admin']],
                ['id' => 'scheduling.mass_req',   'label' => 'Group Requests',    'route' => 'Scheduling/MassRequests.php', 'profiles' => ['admin']],
                ['id' => 'scheduling.mass_drops', 'label' => 'Group Drops',       'route' => 'Scheduling/MassDrops.php', 'profiles' => ['admin']],
                ['id' => 'scheduling.courses',    'label' => 'Courses',           'route' => 'Scheduling/Courses.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'scheduling.print',      'label' => 'Print Schedules',   'route' => 'Scheduling/PrintSchedules.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'scheduling.lists',      'label' => 'Print Class Lists', 'route' => 'Scheduling/PrintClassLists.php', 'profiles' => ['admin','teacher']],
                ['id' => 'scheduling.pictures',   'label' => 'Print Class Pictures','route' => 'Scheduling/PrintClassPictures.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'scheduling.scheduler',  'label' => 'Run Scheduler',     'route' => 'Scheduling/Scheduler.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'grades', 'label' => 'Grades', 'icon' => 'module.grades', 'route' => 'Grades/Grades.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'grades.grades',     'label' => 'Grades',              'route' => 'Grades/Grades.php', 'profiles' => ['teacher']],
                ['id' => 'grades.assign',     'label' => 'Assignments',        'route' => 'Grades/Assignments.php', 'profiles' => ['teacher']],
                ['id' => 'grades.anomalous',  'label' => 'Anomalous Grades',    'route' => 'Grades/AnomalousGrades.php', 'profiles' => ['teacher']],
                ['id' => 'grades.progress',   'label' => 'Progress Reports',    'route' => 'Grades/ProgressReports.php', 'profiles' => ['teacher','parent','student']],
                ['id' => 'grades.breakdown',  'label' => 'Grade Breakdown',     'route' => 'Grades/GradebookBreakdown.php', 'profiles' => ['teacher']],
                ['id' => 'grades.student',    'label' => 'Gradebook Grades',    'route' => 'Grades/StudentGrades.php', 'profiles' => ['parent','student']],
                ['id' => 'grades.student_a',  'label' => 'Assignments',        'route' => 'Grades/StudentAssignments.php', 'profiles' => ['parent','student']],
                ['id' => 'grades.final',      'label' => 'Final Grades',        'route' => 'Grades/FinalGrades.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'grades.input',      'label' => 'Input Final Grades',  'route' => 'Grades/InputFinalGrades.php', 'profiles' => ['admin','teacher']],
                ['id' => 'grades.reports',    'label' => 'Report Cards',        'route' => 'Grades/ReportCards.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'grades.gpa',        'label' => 'GPA / Class Rank',    'route' => 'Grades/GPARankList.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'grades.transcripts','label' => 'Transcripts',         'route' => 'Grades/Transcripts.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'grades.config',     'label' => 'Configuration',       'route' => 'Grades/Configuration.php', 'profiles' => ['admin','teacher']],
                ['id' => 'grades.scales',     'label' => 'Grading Scales',      'route' => 'Grades/ReportCardGrades.php', 'profiles' => ['admin','teacher']],
                ['id' => 'grades.comments',   'label' => 'Report Card Comments','route' => 'Grades/ReportCardComments.php', 'profiles' => ['admin','teacher']],
                ['id' => 'grades.comment_codes','label' => 'Comment Codes',     'route' => 'Grades/ReportCardCommentCodes.php', 'profiles' => ['admin','teacher']],
            ]],
            ['id' => 'attendance', 'label' => 'Attendance', 'icon' => 'module.attendance', 'route' => 'Attendance/TakeAttendance.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'attendance.take',     'label' => 'Take Attendance',     'route' => 'Attendance/TakeAttendance.php', 'profiles' => ['teacher']],
                ['id' => 'attendance.admin',    'label' => 'Administration',      'route' => 'Attendance/Administration.php', 'profiles' => ['admin']],
                ['id' => 'attendance.add',      'label' => 'Add Absences',        'route' => 'Attendance/AddAbsences.php', 'profiles' => ['admin']],
                ['id' => 'attendance.daily',    'label' => 'Daily Summary',       'route' => 'Attendance/DailySummary.php', 'profiles' => ['parent','student','admin','teacher']],
                ['id' => 'attendance.chart',    'label' => 'Attendance Chart',    'route' => 'Attendance/DailySummary.php', 'profiles' => ['admin','teacher']],
                ['id' => 'attendance.complete', 'label' => 'Teacher Completion',  'route' => 'Attendance/TeacherCompletion.php', 'profiles' => ['admin','teacher']],
                ['id' => 'attendance.ada',      'label' => 'Average Daily Attendance','route' => 'Attendance/Percent.php', 'profiles' => ['admin']],
                ['id' => 'attendance.codes',    'label' => 'Attendance Codes',    'route' => 'Attendance/AttendanceCodes.php', 'profiles' => ['admin']],
                ['id' => 'attendance.summary',  'label' => 'Attendance Summary',  'route' => 'Custom/AttendanceSummary.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'eligibility', 'label' => 'Activities', 'icon' => 'module.eligibility', 'route' => 'Eligibility/Student.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'eligibility.student',  'label' => 'Student Screen',  'route' => 'Eligibility/Student.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'eligibility.add',      'label' => 'Add Activity',    'route' => 'Eligibility/AddActivity.php', 'profiles' => ['admin']],
                ['id' => 'eligibility.list',     'label' => 'Student List',     'route' => 'Eligibility/StudentList.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'eligibility.teacher',  'label' => 'Enter Eligibility','route' => 'Eligibility/EnterEligibility.php', 'profiles' => ['teacher']],
                ['id' => 'eligibility.complete', 'label' => 'Teacher Completion','route' => 'Eligibility/TeacherCompletion.php', 'profiles' => ['admin']],
                ['id' => 'eligibility.activities','label' => 'Activities',      'route' => 'Eligibility/Activities.php', 'profiles' => ['admin']],
                ['id' => 'eligibility.entry',    'label' => 'Entry Times',      'route' => 'Eligibility/EntryTimes.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'discipline', 'label' => 'Discipline', 'icon' => 'module.discipline', 'route' => 'Discipline/Referrals.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'discipline.referrals', 'label' => 'Referrals',      'route' => 'Discipline/Referrals.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'discipline.add',       'label' => 'Add Referral',   'route' => 'Discipline/MakeReferral.php', 'profiles' => ['admin','teacher']],
                ['id' => 'discipline.cat',       'label' => 'Category Breakdown','route' => 'Discipline/CategoryBreakdown.php', 'profiles' => ['admin']],
                ['id' => 'discipline.cat_time',  'label' => 'Category Breakdown over Time','route' => 'Discipline/CategoryBreakdownTime.php', 'profiles' => ['admin']],
                ['id' => 'discipline.field',     'label' => 'Breakdown by Student Field','route' => 'Discipline/StudentFieldBreakdown.php', 'profiles' => ['admin']],
                ['id' => 'discipline.log',       'label' => 'Discipline Log',  'route' => 'Discipline/ReferralLog.php', 'profiles' => ['admin']],
                ['id' => 'discipline.form',      'label' => 'Referral Form',   'route' => 'Discipline/DisciplineForm.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'accounting', 'label' => 'Accounting', 'icon' => 'module.accounting', 'route' => 'Accounting/Incomes.php', 'profiles' => ['admin','teacher'], 'children' => [
                ['id' => 'accounting.incomes',   'label' => 'Incomes',         'route' => 'Accounting/Incomes.php', 'profiles' => ['admin']],
                ['id' => 'accounting.expenses',  'label' => 'Expenses',        'route' => 'Accounting/Expenses.php', 'profiles' => ['admin']],
                ['id' => 'accounting.salaries',  'label' => 'Salaries',        'route' => 'Accounting/Salaries.php', 'profiles' => ['admin','teacher']],
                ['id' => 'accounting.staff_pay', 'label' => 'Staff Payments',  'route' => 'Accounting/StaffPayments.php', 'profiles' => ['admin','teacher']],
                ['id' => 'accounting.daily',     'label' => 'Daily Transactions','route' => 'Accounting/DailyTransactions.php', 'profiles' => ['admin']],
                ['id' => 'accounting.staff_bal', 'label' => 'Staff Balances',  'route' => 'Accounting/StaffBalances.php', 'profiles' => ['admin']],
                ['id' => 'accounting.statements','label' => 'Print Statements','route' => 'Accounting/Statements.php', 'profiles' => ['admin','teacher']],
                ['id' => 'accounting.categories','label' => 'Categories',      'route' => 'Accounting/Categories.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'student_billing', 'label' => 'Student Billing', 'icon' => 'module.student_billing', 'route' => 'Student_Billing/StudentFees.php', 'profiles' => ['admin','parent','student'], 'children' => [
                ['id' => 'billing.fees',         'label' => 'Fees',            'route' => 'Student_Billing/StudentFees.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'billing.payments',     'label' => 'Payments',        'route' => 'Student_Billing/StudentPayments.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'billing.mass_fees',    'label' => 'Mass Assign Fees','route' => 'Student_Billing/MassAssignFees.php', 'profiles' => ['admin']],
                ['id' => 'billing.mass_pay',     'label' => 'Mass Assign Payments','route' => 'Student_Billing/MassAssignPayments.php', 'profiles' => ['admin']],
                ['id' => 'billing.balances',     'label' => 'Student Balances','route' => 'Student_Billing/StudentBalances.php', 'profiles' => ['admin']],
                ['id' => 'billing.daily',        'label' => 'Daily Transactions','route' => 'Student_Billing/DailyTransactions.php', 'profiles' => ['admin','parent','student']],
                ['id' => 'billing.statements',   'label' => 'Print Statements','route' => 'Student_Billing/Statements.php', 'profiles' => ['admin','parent','student']],
            ]],
            ['id' => 'food_service', 'label' => 'Food Service', 'icon' => 'module.food_service', 'route' => 'Food_Service/Accounts.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'food.accounts',    'label' => 'Accounts',      'route' => 'Food_Service/Accounts.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'food.statements',  'label' => 'Statements',    'route' => 'Food_Service/Statements.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'food.transactions', 'label' => 'Transactions',  'route' => 'Food_Service/Transactions.php', 'profiles' => ['admin']],
                ['id' => 'food.serve',       'label' => 'Serve Meals',   'route' => 'Food_Service/ServeMenus.php', 'profiles' => ['admin']],
                ['id' => 'food.activity',    'label' => 'Activity Report','route' => 'Food_Service/ActivityReport.php', 'profiles' => ['admin']],
                ['id' => 'food.tx_report',   'label' => 'Transactions Report','route' => 'Food_Service/TransactionsReport.php', 'profiles' => ['admin']],
                ['id' => 'food.meals',       'label' => 'Meal Reports',  'route' => 'Food_Service/MenuReports.php', 'profiles' => ['admin']],
                ['id' => 'food.daily',       'label' => 'Daily Menus',   'route' => 'Food_Service/DailyMenus.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'food.items',       'label' => 'Meal Items',    'route' => 'Food_Service/MenuItems.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'food.kiosk',       'label' => 'Kiosk Preview', 'route' => 'Food_Service/Kiosk.php', 'profiles' => ['admin']],
            ]],
            ['id' => 'resources', 'label' => 'Resources', 'icon' => 'module.resources', 'route' => 'Resources/Resources.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'resources.list', 'label' => 'Resources', 'route' => 'Resources/Resources.php', 'profiles' => ['admin','teacher','parent','student']],
            ]],
            ['id' => 'smartcampus', 'label' => 'SmartCampus', 'icon' => 'module.smartcampus', 'route' => 'SmartCampus/SmartCampus.php', 'profiles' => ['admin','teacher','parent','student'], 'children' => [
                ['id' => 'smartcampus.portal',     'label' => 'Portal',           'route' => 'SmartCampus/SmartCampus.php', 'profiles' => ['admin','teacher','parent','student']],
                ['id' => 'smartcampus.enrollment', 'label' => 'Enrollment List',  'route' => 'SmartCampus/Enrollment.php', 'profiles' => ['admin']],
                ['id' => 'smartcampus.attendance', 'label' => 'Take Attendance',  'route' => 'SmartCampus/TakeAttendance.php', 'profiles' => ['admin','teacher']],
                ['id' => 'smartcampus.discipline',  'label' => 'Discipline Log',   'route' => 'SmartCampus/DisciplineLog.php', 'profiles' => ['admin']],
            ]],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $tree
     * @return array<int,array<string,mixed>>
     */
    private function filterFor(array $tree, string $profile): array
    {
        $out = [];
        foreach ($tree as $node) {
            $profiles = $node['profiles'] ?? [];
            if (!in_array($profile, $profiles, true)) {
                continue;
            }
            $children = $node['children'] ?? [];
            $filteredChildren = $this->filterFor($children, $profile);
            $node['children'] = $filteredChildren;
            if (empty($children) && empty($node['route'])) {
                continue;
            }
            $out[] = $node;
        }
        return $out;
    }

    /**
     * @return array{id:string,label:string,icon:string,children:array<int,mixed>}
     */
    public function dashboardItem(): array
    {
        return [
            'id'    => 'dashboard',
            'label' => 'Dashboard',
            'icon'  => $this->icons->get('dashboard.kpi'),
            'children' => [],
        ];
    }

    public function logoutItem(): array
    {
        return [
            'id'    => 'logout',
            'label' => 'Logout',
            'icon'  => $this->icons->get('auth.logout'),
            'route' => '?modfunc=logout',
            'children' => [],
        ];
    }
}
