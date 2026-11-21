<?php
/**
 * School Form 2 (SF2) — Daily Attendance Report of Learners, per section
 * and month. Printable and CSV.
 *
 * Marks come from the attendance the adviser takes in the section's
 * Advisory class (RosarioSIS attendance_period): "x" absent, "T" tardy,
 * blank present. Learners: males first, then females, alphabetical.
 * Teachers can open only sections they advise.
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

$profile = $_SESSION['PROFILE'] ?? '';
if (empty($_SESSION['STAFF_ID']) || !in_array($profile, ['admin', 'teacher'], true)) {
    header('Location: view_students_application.php');
    exit;
}

const SF2_SCHOOL_NAME = 'Batu-Batu National High School';
const SF2_SCHOOL_ID = '305053';

function sf2_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$conn = db_start(false);
if ($conn === false) {
    http_response_code(503);
    exit('Database unavailable. Please try again shortly.');
}
$context = SmartCampusSchoolContext($conn);
$syear = $context['syear'];
$school_year = $syear . '-' . ($syear + 1);

// Sections the user may open: all for admins, their own advisory for teachers.
$sections = [];
$result = pg_query_params($conn, 'SELECT id, title FROM school_gradelevels WHERE school_id = $1 ORDER BY sort_order, id', [$context['school_id']]);
while ($result && ($row = pg_fetch_assoc($result))) {
    foreach (SmartCampusGradeSections($conn, $context, (int)$row['id']) as $cp_id => $section) {
        if ($profile === 'admin' || $section['teacher_id'] === (int)$_SESSION['STAFF_ID']) {
            $sections[$cp_id] = $section + ['grade' => $row['title']];
        }
    }
}

$section_id = (int)($_GET['section_id'] ?? 0);
$section = $sections[$section_id] ?? null;

// Month within the school year; default: this month.
$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month)) {
    $month = date('Y-m');
}
$first = new DateTimeImmutable($month . '-01');
$last = $first->modify('last day of this month');
$months = [];
for ($m = new DateTimeImmutable($syear . '-06-01'); $m <= new DateTimeImmutable(($syear + 1) . '-05-01'); $m = $m->modify('+1 month')) {
    $months[$m->format('Y-m')] = $m->format('F Y');
}

$days = [];
$learners = [];

if ($section) {
    // School days from the section's calendar; Mon–Fri if the calendar is empty.
    $result = pg_query_params($conn,
        'SELECT ac.school_date FROM attendance_calendar ac
         JOIN course_periods cp ON cp.calendar_id = ac.calendar_id
         WHERE cp.course_period_id = $1 AND ac.school_date BETWEEN $2 AND $3
         ORDER BY ac.school_date',
        [$section_id, $first->format('Y-m-d'), $last->format('Y-m-d')]
    );
    $days = $result ? pg_fetch_all_columns($result, 0) : [];
    if (!$days) {
        for ($d = $first; $d <= $last; $d = $d->modify('+1 day')) {
            if ((int)$d->format('N') <= 5) {
                $days[] = $d->format('Y-m-d');
            }
        }
    }

    $sex_column = SmartCampusStudentFieldColumns($conn)['gender'];
    $sex_select = $sex_column ? 's.' . pg_escape_identifier($conn, $sex_column) : 'NULL';

    // Learners in the section at any time during the month.
    $result = pg_query_params($conn,
        'SELECT s.student_id, s.last_name, s.first_name, s.middle_name,
            COALESCE(' . $sex_select . ', (SELECT sex FROM enrollment_applications ea
                WHERE ea.student_id = s.student_id ORDER BY ea.id DESC LIMIT 1)) AS sex
         FROM schedule sch JOIN students s ON s.student_id = sch.student_id
         WHERE sch.course_period_id = $1 AND sch.start_date <= $3
         AND (sch.end_date IS NULL OR sch.end_date >= $2)
         GROUP BY s.student_id',
        [$section_id, $first->format('Y-m-d'), $last->format('Y-m-d')]
    );
    while ($result && ($row = pg_fetch_assoc($result))) {
        $learners[(int)$row['student_id']] = [
            'name' => trim($row['last_name'] . ', ' . $row['first_name'] . ($row['middle_name'] ? ' ' . $row['middle_name'] : '')),
            'sex' => $row['sex'] === 'Male' ? 'M' : ($row['sex'] === 'Female' ? 'F' : ''),
            'marks' => [],
        ];
    }

    // Absent = state code A; tardy = code "T" (present but late).
    $result = pg_query_params($conn,
        "SELECT ap.student_id, ap.school_date,
            CASE WHEN ac.state_code = 'A' THEN 'x' WHEN UPPER(ac.short_name) = 'T' THEN 'T' ELSE '' END AS mark
         FROM attendance_period ap JOIN attendance_codes ac ON ac.id = ap.attendance_code
         WHERE ap.course_period_id = $1 AND ap.school_date BETWEEN $2 AND $3",
        [$section_id, $first->format('Y-m-d'), $last->format('Y-m-d')]
    );
    while ($result && ($row = pg_fetch_assoc($result))) {
        if (isset($learners[(int)$row['student_id']]) && $row['mark'] !== '') {
            $learners[(int)$row['student_id']]['marks'][$row['school_date']] = $row['mark'];
        }
    }

    $order = ['M' => 0, 'F' => 1, '' => 2];
    uasort($learners, static fn($a, $b) => [$order[$a['sex']], $a['name']] <=> [$order[$b['sex']], $b['name']]);
}

// Daily present counts by sex, and per-learner totals.
$present = [];
foreach ($days as $day) {
    $present[$day] = ['M' => 0, 'F' => 0, 'T' => 0];
    foreach ($learners as $l) {
        if (($l['marks'][$day] ?? '') !== 'x') {
            $present[$day]['T']++;
            if (isset($present[$day][$l['sex']]) && $l['sex'] !== '') {
                $present[$day][$l['sex']]++;
            }
        }
    }
}
$totals = static function (array $l): array {
    $counts = array_count_values($l['marks']);
    return [$counts['x'] ?? 0, $counts['T'] ?? 0];
};

if (($_GET['format'] ?? '') === 'csv' && $section) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SF2_' . preg_replace('/[^A-Za-z0-9]+/', '_', $section['grade'] . ' ' . $section['name']) . '_' . $month . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['School Form 2 (SF2) Daily Attendance Report of Learners', SF2_SCHOOL_NAME, 'School ID ' . SF2_SCHOOL_ID, 'SY ' . $school_year, $first->format('F Y'), $section['grade'], 'Section ' . $section['name']], ',', '"', '');
    fputcsv($out, array_merge(['Learner', 'Sex'], array_map(static fn($d) => date('D j', strtotime($d)), $days), ['Absent', 'Tardy']), ',', '"', '');
    foreach ($learners as $l) {
        [$absent, $tardy] = $totals($l);
        fputcsv($out, array_merge([$l['name'], $l['sex']], array_map(static fn($d) => $l['marks'][$d] ?? '', $days), [$absent, $tardy]), ',', '"', '');
    }
    fputcsv($out, array_merge(['Present (total)', ''], array_map(static fn($d) => $present[$d]['T'], $days)), ',', '"', '');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>SF2 Daily Attendance<?php echo $section ? ' — ' . sf2_h($section['grade'] . ' ' . $section['name']) : ''; ?> | SmartCampus K12</title>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/css/reports.css">
</head>
<body>
<form class="toolbar" method="get">
  <a class="btn" href="dashboard.php">&larr; Dashboard</a>
  <label for="section_id">Section</label>
  <select id="section_id" name="section_id" onchange="this.form.submit()">
    <option value="0">Choose a section&hellip;</option>
    <?php foreach ($sections as $id => $s): ?>
      <option value="<?php echo $id; ?>"<?php echo $id === $section_id ? ' selected' : ''; ?>><?php echo sf2_h($s['grade'] . ' — ' . $s['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <label for="month">Month</label>
  <select id="month" name="month" onchange="this.form.submit()">
    <?php foreach ($months as $value => $label): ?>
      <option value="<?php echo $value; ?>"<?php echo $value === $month ? ' selected' : ''; ?>><?php echo sf2_h($label); ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button class="btn" type="submit">Show</button></noscript>
  <span class="spacer"></span>
  <?php if ($section): ?>
    <a class="btn" href="sf2.php?section_id=<?php echo $section_id; ?>&amp;month=<?php echo $month; ?>&amp;format=csv">Download CSV (Excel)</a>
    <button class="btn primary" type="button" onclick="window.print()">Print / Save as PDF</button>
  <?php endif; ?>
</form>

<?php if (!$sections): ?>
  <p class="empty"><?php echo $profile === 'admin'
      ? 'No sections yet. Create them in <a href="school_setup.php" style="color:var(--sun-gold)">School Year Setup</a>.'
      : 'You are not the adviser of a section yet.'; ?></p>
<?php elseif (!$section): ?>
  <p class="empty">Choose a section and month to see its School Form 2 (SF2) Daily Attendance Report.</p>
<?php else: ?>
  <div class="sheet">
    <h1>School Form 2 (SF2) Daily Attendance Report of Learners</h1>
    <p class="sub">(This replaces Form 1, Form 2 &amp; STS Form 4 - Absenteeism and Dropout Profile)</p>
    <div class="meta">
      <span><b>School ID:</b> <?php echo sf2_h(SF2_SCHOOL_ID); ?></span>
      <span><b>School Year:</b> <?php echo sf2_h($school_year); ?></span>
      <span><b>Report for the Month of:</b> <?php echo sf2_h($first->format('F Y')); ?></span>
      <span><b>Name of School:</b> <?php echo sf2_h(SF2_SCHOOL_NAME); ?></span>
      <span><b>Grade Level:</b> <?php echo sf2_h($section['grade']); ?></span>
      <span><b>Section:</b> <?php echo sf2_h($section['name']); ?></span>
      <span><b>Adviser:</b> <?php echo sf2_h($section['adviser']); ?></span>
    </div>
    <table class="sf2">
      <caption class="sr-only">Daily attendance for <?php echo sf2_h($section['grade'] . ' ' . $section['name'] . ', ' . $first->format('F Y')); ?></caption>
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col">Learner's Name (Last, First, Middle)</th>
          <?php foreach ($days as $day): ?>
            <th scope="col" class="day"><?php echo date('j', strtotime($day)); ?><br><small><?php echo substr(date('D', strtotime($day)), 0, 2); ?></small></th>
          <?php endforeach; ?>
          <th scope="col" class="day">Absent</th>
          <th scope="col" class="day">Tardy</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$learners): ?>
        <tr><td colspan="<?php echo count($days) + 4; ?>" class="c">No learners in this section for <?php echo sf2_h($first->format('F Y')); ?>.</td></tr>
      <?php endif; ?>
      <?php $n = 0; $current = null; foreach ($learners as $l):
          if ($l['sex'] !== $current): $current = $l['sex']; ?>
        <tr class="group"><th colspan="<?php echo count($days) + 4; ?>"><?php echo $current === 'M' ? 'MALE' : ($current === 'F' ? 'FEMALE' : 'SEX NOT RECORDED'); ?></th></tr>
      <?php endif; $n++; [$absent, $tardy] = $totals($l); ?>
        <tr>
          <td class="c"><?php echo $n; ?></td>
          <td><?php echo sf2_h($l['name']); ?></td>
          <?php foreach ($days as $day): ?>
            <td class="mark"><?php echo sf2_h($l['marks'][$day] ?? ''); ?></td>
          <?php endforeach; ?>
          <td class="c"><?php echo $absent; ?></td>
          <td class="c"><?php echo $tardy; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <?php if ($learners): ?>
      <tfoot>
        <?php foreach (['M' => 'Male', 'F' => 'Female', 'T' => 'Combined'] as $key => $label): ?>
          <tr>
            <th scope="row" colspan="2"><?php echo $label; ?> — present</th>
            <?php foreach ($days as $day): ?><td class="mark"><?php echo $present[$day][$key]; ?></td><?php endforeach; ?>
            <td></td><td></td>
          </tr>
        <?php endforeach; ?>
      </tfoot>
      <?php endif; ?>
    </table>
    <p class="legend">Legend: blank = present, <b>x</b> = absent, <b>T</b> = tardy. Marks come from the attendance taken in the section's Advisory class.</p>
  </div>
<?php endif; ?>
</body>
</html>
