<?php
/**
 * Contact form API (public landing page -> SmartCampus project team).
 * Stores messages in the Supabase `kerrfairtex` schema (contact_messages).
 */
require_once 'database.inc.php';
require_once 'Warehouse.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function db_conn() {
    static $c = null;
    if ($c === null) { $c = db_start(false); if ($c === false) throw new Exception('db'); }
    return $c;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = db_conn();
    } catch (Throwable $t) {
        http_response_code(503);
        echo json_encode(['error' => 'Database unavailable']);
        exit;
    }
    $raw = file_get_contents('php://input');
    $posted = json_decode($raw, true);
    if (!is_array($posted)) { $posted = $_POST; }

    $name = isset($posted['name']) ? trim($posted['name']) : '';
    $email = isset($posted['email']) ? trim($posted['email']) : '';
    $mobile = isset($posted['mobile']) ? trim($posted['mobile']) : '';
    $concern = isset($posted['concern']) ? trim($posted['concern']) : '';
    $message = isset($posted['message']) ? trim($posted['message']) : '';

    if (!$name || !$email || !$concern || !$message) {
        http_response_code(400);
        echo json_encode(['error' => 'Please complete the required fields.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Please enter a valid email address.']);
        exit;
    }

    $esc = function ($v) use ($conn) { return $v === '' ? 'NULL' : "'" . pg_escape_string($conn, $v) . "'"; };
    $sql = "INSERT INTO contact_messages (full_name, email, mobile, concern, message) VALUES ("
        . $esc($name) . ',' . $esc($email) . ',' . $esc($mobile) . ',' . $esc($concern) . ',' . $esc($message) . ")";
    $r = @pg_query($conn, $sql);
    if (!$r) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not save your message. Please try again or contact us directly.']);
        exit;
    }

    // --- Tier 3: smart contact routing ---
    // Map concern to a recipient email. The recipient is logged to
    // access_log so the school can see which mailbox should answer.
    // The recipient is NEVER sent to the public submitter (no scraping).
    $routing = [
        'Enrollment'        => 'kerrfairtex@gmail.com',  // placeholders; school fills real aliases
        'SmartCampus'       => 'kerrfairtex@gmail.com',
        'Website'           => 'kerrfairtex@gmail.com',
        'Technical Support' => 'kerrfairtex@gmail.com',
        'School Information'=> 'kerrfairtex@gmail.com',
        'Other'             => 'kerrfairtex@gmail.com',
    ];
    $recipient = isset($routing[$concern]) ? $routing[$concern] : 'kerrfairtex@gmail.com';
    @pg_query(
        $conn,
        "INSERT INTO kerrfairtex.access_log
            (syear, username, profile, ip_address, user_agent, status, created_at, updated_at)
         VALUES (2026, '', '', '" . pg_escape_string($conn, substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 50)) . "', '" .
            pg_escape_string($conn, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)) . "', '" .
            pg_escape_string($conn, 'contact_routed:' . $concern . ':' . $recipient) . "', now(), now())"
    );

    echo json_encode(['ok' => true, 'message' => 'Message received. The SmartCampus team will respond via your provided email or mobile.']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Unknown action']);
exit;
