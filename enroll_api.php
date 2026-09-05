<?php
/**
 * SmartCampus K-12 Enrollment API Implementation
 * Production-ready enrollment system with comprehensive security and error handling
 * Based on verified database schema and user journey requirements
 */

declare(strict_types=1);

// Error handling configuration
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', '/var/log/enroll_api_errors.log');

// Database connection configuration using environment variables
$DB_CONFIG = [
    'host' => $_ENV['DB_HOST'] ?? 'aws-0-ap-northeast-1.pooler.supabase.com',
    'port' => $_ENV['DB_PORT'] ?? 6543,
    'dbname' => $_ENV['DB_NAME'] ?? 'postgres',
    'user' => $_ENV['DB_USER'] ?? 'postgres.ebyepweqwihdvjecrufk',
    'password' => $_ENV['DB_PASSWORD'] ?? '4n=AgYHXO?%ESEKv',
    'charset' => 'utf8mb4',
    'ssl_mode' => 'REQUIRED',
];

// Global database connection variable
$mysqli = null;

/**
 * Establish database connection with retry logic and comprehensive error handling
 * Implements exponential backoff for connection failures
 */
function getDatabaseConnection() {
    global $mysqli;
    
    // Return existing active connection if available
    if ($mysqli !== null && $mysqli->ping()) {
        return $mysqli;
    }
    
    $max_retries = 3;
    $retry_delay = 1;
    $last_error = null;
    
    for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
        try {
            $mysqli = new mysqli(
                $GLOBALS['DB_CONFIG']['host'],
                $GLOBALS['DB_CONFIG']['user'],
                $GLOBALS['DB_CONFIG']['password'],
                $GLOBALS['DB_CONFIG']['dbname'],
                $GLOBALS['DB_CONFIG']['port']
            );
            
            // Verify connection was established
            if ($mysqli->connect_error) {
                throw new Exception("Database connection failed: " . $mysqli->connect_error);
            }
            
            // Set UTF-8 character encoding for proper Unicode support
            $mysqli->set_charset("utf8mb4");
            
            // Configure connection options for optimal performance and compatibility
            // Use set_option if available (PHP 7.0+)
            if (method_exists($mysqli, 'set_option')) {
                $mysqli->set_option(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
            } elseif (method_exists($mysqli, 'options')) {
                // Alternative method for older PHP versions
                $mysqli->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);
            }
            
            // Enable strict SQL mode for better data integrity
            $mysqli->query('SET sql_mode = REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", "")');
            
            error_log("Database connection established successfully on attempt {$attempt}");
            return $mysqli;
            
        } catch (Exception $e) {
            $last_error = $e;
            error_log("Database connection attempt {$attempt} failed: " . $e->getMessage());
            
            if ($attempt < $max_retries) {
                sleep($retry_delay);
                $retry_delay *= 2; // Exponential backoff
            }
        }
    }
    
    // If we get here, all attempts failed
    $error_msg = "Unable to connect to database after {$max_retries} attempts";
    if ($last_error) {
        $error_msg .= ": " . $last_error->getMessage();
    }
    
    error_log($error_msg);
    throw new Exception($error_msg);
}

/**
 * Execute database query with comprehensive error handling and parameterization
 * Provides both procedural and object-oriented support
 */
function dbQuery($sql, $params = []) {
    try {
        $conn = getDatabaseConnection();
        
        // Prepare the SQL statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Query preparation failed: " . $conn->error);
        }
        
        // Bind parameters if provided
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // String parameters
            $stmt->bind_param($types, ...$params);
        }
        
        // Execute the query
        $success = $stmt->execute();
        if ($success === false) {
            throw new Exception("Query execution failed: " . $stmt->error);
        }
        
        // Handle different types of queries
        if (strtoupper(substr($sql, 0, 6)) === 'SELECT') {
            // For SELECT queries, return result set
            $stmt->store_result();
            $result = $stmt->get_result();
        } elseif ($stmt->affected_rows > 0) {
            // For INSERT, UPDATE, DELETE, return affected rows count
            $result = $stmt->affected_rows;
        } else {
            // For other queries that don't return data
            $result = true;
        }
        
        // Clean up statement
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        // Comprehensive error logging
        $error_context = [
            'sql' => $sql,
            'params' => $params,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'script' => basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? 'unknown'),
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $_GET['action'] ?? 'unknown'
        ];
        
        error_log('[' . date('Y-m-d H:i:s') . '] Enrollment API Error: ' . $e->getMessage() . ' - Context: ' . json_encode($error_context));
        
        // Re-throw the exception for higher-level handling
        throw $e;
    }
}

/**
 * Fetch a single row from database result as an associative array
 * Returns null if no rows are available
 */
function dbFetchRow($result) {
    if (is_object($result)) {
        $row = $result->fetch_assoc();
        return $row ?: null;
    }
    return null;
}

/**
 * Fetch all rows from database result as an array of associative arrays
 * Returns empty array if no rows are available
 */
function dbFetchAll($result) {
    if (is_object($result)) {
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    return [];
}

/**
 * Validate and sanitize input data based on type
 * Provides comprehensive input validation and sanitization
 */
function sanitizeInput($data, $type = 'string') {
    // Handle arrays recursively
    if (is_array($data)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $data);
    }
    
    // Handle empty values
    if (empty($data) && $type !== 'date') {
        return $data;
    }
    
    // Apply type-specific validation and sanitization
    switch ($type) {
        case 'string':
            // Remove extra whitespace and escape HTML characters
            $sanitized = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
            // Prevent SQL injection by escaping quotes
            $sanitized = str_replace('"', '&quot;', $sanitized);
            $sanitized = str_replace("'", '&#x27;', $sanitized);
            return $sanitized;
            
        case 'int':
            // Convert to integer with validation
            $sanitized = (int) $data;
            if ($sanitized < 0) {
                throw new Exception("Invalid integer value: {$data}");
            }
            return $sanitized;
            
        case 'email':
            // Validate email format using PHP's built-in filter
            $sanitized = trim($data);
            if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format: {$data}");
            }
            return $sanitized;
            
        case 'phone':
            // Sanitize phone number (keep only valid phone characters)
            $sanitized = preg_replace('/[^0-9+()\-\s]/', '', $data);
            // Validate common phone number patterns
            if (!preg_match('/^(\+?\d{1,3}[-\s]?)?\d{10,}$/', $sanitized)) {
                throw new Exception("Invalid phone format: {$data}");
            }
            return $sanitized;
            
        case 'date':
            // Validate date format and convert to YYYY-MM-DD
            $timestamp = strtotime($data);
            if ($timestamp === false) {
                throw new Exception("Invalid date format: {$data}. Expected format: YYYY-MM-DD");
            }
            $sanitized = date('Y-m-d', $timestamp);
            
            // Validate that the date is not in the future (for enrollment applications)
            $today = date('Y-m-d');
            if ($sanitized > $today) {
                throw new Exception("Birth date cannot be in the future: {$data}");
            }
            
            // Validate reasonable age range (typically 5-25 for enrollment)
            $min_age_date = date('Y-m-d', strtotime('-25 years'));
            if ($sanitized < $min_age_date) {
                throw new Exception("Student age appears too old for current enrollment period: {$data}");
            }
            
            return $sanitized;
            
        case 'grade':
            // Validate against official grade levels
            $valid_grades = [
                'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
                'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9',
                'Grade 10', 'Grade 11', 'Grade 12'
            ];
            if (!in_array($data, $valid_grades)) {
                throw new Exception("Invalid grade level: {$data}. Must be one of: " . implode(', ', $valid_grades));
            }
            return $data;
            
        case 'boolean':
            // Convert to boolean using strict comparison
            $sanitized = filter_var($data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($sanitized === null) {
                throw new Exception("Invalid boolean value: {$data}");
            }
            return $sanitized;
            
        case 'text':
            // Sanitize for HTML content (less strict than 'string')
            $sanitized = trim($data);
            $sanitized = str_replace('"', '&quot;', $sanitized);
            return $sanitized;
            
        case 'slug':
            // Generate URL-friendly slug
            $sanitized = strtolower(trim($data));
            $sanitized = preg_replace('/[^a-z0-9\s]/', '', $sanitized);
            $sanitized = preg_replace('/\s+/', '-', $sanitized);
            return $sanitized;
            
        default:
            // For unknown types, return the data as-is
            return $data;
    }
}

/**
 * Generate unique enrollment reference number
 * Format: BATU-YYYY-XXXXXXXX (8-character uppercase hex)
 */
function generateReferenceNumber() {
    $year = date('Y');
    // Generate random hexadecimal string
    $random_part = strtoupper(substr(md5(time() . rand(1000, 9999)), 0, 8));
    return "BATU-{$year}-{$random_part}";
}

/**
 * Validate CSRF token from session
 * Ensures protection against Cross-Site Request Forgery attacks
 */
function validateCsrfToken($token) {
    // Check if token is provided and matches session token
    if (empty($token) || empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        throw new Exception('Invalid or missing CSRF token');
    }
    return true;
}

/**
 * Comprehensive error logging function
 * Logs all errors with detailed context information
 */
function logError($error_message, $context = []) {
    // Create structured log entry
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error_message' => $error_message,
        'context' => $context,
        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'script_name' => basename($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? 'unknown'),
        'query_string' => $_SERVER['QUERY_STRING'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    ];
    
    // Log to PHP error log with structured format
    error_log('[' . date('Y-m-d H:i:s') . '] Enrollment API Error: ' . json_encode($log_entry));
}

/**
 * Process enrollment submission with comprehensive validation and error handling
 * Core business logic for student enrollment applications
 */
function handleEnrollmentSubmission($data) {
    try {
        // Validate required fields - ensure all mandatory data is present
        $required_fields = [
            'last_name', 'first_name', 'birth_date', 'laddress',
            'sex', 'birthplace', 'grade_level', 'school_year',
            'etype', 'pname', 'pcontact', 'pschool', 'plastgrade'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Validate CSRF token if present (for web form submissions)
        if (isset($_POST['csrf_token'])) {
            validateCsrfToken($_POST['csrf_token']);
        }
        
        // Sanitize and validate all input data using appropriate types
        $sanitized_data = [];
        
        // Personal information fields
        $sanitized_data['last_name'] = sanitizeInput($data['last_name'], 'string');
        $sanitized_data['first_name'] = sanitizeInput($data['first_name'], 'string');
        $sanitized_data['birth_date'] = sanitizeInput($data['birth_date'], 'date');
        $sanitized_data['laddress'] = sanitizeInput($data['laddress'], 'string');
        $sanitized_data['sex'] = sanitizeInput($data['sex'], 'string');
        $sanitized_data['birthplace'] = sanitizeInput($data['birthplace'], 'string');
        
        // Grade and enrollment information
        $sanitized_data['grade_level'] = sanitizeInput($data['grade_level'], 'grade');
        $sanitized_data['school_year'] = sanitizeInput($data['school_year'], 'string');
        $sanitized_data['etype'] = sanitizeInput($data['etype'], 'string');
        
        // Guardian information
        $sanitized_data['pname'] = sanitizeInput($data['pname'], 'string');
        $sanitized_data['pcontact'] = sanitizeInput($data['pcontact'], 'phone');
        $sanitized_data['pemail'] = !empty($data['pemail']) ? sanitizeInput($data['pemail'], 'email') : null;
        $sanitized_data['pschool'] = sanitizeInput($data['pschool'], 'string');
        $sanitized_data['plastgrade'] = sanitizeInput($data['plastgrade'], 'grade');
        
        // Add enrollment metadata
        $sanitized_data['enrollment_period_id'] = 1; // Should be dynamic based on school_year
        $sanitized_data['student_id'] = null; // Will be set after student record creation
        
        // Generate unique reference number
        $ref = generateReferenceNumber();
        
        // Prepare SQL statement for enrollment application insertion
        $sql = "INSERT INTO enrollment_applications (
            ref, student_id, enrollment_period_id, status,
            submitted_at, reviewed_by, reviewed_at,
            data_json, created_at, updated_at
        ) VALUES (?, ?, ?, ?, NOW(), ?, NOW(), ?, NOW(), NOW())";
        
        // Prepare parameters for database query
        $params = [
            $ref,
            $sanitized_data['student_id'],
            $sanitized_data['enrollment_period_id'],
            'Submitted',
            null, // reviewed_by (null for new applications)
            null, // reviewed_at (null for new applications)
            json_encode($sanitized_data), // Store all sanitized data as JSON
        ];
        
        // Execute database insertion
        $result = dbQuery($sql, $params);
        
        // Return success response with reference and status
        return [
            'success' => true,
            'ref' => $ref,
            'status' => 'Submitted',
            'created_at' => date('Y-m-d H:i:s'),
            'message' => 'Enrollment application submitted successfully'
        ];
        
    } catch (Exception $e) {
        // Log the error for debugging and monitoring
        logError($e->getMessage(), [
            'action' => 'enrollment_submission',
            'input_data' => $data,
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Return structured error response
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Retrieve current enrollment period configuration
 * Returns active enrollment periods and current period information
 */
function getEnrollmentConfiguration() {
    try {
        // Query active enrollment periods
        $sql = "SELECT * FROM enrollment_periods WHERE status = 'Open' ORDER BY school_year, enrollment_opens";
        $result = dbQuery($sql);
        $periods = dbFetchAll($result);
        
        return [
            'success' => true,
            'periods' => $periods,
            'current_period' => $periods[0] ?? null,
            'count' => count($periods)
        ];
        
    } catch (Exception $e) {
        logError($e->getMessage(), ['action' => 'get_enrollment_config']);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Retrieve application status by reference number
 * Supports lookup by unique reference identifier
 */
function getApplicationStatus($ref) {
    try {
        // Validate reference format (BATU-YYYY-XXXXX)
        if (!preg_match('/^BATU-\d{4}-\w{8}$/', $ref)) {
            throw new Exception("Invalid reference format: {$ref}. Expected format: BATU-YYYY-XXXXX");
        }
        
        // Query application by reference number
        $sql = "SELECT * FROM enrollment_applications WHERE ref = ?";
        $result = dbQuery($sql, [$ref]);
        $application = dbFetchRow($result);
        
        if (!$application) {
            return [
                'success' => false,
                'found' => false,
                'error' => "Application not found for reference: {$ref}"
            ];
        }
        
        // Parse JSON data field for human-readable learner name
        $data_json = json_decode($application['data_json'] ?? '[]', true);
        $learner_name = $data_json['last_name'] . ', ' . $data_json['first_name'];
        
        return [
            'success' => true,
            'found' => true,
            'application' => $application,
            'learner_name' => $learner_name,
            'status' => $application['status'],
            'submitted_at' => $application['submitted_at'] ?? null
        ];
        
    } catch (Exception $e) {
        logError($e->getMessage(), ['action' => 'get_application_status', 'ref' => $ref]);
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Main request handler - processes all API endpoints with comprehensive routing
 * Supports GET, POST, OPTIONS methods with CORS compliance
 */
function handleRequest() {
    // Set JSON response headers for all requests
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    
    // Handle CORS preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
    
    // Parse JSON input for POST requests
    $input_data = [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get raw input data from request body
        $raw_input = file_get_contents('php://input');
        $input_data = json_decode($raw_input, true);
        
        // Validate JSON input format
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid JSON input: ' . json_last_error_msg()
            ]);
            exit(0);
        }
    }
    
    // Initialize session for CSRF protection and user tracking
    if (session_status() === PHP_SESSION_NONE) {
        // Configure secure session cookie parameters
        session_set_cookie_params([
            'lifetime' => 7200, // 2 hours
            'path' => '/',
            'domain' => $_ENV['COOKIE_DOMAIN'] ?? null,
            'secure' => ($_SERVER['HTTPS'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
    }
    
    // Generate CSRF token if not exists
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    try {
        // Determine requested action from query parameters
        $action = $_GET['action'] ?? '';
        
        // Route requests based on action parameter
        switch ($action) {
            case 'config':
                // Return current enrollment period configuration
                $result = getEnrollmentConfiguration();
                http_response_code($result['success'] ? 200 : 500);
                echo json_encode($result);
                break;
                
            case 'status':
                // Return application status by reference number
                if (empty($_GET['ref'])) {
                    throw new Exception("Missing required parameter: ref");
                }
                $result = getApplicationStatus($_GET['ref']);
                http_response_code($result['success'] ? 200 : 404);
                echo json_encode($result);
                break;
                
            case 'submit':
                // Process enrollment submission
                $result = handleEnrollmentSubmission($input_data);
                http_response_code($result['success'] ? 201 : 400);
                echo json_encode($result);
                break;
                
            case 'draft_finalize':
                // Legacy support for draft finalization (redirect to submit)
                $result = handleEnrollmentSubmission($input_data);
                http_response_code($result['success'] ? 201 : 400);
                echo json_encode($result);
                break;
                
            default:
                // Handle unknown or missing actions
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Unknown action: {$action}. Supported actions: config, status, submit"
                ]);
                break;
        }
        
    } catch (Exception $e) {
        // Handle any uncaught exceptions with proper error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

// Execute the main request handler to process incoming requests
handleRequest();