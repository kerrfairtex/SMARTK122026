<?php
/**
 * Dashboard
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Dashboard build
 *
 * Calls, for each active and user module
 * the `Dashboard[Module_Name]` function.
 *
 * Place your add-on module `Dashboard[Module_Name]` function in the functions.php file.
 *
 * @uses $_REQUEST['_ROSARIO_DASHBOARD']
 *
 * @todo For example, set $_REQUEST['_ROSARIO_DASHBOARD']['export'] to 1 to export data.
 * In URL: &_ROSARIO_DASHBOARD[export]=1
 *
 * @global $RosarioModules
 * @global $_ROSARIO
 * @see DashboardModule.fnc.php for default core modules `Dashboard[Module_Name]` functions.
 * @since 4.0
 * @deprecated since 13.0
 */
function Dashboard()
{
	trigger_error(
		'Dashboard() function is deprecated since RosarioSIS 13.0.',
		E_USER_DEPRECATED
	);
}

/**
 * Dashboard Output HTML
 * Modules HTML inside PopTable
 *
 * @global $_ROSARIO
 * @since 4.0
 * @since 7.7 Move Dashboard() call outside.
 * @deprecated since 13.0 Use (new RosarioSIS\Functions\Dashboard)->output() instead
 *
 * @param integer $rows Number of modules per row, defaults to 4. Optional.
 */
function DashboardOutput( $rows = 4 )
{
	trigger_error(
		'DashboardOutput() function is deprecated since RosarioSIS 13.0. Please use (new RosarioSIS\Functions\Dashboard)->output() instead.',
		E_USER_DEPRECATED
	);

	(new RosarioSIS\Functions\Dashboard)->output( $rows );
}

/**
 * Add module HTML to Dashboard
 *
 * @global $_ROSARIO Add module HTML to $_ROSARIO['Dashboard'][ $module ]
 * @since 4.0
 * @deprecated since 13.0
 *
 * @param string  $module Module.
 * @param string  $html   Dashboard HTML.
 * @param boolean $append Append HTML.
 */
function DashboardAdd( $module, $html, $append = true )
{
	trigger_error(
		'DashboardAdd() function is deprecated since RosarioSIS 13.0.',
		E_USER_DEPRECATED
	);
}
