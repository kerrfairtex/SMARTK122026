<?php
/**
 * BBNIHS Dashboard Service
 *
 * Generates role-specific dashboard widgets based on the authenticated user's
 * profile and permissions.
 *
 * Source-grounded facts:
 *   - DashboardDefaultSmartCampus() in modules/SmartCampus/includes/Dashboard.inc.php
 *   - Widget classes: classes/RosarioSIS/Widgets.php, classes/RosarioSIS/StaffWidgets.php
 *   - User profiles: admin, teacher, parent, student
 *   - Session variables: $_SESSION['STAFF_ID'], $_SESSION['STUDENT_ID'], $_SESSION['UserSchool'], $_SESSION['UserSyear']
 */

declare(strict_types=1);

namespace BBNIHS\Dashboard;

use BBNIHS\Authz\Authz;
use BBNIHS\Context\Context;
use BBNIHS\DataAccess\DataAccess;

final class DashboardService
{
    public function __construct(
        private readonly Context $context,
        private readonly Authz $authz,
        private readonly DataAccess $data,
    ) {}

    /**
     * @return array<string, string|null>
     */
    public function widgets(): array
    {
        $profile = $this->authz->profile();
        $schoolId = (int)($this->context->schoolId() ?? 0);
        $syear = (int)($this->context->syear() ?? 0);

        if ($schoolId === 0 || $syear === 0) {
            return [];
        }

        $widgets = [];

        // School year label
        $widgets['School Year'] = 'SY ' . $syear . '–' . ($syear + 1);

        // Admin: total and active students
        if ($profile === 'admin') {
            $enroll = $this->data->queryOne(
                'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN (end_date IS NULL OR CURRENT_DATE <= end_date)
                                  AND CURRENT_DATE >= start_date THEN 1 END) AS active
                 FROM "student_enrollment"
                 WHERE school_id = :school_id AND syear = :syear',
                [':school_id' => $schoolId, ':syear' => $syear]
            );
            $total = (int)($enroll['total'] ?? 0);
            $active = (int)($enroll['active'] ?? 0);
            $widgets['Total Students'] = $total > 0 ? (string)$total : null;
            $widgets['Active Students'] = $active > 0 ? (string)$active : null;
        }

        // Admin/Teacher: teacher count
        if ($profile === 'admin' || $profile === 'teacher') {
            $teacherCount = (int) $this->data->queryOne(
                'SELECT COUNT(*) AS cnt FROM "staff"
                 WHERE syear = :syear AND profile = :profile
                   AND (schools IS NULL OR position(:school_id in schools) > 0)',
                [':syear' => $syear, ':profile' => 'teacher', ':school_id' => (string)$schoolId]
            )['cnt'];
            $widgets['Teachers'] = $teacherCount > 0 ? (string)$teacherCount : null;
        }

        // Admin/Teacher: section count
        if ($profile === 'admin' || $profile === 'teacher') {
            $sectionCount = (int) $this->data->queryOne(
                'SELECT COUNT(*) AS cnt FROM "course_periods"
                 WHERE syear = :syear AND school_id = :school_id',
                [':syear' => $syear, ':school_id' => $schoolId]
            )['cnt'];
            $widgets['Sections'] = $sectionCount > 0 ? (string)$sectionCount : null;
        }

        // Admin/Teacher: attendance today
        if ($profile === 'admin' || $profile === 'teacher') {
            $att = $this->data->queryOne(
                'SELECT COUNT(*) AS total,
                        COALESCE(SUM(CASE WHEN ad.state_value >= 1 THEN 1 END), 0) AS present
                 FROM "attendance_day" ad
                 JOIN "student_enrollment" se ON se.student_id = ad.student_id
                   AND se.syear = :syear
                   AND se.school_id = :school_id
                   AND CURRENT_DATE >= se.start_date
                   AND (se.end_date IS NULL OR CURRENT_DATE <= se.end_date)
                 WHERE ad.school_date = CURRENT_DATE',
                [':syear' => $syear, ':school_id' => $schoolId]
            );
            $attTotal = (int)($att['total'] ?? 0);
            $attPresent = (int)($att['present'] ?? 0);
            $attPct = $attTotal > 0 ? round(($attPresent / $attTotal) * 100) : 0;
            $widgets['Attendance Today'] = $attTotal > 0
                ? sprintf('%d/%d (%d%%)', $attPresent, $attTotal, $attPct)
                : 'No data';
        }

        // Teacher: class count
        if ($profile === 'teacher') {
            $staffId = (int)($this->context->staffId() ?? 0);
            if ($staffId > 0) {
                $classCount = (int) $this->data->queryOne(
                    'SELECT COUNT(*) AS cnt FROM "course_periods"
                     WHERE (teacher_id = :staff_id OR secondary_teacher_id = :staff_id)
                       AND school_id = :school_id AND syear = :syear',
                    [':staff_id' => $staffId, ':school_id' => $schoolId, ':syear' => $syear]
                )['cnt'];
                $widgets['My Classes'] = $classCount > 0 ? (string)$classCount : '0';
            }
        }

        // Student/parent: personal attendance
        if ($profile === 'student' || $profile === 'parent') {
            $studentId = (int)($this->context->studentId() ?? 0);
            if ($studentId > 0) {
                $total = (int) $this->data->queryOne(
                    'SELECT COUNT(*) AS cnt FROM "attendance_period" WHERE student_id = :sid',
                    [':sid' => $studentId]
                )['cnt'];
                $present = (int) $this->data->queryOne(
                    'SELECT COUNT(*) AS cnt FROM "attendance_period" ap
                     INNER JOIN "attendance_codes" ac ON ac.id = ap.attendance_code
                     WHERE ap.student_id = :sid AND ac.state_code = :state',
                    [':sid' => $studentId, ':state' => 'P']
                )['cnt'];
                $pct = $total > 0 ? round(($present / $total) * 100) : 100;
                $widgets['My Attendance'] = $total > 0 ? sprintf('%d/%d (%d%%)', $present, $total, $pct) : 'No data';
            }
        }

        // Remove empty values
        $widgets = array_filter($widgets, fn($v) => $v !== null && $v !== '');

        return $widgets;
    }
}
