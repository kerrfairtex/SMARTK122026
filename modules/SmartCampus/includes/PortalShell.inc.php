<?php
/**
 * PortalShell.inc.php
 *
 * Outputs the SmartCampus client markup inside RosarioSIS's normal
 * page chrome (Warehouse( 'header' ) has already run in SmartCampus.php
 * by the time Modules.php reaches this include; Warehouse( 'footer' )
 * runs after Modules.php returns).
 *
 * readfile() here is a placeholder for local development so you can
 * iterate on client.html without touching PHP. For production, inline
 * client.html's <body> contents directly into this file (and move its
 * <style>/<script> blocks into assets/themes/.../stylesheet.css and
 * warehouse.js) so the page passes RosarioSIS's Content-Security-Policy
 * headers set in Warehouse.php — inline <script> tags will otherwise
 * be blocked once CSP is enforced (not just Report-Only).
 *
 * @package SmartCampus
 * @since   1.0
 */
?>
<div id="smartcampus-root">
	<?php
	$client_path = __DIR__ . '/../client.html';

	if ( is_file( $client_path ) ) {
		// Dev convenience only — see note above for production.
		readfile( $client_path );
	} else {
		echo '<p>SmartCampus client.html not found at ' . AttrEscape( $client_path ) . '</p>';
	}
	?>
</div>
