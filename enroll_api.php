<?php
/**
 * Enrollment Portal API (public landing page backend)
 *
 * Reads/writes enrollment data in the school schema.
 * Actions:
 *   GET  ?action=config       -> current enrollment period
 *   POST ?action=submit       -> create enrollment_application
 *   GET  ?action=status&ref=  -> read by reference token
 *   POST ?action=draft_save   -> save draft
 *   GET  ?action=draft_resume&token=XXX -> resume draft
 *   POST ?action=draft_finalize -> finalize draft to application
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (getenv('CORS_ORIGIN') ?: '*'));

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

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($action === 'config') {
    $res = @pg_query($conn, "SELECT school_year, enrollment_opens, enrollment_closes, classes_begin, grade_levels, status FROM kerrfairtex.enrollment_periods ORDER BY updated_at DESC LIMIT 1");
    $row = $res ? pg_fetch_assoc($res) : null;
    if (!$row) {
        echo json_encode([
            'period' => [
                'school_year' => '2026-2027',
                'enrollment_opens' => '2026-06-01',
                'enrollment_closes' => '2026-08-15',
                'classes_begin' => '2026-08-17',
                'grade_levels' => 'Kinder, Grade 1-12',
                'status' => 'Closed',
            ],
        ]);
        exit;
    }
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
    $ref_esc = pg_escape_string($conn, $ref);
    $res = @pg_query($conn, "SELECT id, ref, learner_name, grade_level, enrollment_type, status, created_at, updated_at, notes FROM kerrfairtex.enrollment_applications WHERE UPPER(ref) = '$ref_esc'");
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

    $ref = 'BATU-' . date('Y') . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $status = 'Submitted';

    $cols = [
        'ref',
        'learner_name',
        'birth_date',
        'sex',
        'birthplace',
        'address',
        'grade_level',
        'school_year',
        'enrollment_type',
        'parent_name',
        'parent_relationship',
        'parent_contact',
        'parent_address',
        'parent_email',
        'prev_school',
        'prev_school_address',
        'last_grade',
        'prev_sy',
        'learner_ref_no',
        'documents',
        'status',
    ];
    $map = [
        'lname' => 'learner_name',
        'fname' => 'first_name',
        'mname' => 'middle_name',
        'bdate' => 'birth_date',
        'sex' => 'sex',
        'bplace' => 'birthplace',
        'laddress' => 'address',
        'grade' => 'grade_level',
        'sy' => 'school_year',
        'etype' => 'enrollment_type',
        'pname' => 'parent_name',
        'prel' => 'parent_relationship',
        'pcontact' => 'parent_contact',
        'paddress' => 'parent_address',
        'pemail' => 'parent_email',
        'pschool' => 'prev_school',
        'psaddress' => 'prev_school_address',
        'plastgrade' => 'last_grade',
        'psy' => 'prev_sy',
        'lref' => 'learner_ref_no',
        'last_name' => 'learner_name',
        'first_name' => 'first_name',
        'middle_name' => 'middle_name',
        'guardian_name' => 'parent_name',
        'guardian_relationship' => 'parent_relationship',
        'guardian_contact' => 'parent_contact',
        'guardian_address' => 'parent_address',
        'target_grade' => 'grade_level',
    ];

    $vals = [];
    foreach ($cols as $c) {
        if ($c === 'ref') {
            $vals[] = "'" . pg_escape_string($conn, $ref) . "'";
            continue;
        }
        if ($c === 'status') {
            $vals[] = "'" . pg_escape_string($conn, $status) . "'";
            continue;
        }
        if ($c === 'documents') {
            $doc = isset($posted['documents']) ? json_encode($posted['documents']) : null;
            $vals[] = $doc ? "'" . pg_escape_string($conn, $doc) . "'" : 'NULL';
            continue;
        }
        if ($c === 'learner_name') {
            $v = trim((string)($posted['last_name'] ?? '') . ', ' . ($posted['first_name'] ?? '') . ' ' . ($posted['middle_name'] ?? ''));
            $vals[] = $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'";
            continue;
        }
        $srcKey = array_search($c, $map, true);
        $v = ($srcKey !== false && isset($posted[$srcKey])) ? (string)$posted[$srcKey] : '';
        $vals[] = $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'";
    }
    $sql = 'INSERT INTO kerrfairtex.enrollment_applications (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
    $r = @pg_query($conn, $sql);
    if (!$r) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save application: ' . pg_last_error($conn)]);
        exit;
    }
    echo json_encode(['ref' => $ref, 'status' => $status]);
    exit;
}

if ($action === 'draft_save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $posted = json_decode($raw, true);
    if (!is_array($posted)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 14 * 86400);
    $payload_esc = pg_escape_string($conn, json_encode($posted, JSON_UNESCAPED_UNICODE));
    $token_esc   = pg_escape_string($conn, $token);
    $sql = "INSERT INTO kerrfairtex.enrollment_drafts (token, payload, status, expires_at)
            VALUES ('$token_esc', '$payload_esc'::jsonb, 'active', '$expires')";
    $r = @pg_query($conn, $sql);
    if (!$r) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save draft']);
        exit;
    }
    echo json_encode([
        'token'      => $token,
        'expires_at' => $expires,
        'url'        => '?action=draft_resume&token=' . $token,
    ]);
    exit;
}

if ($action === 'draft_resume' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid token format']);
        exit;
    }
    $token_esc = pg_escape_string($conn, $token);
    $r = @pg_query($conn, "SELECT payload, expires_at, status FROM kerrfairtex.enrollment_drafts WHERE token = '$token_esc' AND status = 'active' AND expires_at > now() LIMIT 1");
    $row = $r ? pg_fetch_assoc($r) : null;
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Draft not found or expired']);
        exit;
    }
    $payload = json_decode($row['payload'], true);
    echo json_encode([
        'ok'         => true,
        'payload'    => is_array($payload) ? $payload : new stdClass(),
        'expires_at' => $row['expires_at'],
    ]);
    exit;
}

if ($action === 'draft_finalize' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = file_get_contents('php://input');
    $posted = json_decode($raw, true);
    if (!is_array($posted)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    $token = isset($posted['token']) ? (string)$posted['token'] : '';
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    $token_esc = pg_escape_string($conn, $token);
    $r = @pg_query($conn, "UPDATE kerrfairtex.enrollment_drafts SET status = 'finalized', updated_at = now() WHERE token = '$token_esc' AND status = 'active' AND expires_at > now()");
    if (!$r || pg_affected_rows($r) === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Draft not found or expired']);
        exit;
    }
    $yr = date('Y');
    $ref = 'BATU-' . $yr . '-' . str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $status = 'Submitted';
    $cols = [
        'ref',
        'learner_name',
        'birth_date',
        'sex',
        'birthplace',
        'address',
        'grade_level',
        'school_year',
        'enrollment_type',
        'parent_name',
        'parent_relationship',
        'parent_contact',
        'parent_address',
        'parent_email',
        'prev_school',
        'prev_school_address',
        'last_grade',
        'prev_sy',
        'learner_ref_no',
        'documents',
        'status',
    ];
    $map = [
        'lname' => 'learner_name',
        'fname' => 'first_name',
        'mname' => 'middle_name',
        'bdate' => 'birth_date',
        'sex' => 'sex',
        'bplace' => 'birthplace',
        'laddress' => 'address',
        'grade' => 'grade_level',
        'sy' => 'school_year',
        'etype' => 'enrollment_type',
        'pname' => 'parent_name',
        'prel' => 'parent_relationship',
        'pcontact' => 'parent_contact',
        'paddress' => 'parent_address',
        'pemail' => 'parent_email',
        'pschool' => 'prev_school',
        'psaddress' => 'prev_school_address',
        'plastgrade' => 'last_grade',
        'psy' => 'prev_sy',
        'lref' => 'learner_ref_no',
        'last_name' => 'learner_name',
        'first_name' => 'first_name',
        'middle_name' => 'middle_name',
        'guardian_name' => 'parent_name',
        'guardian_relationship' => 'parent_relationship',
        'guardian_contact' => 'parent_contact',
        'guardian_address' => 'parent_address',
        'target_grade' => 'grade_level',
    ];
    $vals = [];
    foreach ($cols as $c) {
        if ($c === 'ref') {
            $vals[] = "'" . pg_escape_string($conn, $ref) . "'";
            continue;
        }
        if ($c === 'status') {
            $vals[] = "'" . pg_escape_string($conn, $status) . "'";
            continue;
        }
        if ($c === 'documents') {
            $doc = isset($posted['documents']) ? json_encode($posted['documents']) : null;
            $vals[] = $doc ? "'" . pg_escape_string($conn, $doc) . "'" : 'NULL';
            continue;
        }
        if ($c === 'learner_name') {
            $v = trim((string)($posted['last_name'] ?? '') . ', ' . ($posted['first_name'] ?? '') . ' ' . ($posted['middle_name'] ?? ''));
            $vals[] = $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'";
            continue;
        }
        $srcKey = array_search($c, $map, true);
        $v = ($srcKey !== false && isset($posted[$srcKey])) ? (string)$posted[$srcKey] : '';
        $vals[] = $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'";
    }
    $sql = 'INSERT INTO kerrfairtex.enrollment_applications (' . implode(',', $cols) . ') VALUES (' . implode(',', $vals) . ')';
    $r2 = @pg_query($conn, $sql);
    if (!$r2) {
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
