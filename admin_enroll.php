<?php
/**
 * SmartCampus K-12 — Admin Enrollment Management
 *
 * Full user journey: admin reviews, filters, and updates enrollment applications.
 *
 * Security:
 *   - Requires admin login via existing RosarioSIS session
 *   - CSRF protection on state-changing actions
 *   - Input validation on all fields
 *
 * Source-grounded facts:
 *   - enrollment_applications table in kerrfairtex schema
 *   - Columns: id, ref, learner_name, birth_date, sex, birthplace, address,
 *              grade_level, school_year, enrollment_type, parent_name,
 *              parent_relationship, parent_contact, parent_address, parent_email,
 *              prev_school, prev_school_address, last_grade, prev_sy,
 *              learner_ref_no, documents, status, created_at, updated_at, notes
 *   - Accessible to admin profile only
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: text/html; charset=utf-8');

// Require admin login via existing RosarioSIS session
if (!isset($_SESSION['STAFF_ID']) || ($_SESSION['PROFILE'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$schoolYear = (string)($_GET['school_year'] ?? date('Y') . '-' . ((int)date('Y') + 1));

// CSRF helper
function csrf_token(): string {
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = (string)($_POST['token'] ?? '');
    return hash_equals((string)($_SESSION['token'] ?? ''), $token);
}

// Handle admin actions
$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($action)) {
    if (!verify_csrf()) {
        die('CSRF token invalid. Please reload and try again.');
    }

    $applicationId = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
    if ($applicationId <= 0) {
        die('Invalid application ID.');
    }

    $conn = db_conn();

    if ($action === 'approve') {
        $notes = (string)($_POST['notes'] ?? '');
        $notes_esc = pg_escape_string($conn, $notes);
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'Approved', notes = '$notes_esc', updated_at = CURRENT_TIMESTAMP WHERE id = $applicationId";
        pg_query($conn, $sql);
        header('Location: admin_enroll.php?approved=' . $applicationId);
        exit;
    }

    if ($action === 'reject') {
        $notes = (string)($_POST['notes'] ?? '');
        $notes_esc = pg_escape_string($conn, $notes);
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'Rejected', notes = '$notes_esc', updated_at = CURRENT_TIMESTAMP WHERE id = $applicationId";
        pg_query($conn, $sql);
        header('Location: admin_enroll.php?rejected=' . $applicationId);
        exit;
    }

    if ($action === 'enroll') {
        $app = db_fetch_one("SELECT * FROM kerrfairtex.enrollment_applications WHERE id = $applicationId");
        if (!$app) {
            die('Application not found.');
        }

        $firstName = (string)($app['first_name'] ?? '');
        $lastName = (string)($app['last_name'] ?? '');
        $baseUsername = strtolower(substr($firstName, 0, 1) . $lastName);
        $username = $baseUsername;

        // Ensure unique username
        $counter = 1;
        while (db_fetch_one("SELECT STUDENT_ID FROM \"students\" WHERE USERNAME = '" . pg_escape_string($conn, $username) . "'")) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        // Generate random password
        $plainPassword = substr(bin2hex(random_bytes(8)), 0, 10);
        $hashedPassword = crypt($plainPassword, '$6$' . substr(sha1((string)random_int(999999999, 9999999999)), 0, 16));

        $year = (int)explode('-', $schoolYear)[0];

        db_trans_start();
        $studentId = db_insert('students', [
            'USERNAME' => $username,
            'PASSWORD' => $hashedPassword,
            'FIRST_NAME' => $firstName,
            'LAST_NAME' => $lastName,
            'MIDDLE_NAME' => (string)($app['middle_name'] ?? ''),
            'BIRTH_DATE' => (string)($app['birth_date'] ?? ''),
            'SEX' => (string)($app['sex'] ?? ''),
            'ADDRESS' => (string)($app['address'] ?? ''),
            'SYEAR' => $year,
        ]);

        db_insert('student_enrollment', [
            'STUDENT_ID' => $studentId,
            'SCHOOL_ID' => 1,
            'SYEAR' => $year,
            'START_DATE' => (string)($app['classes_begin'] ?? date('Y-m-d')),
            'END_DATE' => null,
            'GRADE_ID' => (int)($app['grade_level'] ?? 0),
        ]);
        db_trans_commit();

        $notes_val = "Student ID: $studentId, Username: $username";
        $notes_esc = pg_escape_string($conn, $notes_val);
        pg_query($conn, "UPDATE kerrfairtex.enrollment_applications SET status = 'Enrolled', notes = '$notes_esc', updated_at = CURRENT_TIMESTAMP WHERE id = $applicationId");

        header('Location: admin_enroll.php?enrolled=' . $applicationId . '&student_id=' . $studentId);
        exit;
    }
}

// Fetch applications
$search = (string)($_GET['search'] ?? '');
$statusFilter = (string)($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
if ($search !== '') {
    $where[] = "(ref ILIKE '" . pg_escape_string(db_conn(), '%' . $search . '%') . "' OR learner_name ILIKE '" . pg_escape_string(db_conn(), '%' . $search . '%') . "' OR parent_name ILIKE '" . pg_escape_string(db_conn(), '%' . $search . '%') . "')";
}
if ($statusFilter !== '') {
    $where[] = "status = '" . pg_escape_string(db_conn(), $statusFilter) . "'";
}

$whereSql = implode(' AND ', $where);

$total = (int)db_fetch_one("SELECT COUNT(*) AS cnt FROM kerrfairtex.enrollment_applications WHERE $whereSql")['cnt'];
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);

$applications = db_query(
    "SELECT id, ref, learner_name, birth_date, sex, address, grade_level, school_year, "
    . "enrollment_type, parent_name, parent_contact, status, created_at, updated_at, notes "
    . "FROM kerrfairtx.enrollment_applications "
    . "WHERE $whereSql "
    . "ORDER BY created_at DESC "
    . "LIMIT $perPage OFFSET $offset"
);

$flash = '';
if (isset($_GET['approved'])) {
    $flash = '<div class="alert alert-success">Application approved successfully.</div>';
} elseif (isset($_GET['rejected'])) {
    $flash = '<div class="alert alert-warning">Application rejected.</div>';
} elseif (isset($_GET['enrolled'])) {
    $flash = '<div class="alert alert-success">Student enrolled successfully. Check notes for credentials.</div>';
}

$token = csrf_token();

?><!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Enrollment Applications | Batu-Batu NHS</title>
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="page-admin">
  <header class="site-header">
    <div class="container">
      <h1><a href="/">Batu-Batu National High School</a></h1>
      <nav class="main-nav">
        <a href="?modfunc=list&modname=SmartCampus/Enrollment.php">Enrollment</a>
        <a href="?modfunc=list&modname=Students/Student.php">Students</a>
        <a href="?modfunc=list&modname=Scheduling/Schedule.php">Schedules</a>
      </nav>
      <div class="user-info"><?= htmlspecialchars((string)($_SESSION['USERNAME'] ?? 'Admin')) ?> | <a href="?modfunc=logout">Logout</a></div>
    </div>
  </header>

  <main class="container">
    <h2>Enrollment Applications — <?= htmlspecialchars($schoolYear) ?></h2>

    <?= $flash ?>

    <form method="get" class="filters">
      <input type="text" name="search" placeholder="Search ref, learner, parent..." value="<?= htmlspecialchars($search) ?>">
      <select name="status">
        <option value="">All statuses</option>
        <option value="Submitted" <?= $statusFilter === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
        <option value="Approved" <?= $statusFilter === 'Approved' ? 'selected' : '' ?>>Approved</option>
        <option value="Rejected" <?= $statusFilter === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="Enrolled" <?= $statusFilter === 'Enrolled' ? 'selected' : '' ?>>Enrolled</option>
      </select>
      <input type="hidden" name="school_year" value="<?= htmlspecialchars($schoolYear) ?>">
      <button type="submit">Filter</button>
      <a href="admin_enroll.php" class="btn-secondary">Reset</a>
    </form>

    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Ref</th>
            <th>Learner</th>
            <th>Grade</th>
            <th>Parent</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($applications as $app): ?>
            <tr class="status-<?= strtolower((string)$app['status']) ?>">
              <td><?= htmlspecialchars((string)$app['ref']) ?></td>
              <td>
                <?= htmlspecialchars((string)$app['learner_name']) ?><br>
                <small><?= htmlspecialchars((string)$app['sex']) ?> | <?= htmlspecialchars((string)($app['birth_date'] ?? '')) ?></small>
              </td>
              <td><?= htmlspecialchars((string)$app['grade_level']) ?></td>
              <td><?= htmlspecialchars((string)$app['parent_name']) ?></td>
              <td><?= htmlspecialchars((string)$app['parent_contact']) ?></td>
              <td><?= htmlspecialchars((string)$app['status']) ?></td>
              <td><?= htmlspecialchars((string)($app['created_at'] ?? '')) ?></td>
              <td>
                <?php if ((string)$app['status'] === 'Submitted'): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Approve this application?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                    <button type="submit" class="btn-success">Approve</button>
                  </form>
                  <form method="post" style="display:inline" onsubmit="return confirm('Reject this application?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                    <textarea name="notes" placeholder="Reason" rows="2" cols="20"></textarea><br>
                    <button type="submit" class="btn-danger">Reject</button>
                  </form>
                <?php elseif ((string)$app['status'] === 'Approved'): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Enroll this student? This will create a student account.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                    <button type="submit" class="btn-primary">Enroll</button>
                  </form>
                <?php else: ?>
                  <span class="muted"><?= htmlspecialchars((string)($app['notes'] ?? '')) ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?page=<?= (int)($page - 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&school_year=<?= urlencode($schoolYear) ?>">Previous</a>
      <?php endif; ?>
      <span>Page <?= (int)$page ?> of <?= (int)$totalPages ?></span>
      <?php if ($page < $totalPages): ?>
        <a href="?page=<?= (int)($page + 1) ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>&school_year=<?= urlencode($schoolYear) ?>">Next</a>
      <?php endif; ?>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>SmartCampus K-12 | Batu-Batu National High School</p>
    </div>
  </footer>

  <input type="hidden" id="csrf-token" value="<?= htmlspecialchars($token) ?>">
  <script src="assets/js/main.js"></script>
</body>
</html>
