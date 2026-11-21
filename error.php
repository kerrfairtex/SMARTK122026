<?php
/**
 * Branded error page for Apache ErrorDocument 403/404/500.
 *
 * Self-contained on purpose: no database or RosarioSIS bootstrap, so it
 * still renders when the app itself is what failed.
 *
 * @package SmartCampus
 */

$code = (int) ( $_SERVER['REDIRECT_STATUS'] ?? 404 );

$messages = [
	403 => [ 'Access not allowed', 'This page is not available to the public.' ],
	404 => [ 'Page not found', 'The page you are looking for does not exist or has moved.' ],
	500 => [ 'Something went wrong', 'The site hit an unexpected problem. Please try again in a few minutes.' ],
];

if ( ! isset( $messages[ $code ] ) )
{
	$code = 404;
}

[ $title, $text ] = $messages[ $code ];

http_response_code( $code );
header( 'Content-Type: text/html; charset=utf-8' );
header( 'Cache-Control: no-store' );
header( 'X-Content-Type-Options: nosniff' );
header( "Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; frame-ancestors 'none'; base-uri 'self'; form-action 'none'" );
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo $code . ' — ' . htmlspecialchars( $title ); ?> | Batu-Batu National High School</title>
<link rel="icon" href="/favicon.ico" sizes="32x32">
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Inter:wght@400;600&family=IBM+Plex+Mono:wght@500&display=swap">
<style>
	body {
		margin: 0;
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: var(--space-3, 1rem);
		background: var(--ink-deep, #0A1420);
		color: var(--sand, #EDE6D6);
		font-family: var(--font-body, system-ui, sans-serif);
	}
	main { max-width: 440px; text-align: center; }
	.code {
		font-family: var(--font-utility, monospace);
		color: var(--sun-gold, #F4B400);
		letter-spacing: 0.08em;
		font-size: 0.85rem;
	}
	h1 {
		font-family: var(--font-display, Georgia, serif);
		font-size: 1.75rem;
		margin: var(--space-2, 0.5rem) 0 var(--space-3, 1rem);
	}
	p { color: var(--foam, #CFE8E4); line-height: 1.6; margin: 0 0 var(--space-4, 1.5rem); }
	.actions { display: flex; flex-wrap: wrap; gap: var(--space-2, 0.5rem); justify-content: center; }
	a {
		display: inline-flex;
		align-items: center;
		min-height: 44px;
		padding: 0 var(--space-4, 1.5rem);
		border-radius: var(--radius-2, 6px);
		font-weight: 600;
		text-decoration: none;
	}
	.primary { background: var(--sun-gold, #F4B400); color: var(--ink-deep, #0A1420); }
	.secondary { border: 1px solid var(--foam-overlay-16, rgba(207, 232, 228, 0.16)); color: var(--sand, #EDE6D6); }
	a:focus-visible { outline: 2px solid var(--sun-gold, #F4B400); outline-offset: 2px; }
</style>
</head>
<body>
<main>
	<div class="code">ERROR <?php echo $code; ?></div>
	<h1><?php echo htmlspecialchars( $title ); ?></h1>
	<p><?php echo htmlspecialchars( $text ); ?></p>
	<div class="actions">
		<a class="primary" href="/">Go to homepage</a>
		<a class="secondary" href="/enroll_status.php">Check application status</a>
	</div>
</main>
</body>
</html>
