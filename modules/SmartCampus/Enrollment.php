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
                include 'includes/EnrollmentShell.inc.php';

                break;
}
