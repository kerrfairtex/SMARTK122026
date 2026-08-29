<?php
/**
 * About Content Read API (Tier 3, public read endpoint)
 *
 * Lets the public landing page fetch the current mission / vision /
 * values_intro copy from the school schema about_content table.
 *
 * GET /about_content_api.php?section=mission
 *   -> 200 { section: "mission", body: "...", updated_at: "..." }
 *   -> 400 { error: "Missing section" }
 *   -> 404 { error: "Not found" }
 *
 * This endpoint is public (no auth) by design: the body is meant to be
 * displayed on the public landing page. Editing requires the signed
 * URL flow in about_edit.php.
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

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

$section = isset($_GET['section']) ? (string)$_GET['section'] : '';
$section = strtolower(preg_replace('/[^a-z_]/', '', $section));
$allowed = ['mission', 'vision', 'values_intro'];
if (!in_array($section, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown or missing section']);
    exit;
}

try {
    $conn = db_conn();
    $r = @pg_query(
        $conn,
        "SELECT section, body, updated_at, updated_by FROM kerrfairtex.about_content WHERE section = '" .
        pg_escape_string($conn, $section) . "' LIMIT 1"
    );
    $row = $r ? pg_fetch_assoc($r) : null;
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    echo json_encode([
        'section'    => $row['section'],
        'body'       => (string)$row['body'],
        'updated_at' => $row['updated_at'],
        'updated_by' => $row['updated_by'] ?: null,
    ]);
} catch (Throwable $t) {
    http_response_code(503);
    echo json_encode(['error' => 'Database unavailable']);
}
