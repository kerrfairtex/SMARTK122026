<?php
/**
 * Page Engagement Signal Endpoint (PR-4, non-destructive)
 *
 * Receives a single POST from the public landing page when a visitor
 * scrolls a section into view for the first time in a session. Writes
 * one row to kerrfairtex.access_log with status='scroll:<section>'.
 * The existing access_log table already receives login events from the
 * SIS portal; engagement rows are distinguishable by the 'scroll:' prefix
 * on status.
 *
 * Endpoint: POST /engagement.php
 *   Body:  { "section": "<section-id>", "duration": <int-ms-optional> }
 *   Reply: { "ok": true } on success
 *
 * Privacy:
 *   - No cookies set, no fingerprinting, no third-party calls.
 *   - IP recorded as-is (server logs already do this).
 *   - Lightweight in-process rate-limit: 1 row per (section, IP) per hour.
 *     The limit is in-memory only (per-process); acceptable for a single-
 *     instance Render service. Resets on process restart.
 */

require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

function engagement_reply($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    engagement_reply(405, ['error' => 'Method not allowed']);
}

$raw = file_get_contents('php://input');
if (!$raw) {
    engagement_reply(400, ['error' => 'Empty body']);
}
$data = json_decode($raw, true);
if (!is_array($data)) {
    engagement_reply(400, ['error' => 'Invalid JSON']);
}

$section_raw = isset($data['section']) ? (string)$data['section'] : '';
$section     = strtolower(preg_replace('/[^a-z0-9_-]/', '', $section_raw));
$section     = substr($section, 0, 64);
$duration    = isset($data['duration']) ? max(0, min(86400000, (int)$data['duration'])) : 0;  // clamp to <=24h

if ($section === '') {
    engagement_reply(400, ['error' => 'Missing section']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// In-process rate limit: 1 row per (section, IP) per hour.
// Acceptable for a single-instance Render service. No external cache needed.
static $rate_state = [];
$rate_key = $ip . '|' . $section;
$now      = time();
foreach ($rate_state as $k => $t) {
    if ($t <= $now - 3600) unset($rate_state[$k]);
}
if (isset($rate_state[$rate_key])) {
    engagement_reply(429, ['error' => 'rate-limited']);
}
$rate_state[$rate_key] = $now;

try {
    $conn = db_conn();
} catch (Throwable $t) {
    engagement_reply(503, ['error' => 'Database unavailable']);
}

$syear     = 2026;  // Engagement events are landing-page-aggregate; not bound to a single syear.
$ip_esc    = pg_escape_string($conn, substr($ip, 0, 50));
$ua        = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '';
$ua_esc    = pg_escape_string($conn, substr($ua, 0, 500));
$status    = 'scroll:' . $section . ($duration > 0 ? ':' . $duration : '');
$status_esc = pg_escape_string($conn, $status);

$sql = "INSERT INTO kerrfairtex.access_log
        (syear, username, profile, ip_address, user_agent, status, created_at, updated_at)
        VALUES ($syear, '', '', '$ip_esc', '$ua_esc', '$status_esc', now(), now())";

$res = @pg_query($conn, $sql);
if (!$res) {
    engagement_reply(500, ['error' => 'Write failed']);
}

engagement_reply(200, ['ok' => true]);
