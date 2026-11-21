<?php
/**
 * Enrollment.php — read-only enrollment list.
 * Uses Ajax.php: enrollment_list. No save action yet — see note in
 * Ajax.php's enrollment_list case about unconfirmed code columns.
 *
 * @package SmartCampus
 * @since   1.2
 */

// Direct-access guard: this file requires the RosarioSIS bootstrap. If opened directly,
// route to the proper in-app entry instead of fatalling.
if ( ! function_exists( 'AllowUse' ) ) {
	if ( PHP_SAPI !== 'cli' ) {
		$modfunc = isset( $_REQUEST['modfunc'] ) && $_REQUEST['modfunc'] !== '' ? '&modfunc=' . urlencode( (string) $_REQUEST['modfunc'] ) : '';
		header( 'Location: ../Modules.php?modname=SmartCampus/' . basename( __FILE__ ) . $modfunc );
	}
	exit;
}

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

                ?>
                <script>
                        window.SMARTCAMPUS_BOOT = {
                                schoolId: <?php echo (int) $school_id; ?>,
                                syear: <?php echo (int) $syear; ?>,
                                ajaxUrl: 'Modules.php?modname=SmartCampus/Ajax.php',
                                token: '<?php echo AttrEscape( $_SESSION['token'] ); ?>'
                        };
                </script>
                <?php
                // Online applications are reviewed in the staff dashboard. Relative
                // link: this page is served from the site root as Modules.php.
                echo '<div style="padding:0.75rem 1rem;background:#f0f9ff;border-bottom:1px solid #bae6fd;font-size:0.9rem;">';
                echo '<strong>BBNIHS Enrollment Applications:</strong> ';
                echo '<a href="dashboard.php" style="color:#1d4ed8;text-decoration:none;font-weight:600;">View Application Queue &rarr;</a> &middot; ';
                echo '<a href="dashboard.php?view=students" style="color:#1d4ed8;text-decoration:none;font-weight:600;">Enrolled Students &rarr;</a>';
                echo '</div>';

                include 'includes/EnrollmentShell.inc.php';

                break;
}
