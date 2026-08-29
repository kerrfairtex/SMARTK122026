<?php
/**
 * Admin Snapshot (Tier 3 + PR-3, read-only dashboard)
 *
 * Auth: shared-secret token in ?token=<hex>. Reuses the ABOUT_EDIT_TOKEN
 * stored in the school config table. If the token is missing or invalid the
 * page renders an empty placeholder so a casual visitor doesn't even know
 * the URL exists.
 *
 * Reads from existing tables (no writes). Three queries, all aggregations.
 *
 * Reversible: delete this file. No DB or other-file changes.
 */

require_once __DIR__ . '/../database.inc.php';
// BUGFIX: Warehouse.php loads its helper functions via
//     $functions = glob( 'functions/*.php', GLOB_NOSORT );
//     foreach ( $functions as $function ) { require_once $function; }
// When this admin file lives in admin/, the working directory is
// admin/, where there is no functions/ directory, so the glob
// returns empty. That means do_action() (functions/Actions.php),
// ErrorSendEmail (functions/Misc.fnc.php), and ~30 other
// action/hook shims are never loaded, and Warehouse.php's init
// crashes on its first DBQuery and on register_shutdown_function.
// chdir to the repo root before pulling Warehouse.php so the
// relative globs find their target files.
chdir( __DIR__ . '/..' );
require_once __DIR__ . '/../functions/Actions.php'; // belt-and-braces: force the action shim
require_once __DIR__ . '/../Warehouse.php';

function admin_db() {
    static $c = null;
    if ($c === null) {
        $c = db_start(false);
        if ($c === false) throw new Exception('db');
    }
    return $c;
}

function admin_check_token() {
    if (!isset($_GET['token'])) return false;
    $provided = (string)$_GET['token'];
    try {
        $conn = admin_db();
        $r = @pg_query($conn, "SELECT config_value FROM config WHERE title = 'ABOUT_EDIT_TOKEN' LIMIT 1");
        $row = $r ? pg_fetch_assoc($r) : null;
        if (!$row) return false;
        $stored = (string)$row['config_value'];
        return $stored !== '' && hash_equals($stored, $provided);
    } catch (Throwable $t) { return false; }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!admin_check_token()) {
    http_response_code(200);
    echo '<!doctype html><meta charset="utf-8"><title>Admin</title>'
       . '<style>body{font-family:system-ui,sans-serif;max-width:640px;margin:3rem auto;padding:0 1rem;color:#0f172a;}</style>'
       . '<h1>No data</h1><p>Auth required.</p>';
    exit;
}

try {
    $conn = admin_db();
} catch (Throwable $t) {
    http_response_code(503);
    echo 'DB unavailable';
    exit;
}

$engagement_rows = [];
$contact_rows = [];
$apps_total = 0;
$apps_by_status = [];
$apps_by_type = [];
$last_app = null;
$last_contact = null;
$contact_total = 0;
$contact_viber = 0;
$contact_whatsapp = 0;
$gen = function () { return date('Y-m-d H:i:s') . ' UTC'; };
$now = $gen();

try {
    $r = @pg_query($conn, "SELECT status, count(*) AS n, max(created_at) AS last_seen
                             FROM kerrfairtex.access_log
                             WHERE status LIKE 'scroll:%' AND created_at > now() - interval '7 days'
                             GROUP BY status
                             ORDER BY n DESC");
    if ($r) { while ($row = pg_fetch_assoc($r)) $engagement_rows[] = $row; }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT count(*) AS n, max(created_at) AS last FROM kerrfairtex.contact_messages");
    if ($r) {
        $row = pg_fetch_assoc($r);
        $contact_total = (int)($row['n'] ?? 0);
        $last_contact = $row['last'] ?? null;
    }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT count(*) AS n, max(created_at) AS last FROM kerrfairtex.access_log WHERE status LIKE 'contact_click:%' AND created_at > now() - interval '7 days'");
    if ($r) {
        $row = pg_fetch_assoc($r);
        $contact_viber = 0; $contact_whatsapp = 0;
    }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT status, count(*) AS n FROM kerrfairtex.access_log
                             WHERE status LIKE 'contact_click:%' AND created_at > now() - interval '7 days'
                             GROUP BY status");
    if ($r) {
        while ($row = pg_fetch_assoc($r)) {
            if (strpos($row['status'], 'contact_click:viber') !== false) $contact_viber += (int)$row['n'];
            if (strpos($row['status'], 'contact_click:whatsapp') !== false) $contact_whatsapp += (int)$row['n'];
        }
    }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT count(*) AS n FROM kerrfairtex.enrollment_applications");
    if ($r) { $apps_total = (int)(pg_fetch_assoc($r)['n'] ?? 0); }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT status, count(*) AS n FROM kerrfairtex.enrollment_applications
                             WHERE status IS NOT NULL AND status <> ''
                             GROUP BY status ORDER BY n DESC");
    if ($r) { while ($row = pg_fetch_assoc($r)) $apps_by_status[$row['status']] = (int)$row['n']; }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT enrollment_type, count(*) AS n FROM kerrfairtex.enrollment_applications
                             WHERE enrollment_type IS NOT NULL AND enrollment_type <> ''
                             GROUP BY enrollment_type ORDER BY n DESC");
    if ($r) { while ($row = pg_fetch_assoc($r)) $apps_by_type[$row['enrollment_type']] = (int)$row['n']; }
} catch (Throwable $t) {}

try {
    $r = @pg_query($conn, "SELECT ref, learner_name, grade_level, enrollment_type, status, created_at
                             FROM kerrfairtex.enrollment_applications
                             ORDER BY created_at DESC LIMIT 1");
    if ($r) { $last_app = pg_fetch_assoc($r) ?: null; }
} catch (Throwable $t) {}

$engagement_total = 0;
$engagement_last = null;
foreach ($engagement_rows as $r) { $engagement_total += (int)$r['n']; if (!$engagement_last || ($r['last_seen'] && $r['last_seen'] > $engagement_last)) $engagement_last = $r['last_seen']; }

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Snapshot &mdash; Batu-Batu National Integrated High School</title>
<meta name="robots" content="noindex,nofollow">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { font-family: system-ui, -apple-system, sans-serif; max-width: 920px; margin: 2rem auto; padding: 0 1.25rem; color: #0f172a; background: #f8fafc; }
h1 { font-size: 1.4rem; margin: 0 0 0.25rem; }
h2 { font-size: 1.1rem; margin: 1.75rem 0 0.5rem; padding-bottom: 0.3rem; border-bottom: 1px solid #cbd5e1; }
.meta { color: #64748b; font-size: 0.85rem; margin-bottom: 1rem; }
.card { background: #fff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 1rem 1.25rem; margin-bottom: 0.75rem; }
table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
th, td { text-align: left; padding: 0.4rem 0.5rem; border-bottom: 1px solid #e2e8f0; }
th { color: #475569; font-weight: 600; }
td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
.links { background: #f1f5f9; border-radius: 6px; padding: 0.75rem 1rem; font-size: 0.85rem; }
.links a { color: #1d4ed8; text-decoration: none; margin-right: 1rem; }
.links a:hover { text-decoration: underline; }
footer { color: #94a3b8; font-size: 0.8rem; margin-top: 2rem; text-align: center; }
</style>
</head>
<body>
<h1>Batu-Batu National Integrated High School &mdash; Admin Snapshot</h1>
<p class="meta">Generated: <?php echo h($now); ?></p>

<h2>Engagement signals &mdash; last 7 days</h2>
<?php if (empty($engagement_rows)): ?>
    <p class="meta">No engagement data yet. Scroll events from the public landing page will appear here.</p>
<?php else: ?>
    <div class="card">
        <table>
            <thead><tr><th>Section</th><th class="num">Reads</th><th>Last seen</th></tr></thead>
            <tbody>
            <?php foreach ($engagement_rows as $row):
                $sec = (string)$row['status'];
                $sec = preg_replace('/^scroll:/', '', $sec);
                if (preg_match('/^\d+$/', $sec)) continue;
                if (strpos($sec, ':') !== false) $sec = preg_replace('/:\d+$/', '', $sec);
            ?>
                <tr><td><?php echo h($sec); ?></td><td class="num"><?php echo (int)$row['n']; ?></td><td><?php echo h($row['last_seen']); ?></td></tr>
            <?php endforeach; ?>
                <tr><th>TOTAL</th><th class="num"><?php echo $engagement_total; ?></th><th><?php echo h($engagement_last); ?></th></tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2>Contact form &mdash; last 7 days</h2>
<div class="card">
    <table>
        <tr><th>Total messages (all time)</th><td class="num"><?php echo $contact_total; ?></td></tr>
        <tr><th>Last submission</th><td><?php echo h($last_contact ?: '—'); ?></td></tr>
        <tr><th>Viber clicks (PR-3 floating button, 7d)</th><td class="num"><?php echo $contact_viber; ?></td></tr>
        <tr><th>WhatsApp clicks (PR-3 floating button, 7d)</th><td class="num"><?php echo $contact_whatsapp; ?></td></tr>
    </table>
</div>

<h2>Enrollment applications</h2>
<div class="card">
    <p><strong>Total submitted:</strong> <?php echo $apps_total; ?></p>
    <p><strong>Last submission:</strong>
        <?php if ($last_app): ?>
            <?php echo h($last_app['ref']); ?> &mdash; <?php echo h($last_app['learner_name'] ?: '(no name)'); ?>
            (<?php echo h($last_app['grade_level'] ?? ''); ?>, <?php echo h($last_app['enrollment_type'] ?? ''); ?>, <?php echo h($last_app['status'] ?? ''); ?>)
            at <?php echo h($last_app['created_at']); ?>
        <?php else: ?>
            none yet
        <?php endif; ?>
    </p>
    <?php if (!empty($apps_by_status)): ?>
        <p><strong>By status:</strong></p>
        <table>
            <?php foreach ($apps_by_status as $status => $n): ?>
                <tr><td><?php echo h($status); ?></td><td class="num"><?php echo $n; ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <?php if (!empty($apps_by_type)): ?>
        <p style="margin-top:0.75rem;"><strong>By enrollment type:</strong></p>
        <table>
            <?php foreach ($apps_by_type as $type => $n): ?>
                <tr><td><?php echo h($type); ?></td><td class="num"><?php echo $n; ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<h2>Direct links</h2>
<div class="links">
    <a href="/about_edit.php?token=<?php echo h($_GET['token']); ?>">Edit mission / vision</a>
    <a href="/enroll_api.php?action=config" target="_blank">Enrollment config (JSON)</a>
    <a href="/contact_api.php" target="_blank">Contact endpoint (info)</a>
    <a href="/engagement.php" target="_blank">Engagement endpoint (info)</a>
</div>

<footer>Read-only snapshot &middot; No writes from this page &middot; 50 lines of PHP, zero JS</footer>
</body>
</html>
