<?php
/**
 * DisciplineShell.inc.php — dev-mode include. See PortalShell.inc.php
 * for the CSP production note.
 */
?>
<div id="smartcampus-root">
        <?php
        $client_path = __DIR__ . '/../discipline.html';
        if ( is_file( $client_path ) ) {
                readfile( $client_path );
        } else {
                echo '<p>discipline.html not found at ' . AttrEscape( $client_path ) . '</p>';
        }
        ?>
</div>
