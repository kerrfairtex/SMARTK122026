<?php
/**
 * SmartCampus Enrollment Admin Dashboard
 *
 * Lets school/SmartCampus personnel:
 *   1. Configure enrollment_periods (school year, opening/closing, grades, status)
 *   2. List applications and advance their status through the pipeline
 *   3. View contact messages
 *
 * AUTH: reuses the RosarioSIS authenticated session (Warehouse.php already
 * starts it). Only an authenticated staff member with PROFILE = 'admin' may
 * access this page. Not logged in -> redirected to the RosarioSIS login.
 * Non-admins see an access-denied message. No separate shared secret.
 */
require_once 'database.inc.php';
require_once 'Warehouse.php';

// Warehouse.php (RosarioSIS) already starts the session; only start if none is active.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_conn() {
    $c = db_start(false);
    if ($c === false) throw new Exception('db');
    return $c;
}

$STAGES = ['Submitted', 'Under Review', 'Documents Needed', 'Verified', 'Approved', 'Enrolled', 'Rejected'];

// Determine RosarioSIS admin identity from the shared authenticated session.
$is_admin = false;
$login_error = '';
try {
    if (function_exists('User') && !empty($_SESSION['STAFF_ID']) && (int) $_SESSION['STAFF_ID'] > 0) {
        $profile = User('PROFILE');
        $is_admin = ($profile === 'admin');
    }
} catch (Throwable $t) {
    // DB unreachable or session not fully initialized — treat as not authenticated.
    $is_admin = false;
}

// Not authenticated at all -> send to the RosarioSIS login (preserving return path).
if (!$is_admin && empty($_SESSION['STAFF_ID'])) {
    header('Location: login.php?modfunc=logout&reason=authenticate');
    exit;
}

if (isset($_GET['logout'])) {
    header('Location: login.php?modfunc=logout');
    exit;
}

$authed = $is_admin;
$msg = '';

if ($authed) {
    try {
        $conn = admin_conn();

        // Save enrollment_periods config
        if (isset($_POST['save_period'])) {
            $sy = pg_escape_string($conn, $_POST['school_year']);
            $opens = $_POST['enrollment_opens'] ? "'" . pg_escape_string($conn, $_POST['enrollment_opens']) . "'" : 'NULL';
            $closes = $_POST['enrollment_closes'] ? "'" . pg_escape_string($conn, $_POST['enrollment_closes']) . "'" : 'NULL';
            $begins = $_POST['classes_begin'] ? "'" . pg_escape_string($conn, $_POST['classes_begin']) . "'" : 'NULL';
            $grades = pg_escape_string($conn, $_POST['grade_levels']);
            $status = pg_escape_string($conn, $_POST['status']);
            pg_query($conn, "UPDATE enrollment_periods SET school_year='$sy', enrollment_opens=$opens, enrollment_closes=$closes, classes_begin=$begins, grade_levels='$grades', status='$status', updated_at=NOW() WHERE id=(SELECT id FROM enrollment_periods ORDER BY updated_at DESC LIMIT 1)");
            $msg = 'Enrollment period saved.';
        }

        // Advance / set application status
        if (isset($_POST['set_status'])) {
            $ref = pg_escape_string($conn, $_POST['ref']);
            $new = pg_escape_string($conn, $_POST['new_status']);
            pg_query($conn, "UPDATE enrollment_applications SET status='$new' WHERE ref='$ref'");
            $msg = 'Status updated for ' . htmlspecialchars($ref) . '.';
        }

        // Load data
        $period = pg_fetch_assoc(pg_query($conn, "SELECT * FROM enrollment_periods ORDER BY updated_at DESC LIMIT 1"));
        $apps = pg_query($conn, "SELECT ref, learner_name, grade_level, enrollment_type, status, created_at FROM enrollment_applications ORDER BY created_at DESC LIMIT 50");
        $msgs = pg_query($conn, "SELECT id, full_name, email, concern, message, created_at FROM contact_messages ORDER BY created_at DESC LIMIT 25");

    } catch (Throwable $t) {
        $msg = 'Database error: ' . htmlspecialchars($t->getMessage());
    }
}

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Enrollment Admin — SmartCampus K-12</title>
<style>
  body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;color:#1e293b;margin:0;padding:2rem;}
  .box{background:#ffffff;border:1px solid rgba(0,0,0,.08);border-radius:8px;padding:1.5rem;max-width:1000px;margin:0 auto 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.06);}
  h1,h2{color:#0f172a;}
  label{display:block;margin:0.5rem 0 0.2rem;color:#475569;font-size:0.85rem;}
  input,select,textarea{width:100%;padding:0.5rem;border-radius:4px;border:1px solid #cbd5e1;background:#ffffff;color:#1e293b;}
  button{background:#0e7490;color:#fff;border:none;padding:0.6rem 1.2rem;border-radius:4px;cursor:pointer;font-weight:600;margin-top:0.75rem;}
  table{width:100%;border-collapse:collapse;font-size:0.85rem;}
  th,td{text-align:left;padding:0.5rem;border-bottom:1px solid rgba(0,0,0,.08);}
  th{color:#0f172a;}
  .msg{background:#0e7490;color:#fff;padding:0.6rem 1rem;border-radius:4px;margin-bottom:1rem;}
  .err{background:#b91c1c;color:#fff;padding:0.4rem 0.8rem;border-radius:4px;}
  a{color:#0e7490;}
  .grid2{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0.75rem;}
</style>
</head>
<body>
<?php if (!$authed): ?>
  <div class="box" style="max-width:480px;">
    <h1>Enrollment Admin</h1>
    <p style="color:#475569;">Access restricted. This dashboard is available to authenticated RosarioSIS administrators only.</p>
    <p style="font-size:0.85rem;color:#64748b;margin-top:1rem;">
      If you are an administrator, please <a href="login.php">sign in to the SmartCampus Portal</a> first, then return to this page.
      <br><br>
      <a href="login.php?modfunc=logout">Log out</a>
    </p>
  </div>
<?php else: ?>
  <div class="box">
    <h1>Enrollment Admin <a style="font-size:0.8rem;float:right;" href="?logout=1">Logout</a></h1>
    <?php if ($msg): ?><div class="msg"><?php echo $msg; ?></div><?php endif; ?>
  </div>

  <div class="box">
    <h2>Enrollment Period Configuration</h2>
    <form method="post">
      <div class="grid2">
        <div><label>School Year</label><input name="school_year" value="<?php echo esc($period['school_year'] ?? ''); ?>"></div>
        <div><label>Enrollment Opens</label><input type="date" name="enrollment_opens" value="<?php echo esc($period['enrollment_opens'] ?? ''); ?>"></div>
        <div><label>Enrollment Closes</label><input type="date" name="enrollment_closes" value="<?php echo esc($period['enrollment_closes'] ?? ''); ?>"></div>
        <div><label>Classes Begin</label><input type="date" name="classes_begin" value="<?php echo esc($period['classes_begin'] ?? ''); ?>"></div>
        <div><label>Grade Levels</label><input name="grade_levels" value="<?php echo esc($period['grade_levels'] ?? ''); ?>"></div>
        <div><label>Status</label>
          <select name="status">
            <?php foreach (['Open','Closed','Paused'] as $s): ?><option <?php echo ($period['status']??'')===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?>
          </select>
        </div>
      </div>
      <button type="submit" name="save_period">Save Period</button>
    </form>
  </div>

  <div class="box">
    <h2>Applications (<?php echo pg_num_rows($apps ?? 0); ?>)</h2>
    <table>
      <thead><tr><th>Ref</th><th>Learner</th><th>Grade</th><th>Type</th><th>Status</th><th>Set status</th></tr></thead>
      <tbody>
      <?php while ($a = pg_fetch_assoc($apps)): ?>
        <tr>
          <td><?php echo esc($a['ref']); ?></td>
          <td><?php echo esc($a['learner_name']); ?></td>
          <td><?php echo esc($a['grade_level']); ?></td>
          <td><?php echo esc($a['enrollment_type']); ?></td>
          <td><strong><?php echo esc($a['status']); ?></strong></td>
          <td>
            <form method="post" style="display:flex;gap:0.3rem;">
              <input type="hidden" name="ref" value="<?php echo esc($a['ref']); ?>">
              <select name="new_status">
                <?php foreach ($STAGES as $s): ?><option <?php echo $a['status']===$s?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?>
              </select>
              <button type="submit" name="set_status" style="margin-top:0;padding:0.4rem 0.7rem;">Set</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <div class="box">
    <h2>Contact Messages (<?php echo pg_num_rows($msgs ?? 0); ?>)</h2>
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Concern</th><th>Message</th><th>Received</th></tr></thead>
      <tbody>
      <?php while ($m = pg_fetch_assoc($msgs)): ?>
        <tr>
          <td><?php echo esc($m['full_name']); ?></td>
          <td><?php echo esc($m['email']); ?></td>
          <td><?php echo esc($m['concern']); ?></td>
          <td><?php echo esc($m['message']); ?></td>
          <td><?php echo esc($m['created_at']); ?></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</body>
</html>
