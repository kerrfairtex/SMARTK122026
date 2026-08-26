<?php
/**
 * SmartCampus Menu
 *
 * Registers this module's programs in the RosarioSIS side menu.
 * Follows the same $menu global convention as core modules
 * (see modules/Attendance/Menu.php in the upstream repo).
 *
 * @uses $menu global var
 * @see  Menu.php in root folder
 *
 * @package SmartCampus
 * @since   1.0
 */

$menu['SmartCampus']['admin'] = [
	'title'   => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php'      => _( 'Portal' ),
	1 => _( 'Enrollment' ),
	'SmartCampus/Enrollment.php'       => _( 'Enrollment List' ),
	2 => _( 'Attendance' ),
	'SmartCampus/TakeAttendance.php'   => _( 'Take Attendance' ),
	3 => _( 'Discipline' ),
	'SmartCampus/DisciplineLog.php'    => _( 'Discipline Log' ),
] + issetVal( $menu['SmartCampus']['admin'], [] );

$menu['SmartCampus']['teacher'] = [
	'title'   => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php'      => _( 'Portal' ),
	'SmartCampus/TakeAttendance.php'   => _( 'Take Attendance' ),
] + issetVal( $menu['SmartCampus']['teacher'], [] );

$menu['SmartCampus']['parent'] = [
	'title'   => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php'      => _( 'Portal' ),
] + issetVal( $menu['SmartCampus']['parent'], [] );
