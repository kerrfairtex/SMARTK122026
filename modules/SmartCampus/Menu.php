<?php
/**
 * SmartCampus module Menu entries
 *
 * @uses $menu global var
 *
 * @see  Menu.php in root folder
 *
 * @package RosarioSIS
 * @subpackage modules
 */

$menu['SmartCampus']['admin'] = [
	'title' => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php' => _( 'My Dashboard' ),
	'SmartCampus/Enrollment.php' => _( 'Enrollment' ),
	'SmartCampus/TakeAttendance.php' => _( 'Take Attendance' ),
	'SmartCampus/DisciplineLog.php' => _( 'Discipline Log' ),
] + issetVal( $menu['SmartCampus']['admin'], [] );

$menu['SmartCampus']['teacher'] = [
	'title' => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php' => _( 'My Dashboard' ),
	'SmartCampus/TakeAttendance.php' => _( 'Take Attendance' ),
	'SmartCampus/DisciplineLog.php' => _( 'Discipline Log' ),
] + issetVal( $menu['SmartCampus']['teacher'], [] );

$menu['SmartCampus']['parent'] = [
	'title' => _( 'SmartCampus' ),
	'default' => 'SmartCampus/SmartCampus.php',
	'SmartCampus/SmartCampus.php' => _( 'My Dashboard' ),
] + issetVal( $menu['SmartCampus']['parent'], [] );

