<?php
/**
 * AttendanceShell.inc.php — dev-mode include. See PortalShell.inc.php
 * for the CSP production note (applies here too): inline <script> in
 * the included HTML will be blocked once CSP is enforced, not just
 * Report-Only.
 */
?>
<div id="smartcampus-root">
        <?php
        $client_path = __DIR__ . '/../attendance.html';
        if ( is_file( $client_path ) ) {
                readfile( $client_path );
        } else {
                echo '<p>attendance.html not found at ' . AttrEscape( $client_path ) . '</p>';
        }
        ?>
</div>
