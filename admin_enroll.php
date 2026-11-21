<?php
/**
 * Legacy admin enrollment page.
 *
 * Enrollment review now lives in the staff dashboard (dashboard.php), which
 * shares one enroll path with enroll_api.php
 * (ProgramFunctions/SmartCampusEnrollment.fnc.php). This redirect keeps old
 * bookmarks working.
 *
 * @package SmartCampus
 */

$view = ( $_GET['view'] ?? '' ) === 'students' ? '?view=students' : '';

header( 'Location: dashboard.php' . $view, true, 301 );
exit;
