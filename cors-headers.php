<?php
/**
 * CORS Header Block — Add to top of API entry points
 * 
 * Files that need this:
 * - modules/SmartCampus/Ajax.php
 * - modules/SmartCampus/SmartCampus.php (if serving API requests)
 * 
 * Replace <your-vercel-app> with your actual Vercel deployment URL
 */

// CORS headers for Vercel frontend ↔ Render API communication.
// Origin is configurable via env var so it can be updated without a code change.
$vercel_origin = getenv( 'CORS_ORIGIN' ) ?: 'https://your-vercel-app.vercel.app';
header("Access-Control-Allow-Origin: {$vercel_origin}");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
