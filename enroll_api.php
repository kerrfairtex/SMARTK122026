<?php
/**
 * Enrollment Portal API (public landing page backend)
 *
 * Reads/writes enrollment data in the Supabase `kerrfairtex` schema.
 * The public page calls this instead of relying on localStorage, so
 * applications persist server-side and status is driven from the DB
 * (the "dashboard" configuration lives in enrollment_periods).
 *
 * Actions:
 *   GET  ?action=config  -> current enrollment period (dates, status, grades)
 *   POST ?action=submit  -> create application, returns reference number
 *   GET  ?action=status&ref=XXX -> application + status pipeline
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function db_conn() {
    static $c = null;
    if ($c === null) {
        $c = db_start(false);
        if ($c === false) {
            throw new Exception('db');
        }
    }
    return $c;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

try {
    $conn = db_conn();
} catch (Throwable $t) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Allow cross-origin POST (fetch from same origin anyway, but be safe)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($action === 'config') {
    $res = @pg_query($conn, "SELECT school_year, enrollment_opens, enrollment_closes, classes_begin, grade_levels, status FROM enrollment_periods ORDER BY updated_at DESC LIMIT 1");
    $row = $res ? pg_fetch_assoc($res) : null;
    if (!$row) {
        echo json_encode(['error' => 'No enrollment period configured']);
        exit;
    }
    // Normalize date fields to ISO strings
    foreach (['enrollment_opens', 'enrollment_closes', 'classes_begin'] as $k) {
        $row[$k] = $row[$k] ? date('Y-m-d', strtotime($row[$k])) : null;
    }
    echo json_encode(['period' => $row]);
    exit;
}

if ($action === 'status') {
    $ref = isset($_GET['ref']) ? strtoupper(trim($_GET['ref'])) : '';
    if (!$ref) {
        http_response_code(400);
        echo json_encode(['error' => 'Reference required']);
        exit;
    }
    $ref = pg_escape_string($conn, $ref);
    $res = @pg_query($conn, "SELECT ref, learner_name, grade_level, enrollment_type, status, created_at FROM enrollment_applications WHERE ref = '$ref'");
    $row = $res ? pg_fetch_assoc($res) : null;
    if (!$row) {
        echo json_encode(['found' => false]);
        exit;
    }
    echo json_encode(['found' => true, 'application' => $row]);
    exit;
}

if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $posted = json_decode($raw, true);
    if (!is_array($posted)) {
        $posted = $_POST;
    }

    // Build reference: BATU-<year>-<random6>
    $yr = date('Y');
    $ref = 'BATU-' . $yr . '-' . str_pad(rand(0, 999999), 6, '0');

    $status = 'Submitted';
    $documents = isset($posted['documents']) ? json_encode($posted['documents']) : null;

    $cols = ['ref','learner_name','birth_date','sex','birthplace','address','grade_level','school_year','enrollment_type','parent_name','parent_relationship','parent_contact','parent_address','parent_email','prev_school','prev_school_address','last_grade','prev_sy','learner_ref_no','documents','status'];
    $vals = [];
    $map = [
        'lname' => 'learner_name', 'bdate' => 'birth_date', 'sex' => 'sex', 'bplace' => 'birthplace',
        'laddress' => 'address', 'grade' => 'grade_level', 'sy' => 'school_year', 'etype' => 'enrollment_type',
        'pname' => 'parent_name', 'prel' => 'parent_relationship', 'pcontact' => 'parent_contact',
        'paddress' => 'parent_address', 'pemail' => 'parent_email', 'pschool' => 'prev_school',
        'psaddress' => 'prev_school_address', 'plastgrade' => 'last_grade', 'psy' => 'prev_sy', 'lref' => 'learner_ref_no'
    ];
    foreach ($cols as $c) {
        if ($c === 'ref') { $vals[] = "'" . pg_escape_string($conn, $ref) . "'"; continue; }
        if ($c === 'status') { $vals[] = "'" . pg_escape_string($conn, $status) . "'"; continue; }
        if ($c === 'documents') { $vals[] = $documents ? "'" . pg_escape_string($conn, $documents) . "'" : 'NULL'; continue; }
        $srcKey = array_search($c, $map);
        $v = ($srcKey !== false && isset($posted[$srcKey])) ? $posted[$srcKey] : '';
        $vals[] = $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'";
    }
    $sql = "INSERT INTO enrollment_applications (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
    $r = @pg_query($conn, $sql);
    if (!$r) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save application']);
        exit;
    }
    echo json_encode(['ref' => $ref, 'status' => $status]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
exit;
