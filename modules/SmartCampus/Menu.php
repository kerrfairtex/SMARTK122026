<?php
/**
 * SmartCampus Menu
 *
 * Registers this module's programs in the RosarioSIS side menu.
 * Follows the same "default" entry convention as core modules
 * (see modules/Attendance/Menu.php in the upstream repo).
 *
 * @package SmartCampus
 * @since   1.0
 */

$MENU['default'] = 'modfunc=portal';

$MENU['modules/SmartCampus/SmartCampus.php']['title']         = 'Portal';
$MENU['modules/SmartCampus/SmartCampus.php']['url']           = 'modname=SmartCampus/SmartCampus.php&modfunc=portal';
$MENU['modules/SmartCampus/SmartCampus.php']['index']         = 0;

$MENU['modules/SmartCampus/Enrollment.php']['title']          = 'Enrollment';
$MENU['modules/SmartCampus/Enrollment.php']['url']            = 'modname=SmartCampus/Enrollment.php&modfunc=list';
$MENU['modules/SmartCampus/Enrollment.php']['index']          = 1;

$MENU['modules/SmartCampus/TakeAttendance.php']['title']      = 'Take Attendance';
$MENU['modules/SmartCampus/TakeAttendance.php']['url']        = 'modname=SmartCampus/TakeAttendance.php&modfunc=list';
$MENU['modules/SmartCampus/TakeAttendance.php']['index']      = 2;

$MENU['modules/SmartCampus/DisciplineLog.php']['title']       = 'Discipline Log';
$MENU['modules/SmartCampus/DisciplineLog.php']['url']         = 'modname=SmartCampus/DisciplineLog.php&modfunc=list';
$MENU['modules/SmartCampus/DisciplineLog.php']['index']       = 3;
