<?php
/**
 * School Form 1 (SF1) — School Register, printable and CSV.
 *
 * One grade level of the current school year: males first, then females,
 * each alphabetical, with totals. Columns the system does not collect
 * (mother tongue, IP, religion, parents) are left blank to complete by hand.
 * Teachers only see learners scheduled in their classes.
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

const SF1_SCHOOL_NAME = 'Batu-Batu National High School';
const SF1_SCHOOL_ID = '305053';

function sf1_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * DepEd SF1 reports age as of the first Friday of June of the school year.
 */
function sf1_age(?string $birth_date, int $syear): string
{
    if (!$birth_date || !strtotime($birth_date)) {
        return '';
    }
    $june1 = new DateTimeImmutable($syear . '-06-01');
    $first_friday = $june1->format('N') === '5' ? $june1 : $june1->modify('next friday');
    return (string)(new DateTimeImmutable($birth_date))->diff($first_friday)->y;
}

$conn = db_start(false);
if ($conn === false) {
    http_response_code(503);
    exit('Database unavailable. Please try again shortly.');
}

try {
    $context = SmartCampusSchoolContext($conn);
} catch (RuntimeException $e) {
    http_response_code(500);
    exit(sf1_h($e->getMessage()));
}
$syear = $context['syear'];
$school_id = $context['school_id'];

$grades = [];
$result = pg_query_params($conn, 'SELECT id, title FROM school_gradelevels WHERE school_id = $1 ORDER BY sort_order, id', [$school_id]);
while ($result && ($row = pg_fetch_assoc($result))) {
    $grades[(int)$row['id']] = $row['title'];
}

$grade_id = (int)($_GET['grade_id'] ?? 0);
$sections = isset($grades[$grade_id]) ? SmartCampusGradeSections($conn, $context, $grade_id) : [];
$section_id = (int)($_GET['section_id'] ?? 0);
$section_name = $sections[$section_id]['name'] ?? '';
if ($section_name === '') {
    $section_id = 0;
}
$learners = [];

if (isset($grades[$grade_id])) {
    $columns = SmartCampusStudentFieldColumns($conn);
    $col = static fn(?string $c, string $alias) => $c ? 's.' . pg_escape_identifier($conn, $c) . ' AS ' . $alias : 'NULL AS ' . $alias;

    $params = [$syear, $school_id, $grade_id];
    $teacher_filter = '';
    if ($profile === 'teacher') {
        $params[] = (int)$_SESSION['STAFF_ID'];
        $teacher_filter = 'AND EXISTS (
            SELECT 1 FROM schedule sch
            JOIN course_periods cp ON cp.course_period_id = sch.course_period_id
            WHERE sch.student_id = s.student_id AND sch.syear = $1
            AND (cp.teacher_id = $4 OR cp.secondary_teacher_id = $4)
            AND (sch.end_date IS NULL OR sch.end_date >= CURRENT_DATE))';
    }
    if ($section_id) {
        $params[] = $section_id;
        $teacher_filter .= ' AND EXISTS (SELECT 1 FROM schedule sec WHERE sec.student_id = s.student_id
            AND sec.course_period_id = $' . count($params) . '
            AND (sec.end_date IS NULL OR sec.end_date >= CURRENT_DATE))';
    }

    // Student fields first, falling back to the application they came from.
    $result = pg_query_params($conn,
        'SELECT s.student_id, s.last_name, s.first_name, s.middle_name, s.name_suffix,
            ' . $col($columns['lrn'], 'lrn') . ',
            ' . $col($columns['gender'], 'gender') . ',
            ' . $col($columns['birthdate'], 'birthdate') . ',
            ea.learner_ref_no AS app_lrn, ea.sex AS app_sex, ea.birth_date AS app_birth_date,
            (SELECT a.address FROM students_join_address sja JOIN address a ON a.address_id = sja.address_id
                WHERE sja.student_id = s.student_id ORDER BY (sja.residence = \'Y\') DESC NULLS LAST, sja.id LIMIT 1) AS address,
            g.guardian, g.relation, g.phone
        FROM students s
        JOIN student_enrollment se ON se.id = (
            SELECT id FROM student_enrollment
            WHERE student_id = s.student_id AND syear = $1 AND school_id = $2
            AND (end_date IS NULL OR end_date >= CURRENT_DATE)
            ORDER BY start_date DESC NULLS LAST, id DESC LIMIT 1)
        LEFT JOIN LATERAL (
            SELECT learner_ref_no, sex, birth_date FROM enrollment_applications
            WHERE student_id = s.student_id ORDER BY id DESC LIMIT 1) ea ON true
        LEFT JOIN LATERAL (
            SELECT TRIM(p.first_name || \' \' || p.last_name) AS guardian, sjp.student_relation AS relation,
                (SELECT value FROM people_join_contacts c WHERE c.person_id = p.person_id AND c.title = \'Phone\' ORDER BY c.id LIMIT 1) AS phone
            FROM students_join_people sjp JOIN people p ON p.person_id = sjp.person_id
            WHERE sjp.student_id = s.student_id
            ORDER BY (sjp.custody = \'Y\') DESC NULLS LAST, sjp.id LIMIT 1) g ON true
        WHERE se.grade_id = $3 ' . $teacher_filter . '
        ORDER BY s.last_name, s.first_name',
        $params
    );

    while ($result && ($row = pg_fetch_assoc($result))) {
        $sex = $row['gender'] ?: $row['app_sex'];
        $birth = $row['birthdate'] ?: $row['app_birth_date'];
        $learners[] = [
            'lrn' => $row['lrn'] ?: (string)$row['app_lrn'],
            'name' => trim($row['last_name'] . ', ' . $row['first_name'] . ' ' . (string)$row['name_suffix'])
                . ($row['middle_name'] ? ', ' . $row['middle_name'] : ''),
            'sex' => $sex === 'Male' ? 'M' : ($sex === 'Female' ? 'F' : ''),
            'birth_date' => $birth ? date('m/d/Y', strtotime($birth)) : '',
            'age' => sf1_age($birth, $syear),
            'address' => (string)$row['address'],
            'guardian' => (string)$row['guardian'],
            'relation' => (string)$row['relation'],
            'phone' => (string)$row['phone'],
        ];
    }

    // DepEd convention: males first, then females, then unspecified.
    $order = ['M' => 0, 'F' => 1, '' => 2];
    usort($learners, static fn($a, $b) => [$order[$a['sex']], $a['name']] <=> [$order[$b['sex']], $b['name']]);
}

$counts = ['M' => 0, 'F' => 0];
foreach ($learners as $l) {
    if (isset($counts[$l['sex']])) {
        $counts[$l['sex']]++;
    }
}
$school_year = $syear . '-' . ($syear + 1);
$grade_title = $grades[$grade_id] ?? '';

$headers = ['LRN', 'Name (Last, First, Middle)', 'Sex', 'Birth Date (mm/dd/yyyy)', 'Age as of 1st Friday of June',
    'Mother Tongue', 'IP (Ethnic Group)', 'Religion', 'Address', "Father's Name", "Mother's Maiden Name",
    'Guardian', 'Relationship', 'Contact Number', 'Remarks'];

if (($_GET['format'] ?? '') === 'csv' && $grade_title !== '') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="SF1_' . preg_replace('/[^A-Za-z0-9]+/', '_', trim($grade_title . ' ' . $section_name)) . '_' . $school_year . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ñ and accents correctly
    fputcsv($out, ['School Form 1 (SF1) School Register', SF1_SCHOOL_NAME, 'School ID ' . SF1_SCHOOL_ID, 'SY ' . $school_year, $grade_title, $section_name !== '' ? 'Section ' . $section_name : ''], ',', '"', '');
    fputcsv($out, $headers, ',', '"', '');
    foreach ($learners as $l) {
        // Leading apostrophe keeps Excel from turning the 12-digit LRN into 1.2E+11.
        fputcsv($out, [$l['lrn'] !== '' ? "'" . $l['lrn'] : '', $l['name'], $l['sex'], $l['birth_date'], $l['age'],
            '', '', '', $l['address'], '', '', $l['guardian'], $l['relation'], $l['phone'], ''], ',', '"', '');
    }
    fputcsv($out, ['Male', $counts['M'], 'Female', $counts['F'], 'Total', count($learners)], ',', '"', '');
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>SF1 School Register<?php echo $grade_title !== '' ? ' — ' . sf1_h($grade_title) : ''; ?> | SmartCampus K12</title>
<link rel="stylesheet" href="/css/tokens.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<link rel="stylesheet" href="/css/reports.css">
</head>
<body>
<form class="toolbar" method="get">
  <a class="btn" href="dashboard.php?view=students">&larr; Dashboard</a>
  <label for="grade_id">Grade level</label>
  <select id="grade_id" name="grade_id" onchange="this.form.submit()">
    <option value="0">Choose a grade&hellip;</option>
    <?php foreach ($grades as $id => $title): ?>
      <option value="<?php echo $id; ?>"<?php echo $id === $grade_id ? ' selected' : ''; ?>><?php echo sf1_h($title); ?></option>
    <?php endforeach; ?>
  </select>
  <?php if ($sections): ?>
    <label for="section_id">Section</label>
    <select id="section_id" name="section_id" onchange="this.form.submit()">
      <option value="0">All sections</option>
      <?php foreach ($sections as $id => $sec): ?>
        <option value="<?php echo $id; ?>"<?php echo $id === $section_id ? ' selected' : ''; ?>><?php echo sf1_h($sec['name']); ?></option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>
  <noscript><button class="btn" type="submit">Show</button></noscript>
  <span class="spacer"></span>
  <?php if ($grade_title !== ''): ?>
    <a class="btn" href="sf1.php?grade_id=<?php echo $grade_id; ?>&amp;section_id=<?php echo $section_id; ?>&amp;format=csv">Download CSV (Excel)</a>
    <button class="btn primary" type="button" onclick="window.print()">Print / Save as PDF</button>
  <?php endif; ?>
</form>

<?php if ($grade_title === ''): ?>
  <p class="empty">Choose a grade level to see its School Form 1 (SF1) School Register for SY <?php echo sf1_h($school_year); ?>.</p>
<?php else: ?>
  <div class="sheet">
    <h1>School Form 1 (SF1) School Register</h1>
    <p class="sub">(This replaces Form 1, Master List &amp; STS Form 2-Family Background and Profile)</p>
    <div class="meta">
      <span><b>School Name:</b> <?php echo sf1_h(SF1_SCHOOL_NAME); ?></span>
      <span><b>School ID:</b> <?php echo sf1_h(SF1_SCHOOL_ID); ?></span>
      <span><b>School Year:</b> <?php echo sf1_h($school_year); ?></span>
      <span><b>Grade Level:</b> <?php echo sf1_h($grade_title); ?></span>
      <span><b>Section:</b> <?php echo $section_name !== '' ? sf1_h($section_name) : '____________'; ?></span>
    </div>
    <table>
      <caption class="sr-only">SF1 learners for <?php echo sf1_h($grade_title); ?></caption>
      <thead><tr>
        <th scope="col">#</th>
        <?php foreach ($headers as $header): ?><th scope="col"><?php echo sf1_h($header); ?></th><?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php if (!$learners): ?>
        <tr><td colspan="<?php echo count($headers) + 1; ?>" class="c">No learners are enrolled in <?php echo sf1_h($grade_title); ?> for SY <?php echo sf1_h($school_year); ?><?php echo $profile === 'teacher' ? ' in your classes' : ''; ?>.</td></tr>
      <?php endif; ?>
      <?php $n = 0; $current = null; foreach ($learners as $l):
          if ($l['sex'] !== $current): $current = $l['sex']; ?>
        <tr class="group"><th colspan="<?php echo count($headers) + 1; ?>"><?php echo $current === 'M' ? 'MALE' : ($current === 'F' ? 'FEMALE' : 'SEX NOT RECORDED'); ?></th></tr>
      <?php endif; $n++; ?>
        <tr>
          <td class="c"><?php echo $n; ?></td>
          <td class="mono"><?php echo $l['lrn'] !== '' ? sf1_h($l['lrn']) : '<span class="flag">none</span>'; ?></td>
          <td><?php echo sf1_h($l['name']); ?></td>
          <td class="c"><?php echo sf1_h($l['sex']); ?></td>
          <td class="mono"><?php echo sf1_h($l['birth_date']); ?></td>
          <td class="c"><?php echo sf1_h($l['age']); ?></td>
          <td></td><td></td><td></td>
          <td><?php echo sf1_h($l['address']); ?></td>
          <td></td><td></td>
          <td><?php echo sf1_h($l['guardian']); ?></td>
          <td><?php echo sf1_h($l['relation']); ?></td>
          <td class="mono"><?php echo sf1_h($l['phone']); ?></td>
          <td></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="totals">
      <span><b>Male:</b> <?php echo $counts['M']; ?></span>
      <span><b>Female:</b> <?php echo $counts['F']; ?></span>
      <span><b>Total:</b> <?php echo count($learners); ?></span>
    </div>
  </div>
<?php endif; ?>
</body>
</html>
