<?php
/**
 * School Year Setup (admin): sections, advisers, learning-area classes,
 * school days, and placing enrolled learners into sections.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';
require_once __DIR__ . '/ProgramFunctions/SmartCampusEnrollment.fnc.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (empty($_SESSION['STAFF_ID']) || ($_SESSION['PROFILE'] ?? '') !== 'admin') {
    header('Location: ' . (empty($_SESSION['STAFF_ID']) ? 'view_students_application.php' : 'dashboard.php'));
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function setup_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * "Sampaguita, Rosal" -> ['Sampaguita', 'Rosal']. Letters, digits, spaces,
 * hyphen, apostrophe and period; 1–25 characters; at most 10 per grade.
 */
function setup_parse_sections(string $raw): array
{
    $names = [];
    foreach (explode(',', $raw) as $name) {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            continue;
        }
        if (!preg_match("/^[\\p{L}\\p{N} .'-]{1,25}$/u", $name)) {
            throw new RuntimeException('Section names may use letters, numbers, spaces, hyphens and apostrophes (up to 25 characters): "' . $name . '".', 400);
        }
        $names[mb_strtolower($name)] = $name;
    }
    if (count($names) > 10) {
        throw new RuntimeException('Up to 10 sections per grade at a time.', 400);
    }
    return array_values($names);
}

$conn = db_start(false);
if ($conn === false) {
    http_response_code(503);
    exit('Database unavailable. Please try again shortly.');
}
$context = SmartCampusSchoolContext($conn);

$grades = [];
$result = pg_query_params($conn, 'SELECT id, title FROM school_gradelevels WHERE school_id = $1 ORDER BY sort_order, id', [$context['school_id']]);
while ($result && ($row = pg_fetch_assoc($result))) {
    $grades[(int)$row['id']] = $row['title'];
}

$staff = [];
$result = pg_query_params($conn,
    "SELECT staff_id, TRIM(first_name || ' ' || last_name) AS name, profile FROM staff
     WHERE syear = $1 AND profile IN ('teacher', 'admin')
     AND (schools IS NULL OR schools = '' OR schools LIKE '%,' || $2 || ',%')
     ORDER BY (profile = 'teacher') DESC, last_name, first_name",
    [$context['syear'], (string)$context['school_id']]
);
while ($result && ($row = pg_fetch_assoc($result))) {
    $staff[(int)$row['staff_id']] = $row['name'] . ($row['profile'] === 'admin' ? ' (admin)' : '');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['csrf_token'], (string)($_POST['token'] ?? ''))) {
            throw new RuntimeException('Security token expired. Please reload the page.', 403);
        }
        $plan = [];
        foreach ((array)($_POST['sections'] ?? []) as $grade_id => $raw) {
            $grade_id = (int)$grade_id;
            $names = setup_parse_sections(is_string($raw) ? $raw : '');
            if (!$names || !isset($grades[$grade_id])) {
                continue;
            }
            $adviser = $_POST['adviser'][$grade_id] ?? 0;
            $adviser = is_scalar($adviser) ? (int)$adviser : 0;
            if (!isset($staff[$adviser])) {
                throw new RuntimeException('Choose an adviser for ' . $grades[$grade_id] . '.', 400);
            }
            $plan[$grade_id] = ['sections' => $names, 'adviser' => $adviser];
        }
        $assign_only = isset($_POST['assign_only']);
        if (!$plan && !$assign_only) {
            throw new RuntimeException('Type at least one section name for a grade level.', 400);
        }
        if ($assign_only) {
            // Re-run placement for every grade that already has sections.
            foreach ($grades as $grade_id => $title) {
                if (!isset($plan[$grade_id]) && SmartCampusGradeSections($conn, $context, $grade_id)) {
                    $plan[$grade_id] = ['sections' => [], 'adviser' => 0];
                }
            }
        }
        $_SESSION['setup_summary'] = SmartCampusRunSchoolSetup($conn, $context, $plan, isset($_POST['learning_areas']));
        header('Location: school_setup.php');
        exit;
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    }
}

$summary = $_SESSION['setup_summary'] ?? null;
unset($_SESSION['setup_summary']);

// Current state per grade: sections and learners without a section.
$state = [];
foreach ($grades as $grade_id => $title) {
    $sections = SmartCampusGradeSections($conn, $context, $grade_id);
    $result = pg_query_params($conn,
        'SELECT COUNT(*) FROM student_enrollment se
         WHERE se.syear = $1 AND se.school_id = $2 AND se.grade_id = $3
         AND (se.end_date IS NULL OR se.end_date >= CURRENT_DATE)
         AND NOT EXISTS (SELECT 1 FROM schedule sch WHERE sch.student_id = se.student_id
            AND sch.course_period_id = ANY ($4::int[]) AND (sch.end_date IS NULL OR sch.end_date >= CURRENT_DATE))',
        [$context['syear'], $context['school_id'], $grade_id, '{' . implode(',', array_keys($sections)) . '}']
    );
    $state[$grade_id] = ['sections' => $sections, 'unassigned' => $result ? (int)pg_fetch_result($result, 0, 0) : 0];
}

$days = pg_query_params($conn,
    'SELECT COUNT(*) FROM attendance_calendar ac JOIN attendance_calendars c ON c.calendar_id = ac.calendar_id
     WHERE ac.syear = $1 AND ac.school_id = $2', [$context['syear'], $context['school_id']]);
$school_days = $days ? (int)pg_fetch_result($days, 0, 0) : 0;
$total_unassigned = array_sum(array_column($state, 'unassigned'));
$default_adviser = (int)array_key_first($staff);

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>School Year Setup | SmartCampus K12</title>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap">
<style>
:root { --panel: #0F1D2C; --panel2: #14263A; --border: #1F3247; --muted: #9DB0B4; }
* { box-sizing: border-box; }
body { margin: 0; background: var(--ink-deep); color: var(--sand); font-family: var(--font-body); }
main { max-width: 1000px; margin: 0 auto; padding: 24px 16px 48px; }
a { color: var(--sun-gold); }
.back { display: inline-flex; min-height: 44px; align-items: center; }
.eyebrow { font-family: var(--font-utility); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--sun-gold); }
h1 { font-family: var(--font-display); font-size: 26px; margin: 6px 0 8px; }
.lead { color: var(--muted); margin: 0 0 20px; line-height: 1.6; max-width: 70ch; }
.notice { border: 1px solid var(--border); border-left: 3px solid #7CC9A0; background: var(--panel); border-radius: 10px; padding: 12px 14px; margin-bottom: 16px; }
.notice.error { border-left-color: var(--reef-coral); }
.facts { display: flex; flex-wrap: wrap; gap: 8px 24px; font-size: 13px; color: var(--muted); margin-bottom: 16px; }
.facts b { color: var(--sand); }
.card { background: var(--panel); border: 1px solid var(--border); border-radius: 12px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; min-width: 720px; }
th, td { padding: 12px 14px; border-bottom: 1px solid var(--border); text-align: left; vertical-align: top; font-size: 13px; }
th { background: var(--panel2); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
.chip { display: inline-block; margin: 0 6px 6px 0; padding: 3px 9px; border-radius: 999px; background: rgba(244,180,0,.12); font-size: 12px; }
.muted { color: var(--muted); }
.warn { color: var(--sun-gold); font-weight: 600; }
input[type=text], select { width: 100%; min-height: 44px; background: var(--ink-deep); color: var(--sand); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font: inherit; font-size: 13px; }
input:focus-visible, select:focus-visible, button:focus-visible, a:focus-visible { outline: 2px solid var(--sun-gold); outline-offset: 2px; }
.options { display: flex; flex-wrap: wrap; gap: 12px 24px; align-items: center; margin: 16px 0; font-size: 14px; }
.options label { display: inline-flex; gap: 8px; align-items: center; min-height: 44px; }
.btn { min-height: 44px; padding: 0 18px; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; border: 1px solid var(--border); background: none; color: var(--sand); }
.btn.primary { background: var(--sun-gold); color: var(--ink-deep); border: none; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
</style>
</head>
<body>
<main>
  <a class="back" href="dashboard.php">&larr; Dashboard</a>
  <div class="eyebrow">School Year <?php echo setup_h($context['syear'] . '–' . ($context['syear'] + 1)); ?></div>
  <h1>School Year Setup</h1>
  <p class="lead">Create sections and their advisers. Each section gets an Advisory class (where the adviser takes daily attendance for SF2) and, for Grades 7–10, the DepEd learning-area classes. Enrolled learners without a section are placed alphabetically into the smallest section of their grade, and new enrollments are placed automatically. Running this again only adds what is missing.</p>

  <?php if ($error !== ''): ?>
    <div class="notice error" role="alert"><?php echo setup_h($error); ?></div>
  <?php elseif ($summary): ?>
    <div class="notice" role="status">Done: <?php echo (int)$summary['sections']; ?> new section(s), <?php echo (int)$summary['classes']; ?> learning-area class(es), <?php echo (int)$summary['courses']; ?> course(s), <?php echo (int)$summary['subjects']; ?> subject(s)<?php echo $summary['school_days'] ? ', ' . (int)$summary['school_days'] . ' school days (Mon–Fri)' : ''; ?>; <?php echo (int)$summary['assigned']; ?> learner(s) placed in sections.</div>
  <?php endif; ?>

  <div class="facts">
    <span><b><?php echo $school_days; ?></b> school days in the calendar<?php echo $school_days ? '' : ' (Mon–Fri days will be added)'; ?></span>
    <span><b><?php echo $total_unassigned; ?></b> enrolled learner(s) without a section</span>
  </div>

  <form method="post">
    <input type="hidden" name="token" value="<?php echo setup_h($_SESSION['csrf_token']); ?>">
    <div class="card">
      <table>
        <caption class="sr-only">Sections by grade level</caption>
        <thead><tr><th scope="col">Grade level</th><th scope="col">Current sections</th><th scope="col">Add sections</th><th scope="col">Adviser</th></tr></thead>
        <tbody>
        <?php foreach ($grades as $grade_id => $title): $s = $state[$grade_id]; ?>
          <tr>
            <th scope="row" style="background:none;font-size:14px;text-transform:none;letter-spacing:0;color:var(--sand)"><?php echo setup_h($title); ?>
              <?php if ($s['unassigned']): ?><div class="warn" style="font-size:12px;margin-top:4px"><?php echo $s['unassigned']; ?> without a section</div><?php endif; ?>
            </th>
            <td>
              <?php if (!$s['sections']): ?><span class="muted">None yet</span><?php endif; ?>
              <?php foreach ($s['sections'] as $section): ?>
                <span class="chip"><?php echo setup_h($section['name']); ?> · <?php echo setup_h($section['adviser']); ?> · <?php echo $section['learners']; ?></span>
              <?php endforeach; ?>
            </td>
            <td>
              <label class="sr-only" for="sections-<?php echo $grade_id; ?>">New sections for <?php echo setup_h($title); ?></label>
              <input type="text" id="sections-<?php echo $grade_id; ?>" name="sections[<?php echo $grade_id; ?>]" placeholder="e.g. Sampaguita, Rosal" value="<?php $typed = $_POST['sections'][$grade_id] ?? ''; echo setup_h(is_string($typed) ? $typed : ''); ?>">
            </td>
            <td>
              <label class="sr-only" for="adviser-<?php echo $grade_id; ?>">Adviser for <?php echo setup_h($title); ?></label>
              <select id="adviser-<?php echo $grade_id; ?>" name="adviser[<?php echo $grade_id; ?>]">
                <?php foreach ($staff as $id => $name): ?>
                  <option value="<?php echo $id; ?>"<?php $picked = $_POST['adviser'][$grade_id] ?? $default_adviser; echo (is_scalar($picked) ? (int)$picked : $default_adviser) === $id ? ' selected' : ''; ?>><?php echo setup_h($name); ?></option>
                <?php endforeach; ?>
              </select>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="options">
      <label><input type="checkbox" name="learning_areas" value="1" checked> Also create DepEd learning-area classes for Grades 7–10 (taught by the adviser; reassign teachers later in Scheduling → Courses)</label>
    </div>
    <div class="options">
      <button class="btn primary" type="submit">Create sections &amp; place learners</button>
      <?php if ($total_unassigned): ?><button class="btn" type="submit" name="assign_only" value="1">Only place learners in existing sections</button><?php endif; ?>
    </div>
  </form>
</main>
</body>
</html>
