<?php
/**
 * Public News & Events feed for the homepage (read-only JSON).
 *
 * GET news_api.php -> { news: [...], events: [...] }
 *   news:   latest 6 published, unexpired posts that are not upcoming events
 *   events: next 6 published events dated today or later
 * Cached for 5 minutes. Body text is plain text; the page escapes it.
 *
 * @package SmartCampus
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'GET only']);
    exit;
}

$conn = db_start(false);
if ($conn === false) {
    http_response_code(503);
    header('Cache-Control: no-store');
    echo json_encode(['success' => false, 'error' => 'News is temporarily unavailable.']);
    exit;
}

$visible = "is_published AND (expires_on IS NULL OR expires_on >= CURRENT_DATE)";

$fetch = static function (string $sql) use ($conn): array {
    $result = pg_query($conn, $sql);
    $rows = [];
    while ($result && ($row = pg_fetch_assoc($result))) {
        $rows[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'body' => $row['body'],
            'category' => $row['category'],
            'event_date' => $row['event_date'],
            'posted' => substr((string)$row['created_at'], 0, 10),
        ];
    }
    return $rows;
};

$columns = 'id, title, body, category, event_date, created_at';

// Public and cacheable: drop the session cookie Warehouse.php sets.
header_remove('Set-Cookie');
header('Cache-Control: public, max-age=300');

echo json_encode([
    'success' => true,
    'news' => $fetch("SELECT $columns FROM kerrfairtex.announcements WHERE $visible
        AND NOT (category = 'event' AND event_date >= CURRENT_DATE)
        ORDER BY created_at DESC LIMIT 6"),
    'events' => $fetch("SELECT $columns FROM kerrfairtex.announcements WHERE $visible
        AND category = 'event' AND event_date >= CURRENT_DATE
        ORDER BY event_date, id LIMIT 6"),
]);
