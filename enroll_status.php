<?php
/**
 * Public Enrollment Application Status
 * 
 * Allows applicants to check their application status using their reference number.
 * Only exposes status — never reveals learner name, birth date, parent info, etc.
 * 
 * Route: /enroll_status.php?ref=BATU-2026-XXXXXX
 * JSON API: /enroll_api.php?action=status&ref=BATU-2026-XXXXXX
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

// Security headers
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://accounts.google.com https://apis.google.com https://www.googletagmanager.com https://www.google-analytics.com https://ssl.google-analytics.com https://cdn.ampproject.org; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\'; frame-ancestors \'none\'; form-action \'self\'; base-uri \'self\'; object-src \'none\';');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// Database connection helper (uses existing database.inc.php bootstrap)
function db_conn() {
    static $c = null;
    if ($c === null) {
        $c = db_start(false);
        if ($c === false) {
            throw new Exception('Database unavailable');
        }
    }
    return $c;
}

// HTML page for browser access
$ref = trim((string)($_GET['ref'] ?? ''));
$statusResult = null;
$statusError = null;

if ($ref !== '') {
    if (!preg_match('/^BATU-\d{4}-\d{6}$/', $ref)) {
        $statusError = 'Invalid reference format.';
    } else {
        try {
            $conn = db_conn();
            $sql = "SELECT ref, status, created_at FROM kerrfairtex.enrollment_applications WHERE ref = $1";
            $result = pg_query_params($conn, $sql, [$ref]);
            
            if ($result !== false) {
                $row = pg_fetch_assoc($result);
                if ($row) {
                    $statusResult = $row;
                } else {
                    $statusError = 'Application not found.';
                }
            } else {
                $statusError = 'Unable to check status. Please try again.';
            }
        } catch (Exception $e) {
            $statusError = 'Unable to check status. Please try again.';
        }
    }
}

// Map status for display
$displayStatus = '';
if ($statusResult) {
    switch ($statusResult['status']) {
        case 'submitted': $displayStatus = 'Submitted'; break;
        case 'under_review': $displayStatus = 'Under Review'; break;
        case 'approved': $displayStatus = 'Approved'; break;
        case 'rejected': $displayStatus = 'Rejected'; break;
        case 'enrolled': $displayStatus = 'Enrolled'; break;
        default: $displayStatus = ucfirst(str_replace('_', ' ', $statusResult['status']));
    }
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enrollment Status | Batu-Batu National High School</title>
  <link rel="canonical" href="https://smartcampk12.onrender.com/enroll_status.php">
  <link rel="stylesheet" href="/css/tokens.css">
  <link rel="stylesheet" href="/css/base.css">
  <link rel="stylesheet" href="/css/components.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    body { font-family: var(--font-body); background: var(--ink-deep); color: var(--sand); min-height: 100vh; display: flex; flex-direction: column; }
    .container { max-width: 600px; margin: 0 auto; padding: var(--space-4); width: 100%; }
    .card { background: var(--tide-teal); border-radius: var(--radius-3); padding: var(--space-5); margin-top: var(--space-5); }
    h1 { font-family: var(--font-display); font-size: 1.5rem; margin-bottom: var(--space-3); }
    .form-field { margin-bottom: var(--space-4); }
    label { display: block; font-weight: 600; margin-bottom: var(--space-2); color: var(--foam); }
    input[type="text"] { width: 100%; padding: var(--space-3); border: 1px solid var(--foam); border-radius: var(--radius-2); background: var(--ink-deep); color: var(--sand); font-size: 1rem; font-family: var(--font-utility); }
    .btn-primary { display: inline-block; padding: var(--space-3) var(--space-4); background: var(--sun-gold); color: var(--ink-deep); border: none; border-radius: var(--radius-2); font-weight: 700; cursor: pointer; text-decoration: none; }
    .btn-primary:hover { opacity: 0.9; }
    .status-display { background: var(--ink-deep); border-radius: var(--radius-2); padding: var(--space-4); margin-top: var(--space-4); }
    .status-label { font-size: 0.85rem; color: var(--foam); text-transform: uppercase; letter-spacing: 0.05em; }
    .status-value { font-size: 1.25rem; font-weight: 700; color: var(--sun-gold); margin-top: var(--space-1); }
    .error { color: var(--reef-coral); margin-top: var(--space-3); }
    .back-link { display: inline-block; margin-top: var(--space-4); color: var(--foam); text-decoration: none; }
    .back-link:hover { color: var(--sun-gold); }
  </style>
</head>
<body>
  <div class="container">
    <h1>Check Enrollment Status</h1>
    
    <div class="card">
      <form method="get" class="form-field">
        <label for="ref">Reference Number</label>
        <input type="text" id="ref" name="ref" placeholder="BATU-2026-XXXXXX" value="<?= htmlspecialchars($ref) ?>" required>
        <button type="submit" class="btn-primary" style="margin-top: var(--space-3);">Check Status</button>
      </form>
      
      <?php if ($statusError): ?>
        <p class="error"><?= htmlspecialchars($statusError) ?></p>
      <?php endif; ?>
      
      <?php if ($statusResult): ?>
        <div class="status-display">
          <div class="status-label">Reference</div>
          <div class="status-value"><?= htmlspecialchars($statusResult['ref']) ?></div>
          <div class="status-label" style="margin-top: var(--space-3);">Status</div>
          <div class="status-value"><?= htmlspecialchars($displayStatus) ?></div>
          <div class="status-label" style="margin-top: var(--space-3);">Submitted</div>
          <div class="status-value"><?= htmlspecialchars(date('F j, Y', strtotime($statusResult['created_at']))) ?></div>
        </div>
      <?php endif; ?>
    </div>
    
    <a href="/" class="back-link">&larr; Back to Enrollment</a>
  </div>
</body>
</html>
