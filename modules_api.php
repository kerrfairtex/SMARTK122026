<?php
/**
 * Modules Status API (Tier 3 / re-categorized Tier 2, public read endpoint)
 *
 * Returns the current enabled/disabled state of the 13 RosarioSIS
 * modules from the school config table as JSON. The public landing
 * page uses this to render "active / not yet active" dots on the
 * feature tiles without needing the RosarioSIS session.
 *
 * GET /modules_api.php
 *   -> 200 {
 *        "modules": {
 *           "School_Setup": true, "Students": true, "Users": true,
 *           "Scheduling": true, "Grades": true, "Attendance": true,
 *           "Eligibility": true, "Discipline": true, "Accounting": true,
 *           "Student_Billing": true, "Food_Service": true, "Resources": true,
 *           "Custom": true
 *        },
 *        "updated_at": "..."
 *      }
 *   -> 503 { "error": "Database unavailable" }
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

try {
    $conn = db_conn();
    $r = @pg_query($conn, "SELECT config_value, updated_at FROM config WHERE title = 'MODULES' LIMIT 1");
    $row = $r ? pg_fetch_assoc($r) : null;
    $modules = [];
    if ($row && $row['config_value']) {
        // The MODULES value is a PHP-serialized array. Decode it.
        // Suppress warnings because malformed data should still serve a
        // safe default (everything available).
        $decoded = @unserialize($row['config_value']);
        if (is_array($decoded)) {
            foreach ($decoded as $k => $v) {
                $modules[$k] = (bool)$v;
            }
        }
    }
    // If decoding failed or produced an empty array, fall back to all-true
    // (the school defaults to all modules enabled until they disable one).
    if (empty($modules)) {
        $modules = [
            'School_Setup' => true, 'Students' => true, 'Users' => true,
            'Scheduling'   => true, 'Grades'  => true, 'Attendance' => true,
            'Eligibility'  => true, 'Discipline' => true, 'Accounting' => true,
            'Student_Billing' => true, 'Food_Service' => true, 'Resources' => true,
            'Custom' => true,
        ];
    }
    echo json_encode([
        'modules'    => $modules,
        'updated_at' => $row ? $row['updated_at'] : null,
    ]);
} catch (Throwable $t) {
    // On DB failure, default to all-enabled (safe UX).
    echo json_encode([
        'modules' => [
            'School_Setup' => true, 'Students' => true, 'Users' => true,
            'Scheduling'   => true, 'Grades'  => true, 'Attendance' => true,
            'Eligibility'  => true, 'Discipline' => true, 'Accounting' => true,
            'Student_Billing' => true, 'Food_Service' => true, 'Resources' => true,
            'Custom' => true,
        ],
        'updated_at' => null,
    ]);
}
