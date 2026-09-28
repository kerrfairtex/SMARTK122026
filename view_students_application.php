<?php
/**
 * View Students Application - Teacher/Admin Login Card
 *
 * Shows a login card styled after the SMARTCAMPUS-K12 design system.
 * Authenticates against the RosarioSIS staff table (teacher/admin profiles only).
 * On success: redirects to dashboard.php
 * On failure: shows error message.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

// Security headers
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://accounts.google.com https://apis.google.com https://www.googletagmanager.com https://www.google-analytics.com https://ssl.google-analytics.com https://cdn.ampproject.org; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'; object-src 'none';");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// If already authenticated as teacher or admin, redirect to dashboard
if (isset($_SESSION['STAFF_ID']) && in_array($_SESSION['PROFILE'] ?? '', ['admin', 'teacher'])) {
    header('Location: dashboard.php');
    exit;
}

$login_error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim((string)$_POST['username']);
    $password = (string)$_POST['password'];

    if ($username === '' || $password === '') {
        $login_error = 'Please enter both username and password.';
    } else {
        try {
            $conn = db_start(false);
            if ($conn === false) {
                throw new Exception('Database unavailable');
            }

            // Check credentials against staff table — only admin and teacher profiles allowed
            $sql = "SELECT STAFF_ID, USERNAME, PASSWORD, PROFILE FROM staff
                    WHERE UPPER(USERNAME) = UPPER($1)
                      AND SYEAR = (SELECT VALUE FROM config WHERE title = 'SYEAR')
                      AND PROFILE IN ('admin', 'teacher')
                      AND PROFILE NOT IN ('none', '')";

            $result = pg_query_params($conn, $sql, [$username]);
            $staff = $result ? pg_fetch_assoc($result) : false;

            if (!$staff) {
                $login_error = 'Invalid credentials or insufficient permissions.';
            } else {
                // Verify password using RosarioSIS-compatible hash verification
                if (crypt($password, $staff['PASSWORD']) === $staff['PASSWORD']) {
                    // Success - set session
                    $_SESSION['STAFF_ID'] = (int)$staff['STAFF_ID'];
                    $_SESSION['USERNAME'] = $staff['USERNAME'];
                    $_SESSION['PROFILE'] = $staff['PROFILE'];
                    $_SESSION['LAST_LOGIN'] = date('Y-m-d H:i:s');
                    $_SESSION['FAILED_LOGIN'] = null;

                    // Ensure school context is available
                    if (!isset($_SESSION['UserSchool'])) {
                        $_SESSION['UserSchool'] = DBGetOne("SELECT id FROM schools LIMIT 1");
                    }
                    if (!isset($_SESSION['UserSyear'])) {
                        $_SESSION['UserSyear'] = Config('SYEAR');
                    }

                    // Redirect to dashboard
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $login_error = 'Invalid username or password.';
                }
            }
        } catch (Exception $e) {
            $login_error = 'Login temporarily unavailable. Please try again.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <!-- build: 220019fc2 2026-08-29T08:46:36.167Z -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SmartCampus K12</title>
  <link rel="icon" href="favicon.ico" sizes="32x32">
  <link rel="icon" href="apple-touch-icon.png" sizes="128x128">
  <link rel="canonical" href="https://smartcampk12.onrender.com/view_students_application.php">
  <link rel="stylesheet" href="/css/tokens.css">
  <link rel="stylesheet" href="/css/base.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: var(--space-3);
      font-family: var(--font-body);
      background: var(--ink-deep);
      color: var(--text);
    }
    .login-card {
      width: 100%;
      max-width: 380px;
      background: var(--panel, #15181C);
      border: 1px solid var(--border, #262A2F);
      border-radius: 14px;
      padding: 2.5rem 2rem;
      box-shadow: var(--elev-3);
    }
    .brand-row {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 1.5rem;
      justify-content: center;
    }
    .brand-mark {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--sun-gold);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .brand-mark span {
      font-family: var(--font-utility, monospace);
      font-weight: 600;
      font-size: 14px;
      color: var(--ink-deep);
    }
    .brand-name {
      font-weight: 600;
      font-size: 1rem;
      color: var(--sand);
    }
    .login-card h1 {
      font-size: 1.1rem;
      margin: 0 0 0.5rem;
      font-weight: 600;
      color: var(--sand);
      text-align: center;
    }
    .login-card p {
      font-size: 0.85rem;
      color: var(--muted, #8B9096);
      margin: 0 0 1.5rem;
      text-align: center;
    }
    .field {
      margin-bottom: 1.25rem;
    }
    .field label {
      display: block;
      font-size: 0.75rem;
      color: var(--muted, #8B9096);
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .field input {
      width: 100%;
      background: var(--bg, #0E1013);
      border: 1px solid var(--border, #262A2F);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      color: #ECE9E4;
      color: var(--text);
      font-size: 0.95rem;
      transition: border-color 0.2s;
    }
    .field input:focus {
      outline: none;
      border-color: var(--accent, #E8A33D);
    }
    .btn-login {
      width: 100%;
      background: var(--sun-gold);
      color: var(--ink-deep);
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1rem;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: opacity 0.2s;
    }
    .btn-login:hover { opacity: 0.9; }
    .error-msg {
      color: var(--reef-coral, #C9836A);
      font-size: 0.8rem;
      margin-bottom: 1rem;
      text-align: center;
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.8rem;
      color: var(--muted, #8B9096);
    }
    .back-link:hover { color: var(--sand); }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="brand-row">
      <div class="brand-mark"><span>SK</span></div>
      <div class="brand-name">SmartCampus K12</div>
    </div>
    <h1>Admin and Teachers only!</h1>

    <?php if (!empty($login_error)): ?>
      <div class="error-msg"><?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-login">Login</button>
    </form>

    <a href="/index.php?logout=1" class="back-link">&larr; Back to Login</a>
  </div>
</body>
</html>