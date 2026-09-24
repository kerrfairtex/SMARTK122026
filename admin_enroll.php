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
 *              learner_ref_no, documents, status, created_at
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

    if ($action === 'under_review') {
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'under_review' WHERE id = $applicationId";
        pg_query($conn, $sql);
        header('Location: admin_enroll.php?under_review=' . $applicationId);
        exit;
    }

    if ($action === 'approve') {
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'approved' WHERE id = $applicationId";
        pg_query($conn, $sql);
        header('Location: admin_enroll.php?approved=' . $applicationId);
        exit;
    }

    if ($action === 'reject') {
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'rejected' WHERE id = $applicationId";
        pg_query($conn, $sql);
        header('Location: admin_enroll.php?rejected=' . $applicationId);
        exit;
    }

    if ($action === 'enroll') {
        $applicationId = (int)$applicationId;
        if ($applicationId <= 0) {
            die('Invalid application ID.');
        }
        
        $conn = db_conn();
        db_trans_start();
        
        // Lock application row for update and validate
        $appResult = DBQuery("SELECT id, status, learner_name, first_name, middle_name, last_name, name_suffix, grade_level, student_id FROM kerrfairtex.enrollment_applications WHERE id = $applicationId FOR UPDATE");
        $app = db_fetch_row($appResult);
        if (is_array($app)) {
            $app = array_change_key_case($app, CASE_LOWER);
        }
        
        if (!$app) {
            db_trans_rollback();
            die('Application not found.');
        }
        
        // Validate status is approved
        if ($app['status'] !== 'approved') {
            db_trans_rollback();
            die('Application must be approved before enrollment.');
        }
        
        // Check if already enrolled (by status or student_id link)
        if ($app['status'] === 'enrolled' || !empty($app['student_id'])) {
            db_trans_rollback();
            die('Application already enrolled.');
        }
        
        // Resolve grade_id from school_gradelevels - no fallback to 0
        $gradeId = resolveGradeId($app['grade_level'] ?? '');
        if ($gradeId === null) {
            db_trans_rollback();
            die('Invalid grade level: ' . htmlspecialchars($app['grade_level'] ?? ''));
        }
        
        // Read structured name columns directly from application
        $firstName = (string)($app['first_name'] ?? '');
        $lastName = (string)($app['last_name'] ?? '');
        $middleName = (string)($app['middle_name'] ?? '');
        $nameSuffix = (string)($app['name_suffix'] ?? '');
        if (empty($firstName) && empty($lastName)) {
            $learnerName = (string)($app['learner_name'] ?? '');
            if (strpos($learnerName, ' ') !== false) {
                $nameParts = explode(' ', $learnerName, 2);
                $firstName = $firstName ?: ($nameParts[0] ?? '');
                $lastName = $lastName ?: ($nameParts[1] ?? '');
            } else {
                $firstName = $firstName ?: $learnerName;
            }
        }
        
        if (empty($firstName) || empty($lastName)) {
            db_trans_rollback();
            die('Student first name and last name are required.');
        }
        
        // Generate unique username
        $baseUsername = strtolower(substr($firstName, 0, 1) . $lastName);
        $username = $baseUsername;
        $counter = 1;
        while (true) {
            $checkResult = DBQuery("SELECT student_id FROM kerrfairtex.students WHERE username = '" . pg_escape_string($conn, $username) . "'");
            $checkRow = db_fetch_row($checkResult);
            if (!$checkRow) break;
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Generate password
        $plainPassword = substr(bin2hex(random_bytes(8)), 0, 10);
        $hashedPassword = crypt($plainPassword, '$6$' . substr(sha1((string)random_int(999999999, 9999999999)), 0, 16));
        
        $year = (int)explode('-', $schoolYear)[0];
        
        // Create student record using DBQuery (db_insert does not exist)
        $sql = "INSERT INTO kerrfairtex.students (username, password, first_name, last_name, middle_name, name_suffix, created_at)
                VALUES ('" . pg_escape_string($conn, $username) . "',
                        '" . pg_escape_string($conn, $hashedPassword) . "',
                        '" . pg_escape_string($conn, $firstName) . "',
                        '" . pg_escape_string($conn, $lastName) . "',
                        '" . pg_escape_string($conn, $middleName) . "',
                        '" . pg_escape_string($conn, $nameSuffix) . "',
                        NOW())";
        $result = DBQuery($sql);
        if ($result === false) {
            db_trans_rollback();
            die('Failed to create student record.');
        }
        $studentId = DBLastInsertID();
        
        // Create student_enrollment
        $sql = "INSERT INTO kerrfairtex.student_enrollment (syear, school_id, student_id, grade_id, start_date)
                VALUES (" . $year . ", 1, " . (int)$studentId . ", " . (int)$gradeId . ", NOW())";
        $result = DBQuery($sql);
        if ($result === false) {
            db_trans_rollback();
            die('Failed to create student enrollment.');
        }
        
        // Update application status
        $sql = "UPDATE kerrfairtex.enrollment_applications SET status = 'enrolled', student_id = " . (int)$studentId . " WHERE id = $applicationId";
        $result = DBQuery($sql);
        if ($result === false) {
            db_trans_rollback();
            die('Failed to update application status.');
        }
        
        db_trans_commit();
        
        // NOTE: student_id column requires migration:
        // ALTER TABLE kerrfairtex.enrollment_applications ADD COLUMN IF NOT EXISTS student_id INTEGER;
        // After migration, add: student_id = " . (int)$studentId . " to the UPDATE above
        
        header('Location: admin_enroll.php?enrolled=' . $applicationId . '&student_id=' . $studentId);
        exit;
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
    . "enrollment_type, parent_name, parent_contact, status, created_at "
    . "FROM kerrfairtex.enrollment_applications "
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
    $flash = '<div class="alert alert-success">Student enrolled successfully.</div>';
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
        <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
        <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
        <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="enrolled" <?= $statusFilter === 'enrolled' ? 'selected' : '' ?>>Enrolled</option>
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
                <?php if ((string)$app['status'] === 'submitted'): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Mark this application as under review?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="under_review">
                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                    <button type="submit" class="btn-warning">Review</button>
                  </form>
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
                    <button type="submit" class="btn-danger">Reject</button>
                  </form>
                <?php elseif ((string)$app['status'] === 'under_review'): ?>
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
                    <button type="submit" class="btn-danger">Reject</button>
                  </form>
                <?php elseif ((string)$app['status'] === 'approved'): ?>
                  <form method="post" style="display:inline" onsubmit="return confirm('Enroll this student? This will create a student account.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="enroll">
                    <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">
                    <button type="submit" class="btn-primary">Enroll</button>
                  </form>
                <?php else: ?>
                  <span class="muted"></span>
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


/**
 * Resolve grade_id from grade_level text using school_gradelevels table.
 * Returns null if no match found (caller must handle as error).
 */
function resolveGradeId($gradeLevel) {
    if (empty($gradeLevel)) {
        return null;
    }
    
    $conn = db_conn();
    
    // Normalize the grade level value
    $normalized = trim($gradeLevel);
    
    // Try exact match on short_name or title
    $sql = "SELECT id FROM kerrfairtex.school_gradelevels 
            WHERE short_name = $1 OR title = $1 
            ORDER BY id LIMIT 1";
    $result = pg_query_params($conn, $sql, [$normalized]);
    
    if ($result !== false) {
        $row = pg_fetch_assoc($result);
        if ($row) {
            return (int)$row['id'];
        }
    }
    
    // Try pattern matching: "Grade 7" → match "07" or "7th"
    if (preg_match('/Grade\s+(\d+)/i', $normalized, $matches)) {
        $gradeNum = $matches[1];
        // Try zero-padded format: "07"
        $padded = str_pad($gradeNum, 2, '0', STR_PAD_LEFT);
        $sql = "SELECT id FROM kerrfairtex.school_gradelevels 
                WHERE short_name = $1 OR title = $1 
                ORDER BY id LIMIT 1";
        $result = pg_query_params($conn, $sql, [$padded]);
        if ($result !== false) {
            $row = pg_fetch_assoc($result);
            if ($row) {
                return (int)$row['id'];
            }
        }
        
        // Try ordinal format: "7th"
        $ordinal = $gradeNum . 'th';
        $sql = "SELECT id FROM kerrfairtex.school_gradelevels 
                WHERE title ILIKE $1 
                ORDER BY id LIMIT 1";
        $result = pg_query_params($conn, $sql, [$ordinal]);
        if ($result !== false) {
            $row = pg_fetch_assoc($result);
            if ($row) {
                return (int)$row['id'];
            }
        }
    }
    
    // Try Kinder/Kindergarten
    if (stripos($normalized, 'kinder') !== false) {
        $sql = "SELECT id FROM kerrfairtex.school_gradelevels 
                WHERE short_name = 'KG' OR title ILIKE '%kindergarten%' 
                ORDER BY id LIMIT 1";
        $result = pg_query_params($conn, $sql, [$normalized]);
        if ($result !== false) {
            $row = pg_fetch_assoc($result);
            if ($row) {
                return (int)$row['id'];
            }
        }
    }
    
    return null; // No mapping found - caller must treat as error
}
