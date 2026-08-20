<?php
/**
 * TakeAttendance.php — standalone attendance screen.
 * Uses Ajax.php: attendance_codes, attendance_list, attendance_save.
 *
 * @package SmartCampus
 * @since   1.2
 */

if ( ! isset( $_REQUEST['modfunc'] ) ) {
        $_REQUEST['modfunc'] = false;
}

if ( ! AllowUse() ) {
        exit;
}

$school_id = UserSchool();
$syear     = UserSyear();

switch ( $_REQUEST['modfunc'] ) {

        case 'list':
        default:

                // Same open TODO as SmartCampus.php: verify UserCoursePeriod()
                // returns a real value in this context before trusting it.
                $course_period_id = (int) UserCoursePeriod();
                $period_id         = (int) ( $_REQUEST['period_id'] ?? 0 );

                ?>
                <script>
                        window.SMARTCAMPUS_BOOT = {
                                schoolId: <?php echo (int) $school_id; ?>,
                                syear: <?php echo (int) $syear; ?>,
                                coursePeriodId: <?php echo $course_period_id; ?>,
                                periodId: <?php echo $period_id; ?>,
                                ajaxUrl: 'Modules.php?modname=SmartCampus/Ajax.php',
                                token: '<?php echo AttrEscape( $_SESSION['token'] ); ?>'
                        };
                </script>
                <?php
                include 'includes/AttendanceShell.inc.php';

                break;
}
