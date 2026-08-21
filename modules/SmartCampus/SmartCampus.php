<?php
/**
 * SmartCampus.php
 *
 * Main entry point for the SmartCampus module.
 * Reached as: index.php?modname=SmartCampus/SmartCampus.php&modfunc=portal
 *
 * @package SmartCampus
 * @since   1.0
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

        case 'portal':
        default:

                $enrolled_RET = DBQuery( "SELECT COUNT(*) AS student_count
                        FROM student_enrollment
                        WHERE school_id='" . (int) $school_id . "'
                        AND syear='" . (int) $syear . "'
                        AND ( end_date IS NULL OR end_date >= CURRENT_DATE )" );

                $enrolled_RES = db_fetch_row( $enrolled_RET );

                $total_enrolled = ! empty( $enrolled_RES['student_count'] )
                        ? (int) $enrolled_RES['student_count']
                        : 0;

                // TODO: course_period_id / period_id should come from the logged-in
                // teacher's current course period (core RosarioSIS exposes this via
                // UserCoursePeriod() in teacher context — verify it returns a real
                // value here before trusting it further).
                $course_period_id = (int) UserCoursePeriod();
                $period_id         = (int) ( $_REQUEST['period_id'] ?? 0 );

                ?>
                <script>
                        window.SMARTCAMPUS_BOOT = {
                                schoolId: <?php echo (int) $school_id; ?>,
                                syear: <?php echo (int) $syear; ?>,
                                totalEnrolled: <?php echo (int) $total_enrolled; ?>,
                                coursePeriodId: <?php echo $course_period_id; ?>,
                                periodId: <?php echo $period_id; ?>,
                                ajaxUrl: 'Modules.php?modname=SmartCampus/Ajax.php',
                                token: '<?php echo AttrEscape( $_SESSION['token'] ); ?>'
                        };
                </script>
                <?php
                include 'includes/PortalShell.inc.php';

                break;
}
