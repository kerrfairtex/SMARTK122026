<?php
/**
 * Integration tests for BBNIHS SmartCampus enrollment and database schema.
 *
 * Run: vendor/bin/phpunit tests/Integration/SmartCampusEnrollmentTest.php
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

class SmartCampusEnrollmentTest extends TestCase
{
    private static $conn = null;
    private static $dbPassword = '4n=AgYHXO?%ESEKv';
    private static $dbHost = 'aws-0-ap-northeast-1.pooler.supabase.com';
    private static $dbPort = '6543';
    private static $dbUser = 'postgres.ebyepweqwihdvjecrufk';
    private static $dbName = 'postgres';

    public static function setUpBeforeClass(): void
    {
        $connStr = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s options=\'--search_path=kerrfairtex,public\' sslmode=require',
            self::$dbHost,
            self::$dbPort,
            self::$dbName,
            self::$dbUser,
            self::$dbPassword
        );
        self::$conn = pg_connect($connStr);
        if (!self::$conn) {
            self::markTestSkipped('Cannot connect to database: ' . pg_last_error());
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$conn) {
            pg_close(self::$conn);
        }
    }

    public function testBBNIHSTablesExist()
    {
        $tables = ['enrollment_periods', 'enrollment_applications', 'enrollment_drafts', 'about_content', 'bbnihs_access_log'];
        foreach ($tables as $table) {
            $res = @pg_query(self::$conn, "SELECT to_regclass('kerrfairtex.$table')");
            $this->assertNotFalse($res, "Query failed for table $table");
            $row = pg_fetch_result($res, 0, 0);
            $this->assertNotNull($row, "Table kerrfairtex.$table does not exist");
            $this->assertNotEmpty($row, "Table kerrfairtex.$table does not exist");
        }
    }

    public function testEnrollmentPeriodConfigExists()
    {
        $res = @pg_query(self::$conn, "SELECT * FROM kerrfairtex.enrollment_periods ORDER BY updated_at DESC LIMIT 1");
        $this->assertNotFalse($res);
        $row = pg_fetch_assoc($res);
        $this->assertNotEmpty($row, 'No enrollment period configured');
        $this->assertEquals('2026-2027', $row['school_year']);
        $this->assertNotEmpty($row['enrollment_opens']);
        $this->assertNotEmpty($row['enrollment_closes']);
    }

    public function testStaffUserExists()
    {
        $res = @pg_query(self::$conn, "SELECT staff_id, username, profile FROM kerrfairtex.staff WHERE profile = 'admin' LIMIT 1");
        $this->assertNotFalse($res);
        $row = pg_fetch_assoc($res);
        $this->assertNotEmpty($row, 'No admin staff user found');
        $this->assertEquals('admin', $row['profile']);
    }

    public function testStudentUserExists()
    {
        $res = @pg_query(self::$conn, "SELECT student_id, username FROM kerrfairtex.students LIMIT 1");
        $this->assertNotFalse($res);
        $row = pg_fetch_assoc($res);
        $this->assertNotEmpty($row, 'No student user found');
    }

    public function testInsertApplication()
    {
        $ref = 'BATU-' . date('Y') . '-' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sql = "INSERT INTO kerrfairtex.enrollment_applications (ref, learner_name, birth_date, sex, grade_level, school_year, enrollment_type, status) VALUES ('" . pg_escape_string(self::$conn, $ref) . "', 'Test Student', '2010-01-01', 'Male', 'Grade 1', '2026-2027', 'New', 'Submitted')";
        $r = @pg_query(self::$conn, $sql);
        $this->assertNotFalse($r, 'Failed to insert test application: ' . pg_last_error(self::$conn));

        $res = @pg_query(self::$conn, "SELECT ref FROM kerrfairtex.enrollment_applications WHERE ref = '" . pg_escape_string(self::$conn, $ref) . "'");
        $row = pg_fetch_assoc($res);
        $this->assertEquals($ref, $row['ref']);

        // Cleanup
        @pg_query(self::$conn, "DELETE FROM kerrfairtex.enrollment_applications WHERE ref = '" . pg_escape_string(self::$conn, $ref) . "'");
    }

    public function testInsertDraft()
    {
        $token = bin2hex(random_bytes(32));
        $payload = ['test' => true];
        $expires = date('Y-m-d H:i:s', time() + 14 * 86400);
        $sql = "INSERT INTO kerrfairtex.enrollment_drafts (token, payload, status, expires_at) VALUES ('" . pg_escape_string(self::$conn, $token) . "', '" . pg_escape_string(self::$conn, json_encode($payload)) . "'::jsonb, 'active', '$expires')";
        $r = @pg_query(self::$conn, $sql);
        $this->assertNotFalse($r, 'Failed to insert test draft: ' . pg_last_error(self::$conn));

        $res = @pg_query(self::$conn, "SELECT token FROM kerrfairtex.enrollment_drafts WHERE token = '" . pg_escape_string(self::$conn, $token) . "'");
        $row = pg_fetch_assoc($res);
        $this->assertEquals($token, $row['token']);

        // Cleanup
        @pg_query(self::$conn, "DELETE FROM kerrfairtex.enrollment_drafts WHERE token = '" . pg_escape_string(self::$conn, $token) . "'");
    }
}
