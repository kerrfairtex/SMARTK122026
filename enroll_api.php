<?php
/**
 * SmartCampus K-12 Enrollment API
 *
 * Public enrollment submission endpoint. Uses the existing RosarioSIS
 * PostgreSQL connection layer (database.inc.php + Warehouse.php) — NOT a
 * separate MySQLi layer.
 *
 * Grounded in the live kerrfairtex schema:
 *   - enrollment_applications: id, ref, learner_name, first_name, middle_name,
 *     last_name, name_suffix, birth_date, sex, birthplace, address,
 *     grade_level, school_year, enrollment_type, parent_name, parent_contact,
 *     parent_email, prev_school, last_grade, status, created_at
 *   - enrollment_periods: id, school_year, enrollment_opens, enrollment_closes,
 *     classes_begin, grade_levels, status, updated_at
 *   - enrollment_drafts: id, token, payload, status, expires_at, created_at, updated_at
 */

declare(strict_types=1);

require_once __DIR__ . '/database.inc.php';
require_once __DIR__ . '/Warehouse.php';

// Error handling — never display errors to applicants
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/var/log/enroll_api_errors.log');

// ---------------------------------------------------------------------------
// Database helpers (wrap the existing PostgreSQL connection)
// ---------------------------------------------------------------------------

/**
 * Get a PostgreSQL connection reusing the RosarioSIS db_start() bootstrap.
 */
function db_conn() {
    static $c = null;
    if ($c === null) {
        $c = db_start(false);
        if ($c === false) {
            throw new Exception('Database unavailable');
        }
    }
    return $c;
}

/**
 * Execute a parameterized PostgreSQL query.
 * Uses pg_query_params for safe parameter binding.
 *
 * @return resource|false PostgreSQL result resource
 */
function enrollDbQuery($sql, $params = []) {
    $conn = db_conn();
    if ($params !== null && count($params) > 0) {
        $result = pg_query_params($conn, $sql, $params);
    } else {
        $result = pg_query($conn, $sql);
    }
    return $result;
}

/**
 * Fetch a single row as an associative array.
 */
function dbFetchRow($result) {
    if ($result === false) return null;
    $row = pg_fetch_assoc($result);
    return $row ?: null;
}

/**
 * Fetch all rows as an array of associative arrays.
 */
function dbFetchAll($result) {
    if ($result === false) return [];
    $rows = [];
    while ($row = pg_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

// ---------------------------------------------------------------------------
// Input validation and sanitization
// ---------------------------------------------------------------------------

function sanitizeInput($data, $type = 'string') {
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $data);
    }

    if (empty($data) && $type !== 'date' && $type !== 'boolean') {
        return $data;
    }

    switch ($type) {
        case 'string':
            return trim(htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8'));

        case 'int':
            $sanitized = (int) $data;
            if ($sanitized < 0) {
                throw new Exception("Invalid integer value");
            }
            return $sanitized;

        case 'email':
            $sanitized = trim((string)$data);
            if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }
            return $sanitized;

        case 'phone':
            $sanitized = preg_replace('/[^0-9+()\-\s]/', '', (string)$data);
            if (!preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $sanitized)) {
                throw new Exception("Invalid phone format. Please enter a valid phone number.");
            }
            return $sanitized;

        case 'date':
            $timestamp = strtotime((string)$data);
            if ($timestamp === false) {
                throw new Exception("Invalid date format. Expected YYYY-MM-DD.");
            }
            $sanitized = date('Y-m-d', $timestamp);
            $today = date('Y-m-d');
            if ($sanitized > $today) {
                throw new Exception("Date of birth cannot be in the future.");
            }
            $min_age_date = date('Y-m-d', strtotime('-25 years'));
            if ($sanitized < $min_age_date) {
                throw new Exception("Student age appears too old for current enrollment period.");
            }
            return $sanitized;

        case 'grade':
            $valid_grades = [
                'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
                'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9',
                'Grade 10', 'Grade 11', 'Grade 12'
            ];
            if (!in_array($data, $valid_grades)) {
                throw new Exception("Invalid grade level. Must be one of: " . implode(', ', $valid_grades));
            }
            return $data;

        case 'grade_or_text':
            // For plastgrade: accept predefined grades OR free text
            $valid_grades = [
                'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
                'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9',
                'Grade 10', 'Grade 11', 'Grade 12'
            ];
            if (in_array($data, $valid_grades)) {
                return $data;
            }
            // Allow free text (e.g., "Grade 6", "4th Grade", "Nursery", etc.)
            $sanitized = trim((string)$data);
            if (strlen($sanitized) < 1 || strlen($sanitized) > 100) {
                throw new Exception("Last grade completed must be 1-100 characters.");
            }
            return htmlspecialchars($sanitized, ENT_QUOTES, 'UTF-8');

        case 'boolean':
            $sanitized = filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($sanitized === null) {
                throw new Exception("Invalid boolean value");
            }
            return $sanitized;

        case 'text':
            return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');

        case 'school_year':
            $sanitized = trim((string)$data);
            if (!preg_match('/^\d{4}-\d{4}$/', $sanitized)) {
                throw new Exception("Invalid school year format. Expected YYYY-YYYY (e.g., 2026-2027).");
            }
            $parts = explode('-', $sanitized);
            if ((int)$parts[1] !== (int)$parts[0] + 1) {
                throw new Exception("Invalid school year range. Must be consecutive years (e.g., 2026-2027).");
            }
            return $sanitized;

        case 'enrollment_type':
            $valid_types = ['New', 'Transfer'];
            if (!in_array($data, $valid_types)) {
                throw new Exception("Invalid enrollment type. Must be 'New' or 'Transfer'.");
            }
            return $data;

        default:
            return $data;
    }
}

/**
 * Generate unique enrollment reference number.
 * Format: BATU-YYYY-XXXXXX (6-digit numeric, matching existing data)
 */
function generateReferenceNumber() {
    $year = date('Y');
    $random_part = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    return "BATU-{$year}-{$random_part}";
}

/**
 * Resolve enrollment_period_id from school_year using the live schema.
 * Throws if no period exists or if the period is not open.
 */
function resolveEnrollmentPeriod($schoolYear) {
    $conn = db_conn();
    $sql = "SELECT id, school_year, enrollment_opens, enrollment_closes, status
            FROM kerrfairtex.enrollment_periods
            WHERE school_year = $1
            ORDER BY enrollment_opens DESC
            LIMIT 1";
    $result = pg_query_params($conn, $sql, [$schoolYear]);

    if ($result === false) {
        throw new Exception("Database error resolving enrollment period");
    }

    $row = pg_fetch_assoc($result);
    if (!$row) {
        throw new Exception("No enrollment period found for school year " . htmlspecialchars($schoolYear));
    }

    // Determine if enrollment is currently open via date comparison
    $isOpen = false;
    if (!empty($row['enrollment_opens']) && !empty($row['enrollment_closes'])) {
        $now = date('Y-m-d');
        $isOpen = ($row['enrollment_opens'] <= $now && $row['enrollment_closes'] >= $now);
    }
    // Fallback: explicit 'Open' status column
    if (!$isOpen && isset($row['status']) && $row['status'] === 'Open') {
        $isOpen = true;
    }

    if (!$isOpen) {
        throw new Exception("Enrollment period for " . htmlspecialchars($schoolYear) . " is not currently open.");
    }

    return (int)$row['id'];
}

/**
 * Process enrollment submission.
 * Aligns to the live kerrfairtex.enrollment_applications schema.
 */
function handleEnrollmentSubmission($data) {
    $conn = db_conn();

    // Honeypot: if website_url is populated, this is a bot — reject silently
    if (!empty($data['website_url'])) {
        throw new Exception('Submission rejected.');
    }

    // Required fields (from the public form after rename)
    $required_fields = [
        'first_name', 'last_name', 'birth_date', 'laddress',
        'sex', 'birthplace', 'grade_level', 'school_year',
        'etype', 'pname', 'pcontact', 'pschool', 'plastgrade'
    ];

    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: " . $field);
        }
    }

    // Sanitize and validate all input
    $sanitized = [];

    $sanitized['first_name']      = sanitizeInput($data['first_name'], 'string');
    $sanitized['last_name']       = sanitizeInput($data['last_name'], 'string');
    $sanitized['middle_name']     = !empty($data['middle_name']) ? sanitizeInput($data['middle_name'], 'string') : null;
    $sanitized['name_suffix']     = !empty($data['name_suffix']) ? sanitizeInput($data['name_suffix'], 'string') : null;
    $sanitized['birth_date']      = sanitizeInput($data['birth_date'], 'date');
    $sanitized['laddress']        = sanitizeInput($data['laddress'], 'string');
    $sanitized['sex']             = sanitizeInput($data['sex'], 'string');
    $sanitized['birthplace']      = sanitizeInput($data['birthplace'], 'string');
    $sanitized['grade_level']     = sanitizeInput($data['grade_level'], 'grade');
    $sanitized['school_year']     = sanitizeInput($data['school_year'], 'school_year');
    $sanitized['etype']           = sanitizeInput($data['etype'], 'enrollment_type');
    $sanitized['pname']           = sanitizeInput($data['pname'], 'string');
    $sanitized['pcontact']        = sanitizeInput($data['pcontact'], 'phone');
    $sanitized['pschool']         = sanitizeInput($data['pschool'], 'string');
    $sanitized['plastgrade']      = sanitizeInput($data['plastgrade'], 'grade_or_text');

    // Optional fields
    $sanitized['pemail'] = !empty($data['pemail']) ? sanitizeInput($data['pemail'], 'email') : null;

    // learner_name: construct from structured name components
    $nameParts = array_filter([
        $sanitized['first_name'],
        $sanitized['middle_name'],
        $sanitized['last_name'],
        $sanitized['name_suffix']
    ], function($p) { return $p !== null && $p !== ''; });
    $learnerName = implode(' ', $nameParts);

    // Resolve enrollment period dynamically from school_year
    $enrollmentPeriodId = resolveEnrollmentPeriod($sanitized['school_year']);

    // Generate unique reference number
    $ref = generateReferenceNumber();

    // Ensure ref uniqueness (retry on collision)
    $attempts = 0;
    while ($attempts < 5) {
        $exists = pg_query_params($conn,
            "SELECT id FROM kerrfairtex.enrollment_applications WHERE ref = $1",
            [$ref]
        );
        if ($exists !== false && pg_num_rows($exists) === 0) {
            break;
        }
        $ref = generateReferenceNumber();
        $attempts++;
    }

    // INSERT into enrollment_applications matching the LIVE schema
    $sql = "INSERT INTO kerrfairtex.enrollment_applications (
        ref, learner_name, first_name, middle_name, last_name, name_suffix,
        birth_date, sex, birthplace, address,
        grade_level, school_year, enrollment_type,
        parent_name, parent_contact, parent_email,
        prev_school, last_grade,
        status, created_at
    ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16, $17, $18, 'Submitted', NOW())";

    $params = [
        $ref,
        $learnerName,
        $sanitized['first_name'],
        $sanitized['middle_name'],
        $sanitized['last_name'],
        $sanitized['name_suffix'],
        $sanitized['birth_date'],
        $sanitized['sex'],
        $sanitized['birthplace'],
        $sanitized['laddress'],
        $sanitized['grade_level'],
        $sanitized['school_year'],
        $sanitized['etype'],
        $sanitized['pname'],
        $sanitized['pcontact'],
        $sanitized['pemail'],
        $sanitized['pschool'],
        $sanitized['plastgrade'],
    ];

    $result = pg_query_params($conn, $sql, $params);

    if ($result === false) {
        throw new Exception("Failed to save application");
    }

    return [
        'success' => true,
        'ref' => $ref,
        'status' => 'Submitted',
        'message' => 'Enrollment application submitted successfully'
    ];
}

/**
 * Get current enrollment period configuration.
 * Uses the live schema: enrollment_periods with school_year, enrollment_opens,
 * enrollment_closes. Open status is determined by date comparison.
 */
function getEnrollmentConfiguration() {
    $conn = db_conn();

    $sql = "SELECT id, school_year, enrollment_opens, enrollment_closes,
                   classes_begin, status, grade_levels
            FROM kerrfairtex.enrollment_periods
            ORDER BY enrollment_opens DESC
            LIMIT 1";

    $result = pg_query($conn, $sql);

    if ($result === false) {
        throw new Exception("Database error");
    }

    $row = pg_fetch_assoc($result);

    if (!$row) {
        return [
            'success' => true,
            'period' => null,
            'status' => 'Closed'
        ];
    }

    // Determine if enrollment is currently open via date comparison
    $isOpen = false;
    if (!empty($row['enrollment_opens']) && !empty($row['enrollment_closes'])) {
        $now = date('Y-m-d');
        $isOpen = ($row['enrollment_opens'] <= $now && $row['enrollment_closes'] >= $now);
    }
    if (!$isOpen && isset($row['status']) && $row['status'] === 'Open') {
        $isOpen = true;
    }

    return [
        'success' => true,
        'period' => [
            'enrollment_period_id' => (int)$row['id'],
            'school_year' => $row['school_year'],
            'title' => $row['school_year'] . ' Academic Year',
            'enrollment_opens' => $row['enrollment_opens'],
            'enrollment_closes' => $row['enrollment_closes'],
            'classes_begin' => $row['classes_begin'],
            'grade_levels' => $row['grade_levels'],
        ],
        'status' => $isOpen ? 'Open' : 'Closed',
        'is_open' => $isOpen,
    ];
}

/**
 * Get application status by reference number.
 * Uses the live schema: ref is a UNIQUE column on enrollment_applications.
 */
function getApplicationStatus($ref) {
    $conn = db_conn();

    // Validate reference format: BATU-YYYY-XXXXXX
    if (!preg_match('/^BATU-\d{4}-\d{6}$/', $ref)) {
        throw new Exception("Invalid reference format.");
    }

    $sql = "SELECT ref, status, created_at
            FROM kerrfairtex.enrollment_applications
            WHERE ref = $1";

    $result = pg_query_params($conn, $sql, [$ref]);

    if ($result === false) {
        throw new Exception("Database error");
    }

    $row = pg_fetch_assoc($result);

    if (!$row) {
        return [
            'success' => false,
            'found' => false,
            'error' => "Application not found."
        ];
    }

    // Map internal status to display status
    $displayStatus = '';
    switch ($row['status']) {
        case 'submitted':
            $displayStatus = 'Submitted';
            break;
        case 'under_review':
            $displayStatus = 'Under Review';
            break;
        case 'approved':
            $displayStatus = 'Approved';
            break;
        case 'rejected':
            $displayStatus = 'Rejected';
            break;
        case 'enrolled':
            $displayStatus = 'Enrolled';
            break;
        default:
            $displayStatus = ucfirst(str_replace('_', ' ', $row['status']));
    }
    
    return [
        'success' => true,
        'found' => true,
        'ref' => $row['ref'],
        'status' => $displayStatus,
    ];
}

// ---------------------------------------------------------------------------
// Rate limiting (in-memory per IP per hour)
// ---------------------------------------------------------------------------

$__submission_counts = [];

function checkRateLimit($ip) {
    global $__submission_counts;
    $key = $ip . ':' . date('Y-m-d:H');
    if (!isset($__submission_counts[$key])) {
        $__submission_counts[$key] = 0;
    }
    if ($__submission_counts[$key] >= 10) {
        throw new Exception("Rate limit exceeded. Please try again later.");
    }
    $__submission_counts[$key]++;
}

// ---------------------------------------------------------------------------
// Error logging
// ---------------------------------------------------------------------------

function logError($message, $context = []) {
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $message,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'action' => $_GET['action'] ?? 'unknown',
        'context' => $context
    ];
    error_log('[' . date('Y-m-d H:i:s') . '] Enrollment API: ' . json_encode($entry));
}

// ---------------------------------------------------------------------------
// Request handler
// ---------------------------------------------------------------------------

function handleRequest() {
    // Set JSON response headers
    header('Content-Type: application/json; charset=utf-8');

    // CORS: restrict to known frontend origin
    $allowedOrigin = getenv('CORS_ORIGIN') ?: 'https://smartk-122026.vercel.app';
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . $origin);
    } else {
        header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    }
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Max-Age: 86400');

    // Handle CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }

    // Initialize session
    if (session_status() === PHP_SESSION_NONE) {
        $cookieParams = [
            'lifetime' => 7200,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        session_set_cookie_params($cookieParams);
        session_start();
    }

    // Generate CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    try {
        $action = $_GET['action'] ?? '';

        switch ($action) {
            case 'config':
                $result = getEnrollmentConfiguration();
                http_response_code(200);
                echo json_encode($result);
                break;

            case 'status':
                if (empty($_GET['ref'])) {
                    throw new Exception("Missing required parameter: ref");
                }
                $result = getApplicationStatus($_GET['ref']);
                http_response_code($result['success'] ? 200 : 404);
                echo json_encode($result);
                break;

            case 'submit':
                // Rate limiting for unauthenticated public endpoint
                checkRateLimit($_SERVER['REMOTE_ADDR'] ?? 'unknown');

                // Parse JSON input
                $raw_input = file_get_contents('php://input');
                $input_data = json_decode($raw_input, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid JSON input'
                    ]);
                    exit(0);
                }

                if (!is_array($input_data)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Request body must be a JSON object'
                    ]);
                    exit(0);
                }

                // CSRF: validate token if provided in JSON body
                if (isset($input_data['csrf_token'])) {
                    if (empty($_SESSION['csrf_token']) ||
                        !hash_equals($_SESSION['csrf_token'], $input_data['csrf_token'])) {
                        throw new Exception('Invalid CSRF token');
                    }
                }

                $result = handleEnrollmentSubmission($input_data);
                http_response_code($result['success'] ? 201 : 400);
                echo json_encode($result);
                break;

            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Unknown action: " . $action . ". Supported: config, status, submit"
                ]);
                break;
        }

    } catch (Exception $e) {
        logError($e->getMessage());

        $code = 500;
        $msg = $e->getMessage();
        if (strpos($msg, 'Missing required') !== false ||
            strpos($msg, 'Invalid') !== false ||
            strpos($msg, 'No enrollment period') !== false ||
            strpos($msg, 'not currently open') !== false ||
            strpos($msg, 'Rate limit') !== false ||
            strpos($msg, 'Invalid JSON') !== false ||
            strpos($msg, 'JSON object') !== false) {
            $code = 400;
        }

        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $msg
        ]);
    }
}

// Execute the request handler
handleRequest();
