<?php
/**
 * BBNIHS Authentication Repository
 *
 * Data-access contract for authentication operations.
 * Wraps direct SQL queries against staff, students, student_enrollment,
 * and access_log tables.
 *
 * Source-grounded facts:
 *   - staff columns: USERNAME, PROFILE, STAFF_ID, LAST_LOGIN, FAILED_LOGIN, PASSWORD, SYEAR
 *   - students columns: STUDENT_ID, USERNAME, LAST_LOGIN, FAILED_LOGIN, PASSWORD, SYEAR
 *   - student_enrollment columns: STUDENT_ID, SYEAR, START_DATE, END_DATE
 *   - access_log columns: CREATED_AT, USER_AGENT, IP_ADDRESS, STATUS, SYEAR, USERNAME, PROFILE
 *   - Login lookup: UPPER(USERNAME) + SYEAR for staff, student_enrollment date check for students
 */

declare(strict_types=1);

namespace BBNIHS\Repositories;

use BBNIHS\DataAccess\DataAccess;

final class AuthenticationRepository
{
    public function __construct(private readonly DataAccess $data) {}

    /**
     * Find staff by username and school year.
     *
     * @return array<string, mixed>|null
     */
    public function findStaff(string $username, int $syear): ?array
    {
        $rows = $this->data->query(
            'SELECT USERNAME, PROFILE, STAFF_ID, LAST_LOGIN, FAILED_LOGIN, PASSWORD, SYEAR
             FROM "staff"
             WHERE SYEAR = :syear AND UPPER(USERNAME) = UPPER(:username)',
            [':syear' => $syear, ':username' => $username]
        );
        return $rows[0] ?? null;
    }

    /**
     * Find active student enrollment by username and school year.
     * Active = CURRENT_DATE >= START_DATE AND (END_DATE IS NULL OR CURRENT_DATE <= END_DATE)
     *
     * @return array<string, mixed>|null
     */
    public function findActiveStudent(string $username, int $syear): ?array
    {
        $rows = $this->data->query(
            'SELECT s.USERNAME, s.STUDENT_ID, s.LAST_LOGIN, s.FAILED_LOGIN, s.PASSWORD, se.START_DATE
             FROM "students" s
             INNER JOIN "student_enrollment" se ON se.STUDENT_ID = s.STUDENT_ID
             WHERE se.SYEAR = :syear
               AND CURRENT_DATE >= se.START_DATE
               AND (CURRENT_DATE <= se.END_DATE OR se.END_DATE IS NULL)
               AND UPPER(s.USERNAME) = UPPER(:username)',
            [':syear' => $syear, ':username' => $username]
        );
        return $rows[0] ?? null;
    }

    /**
     * Find any student enrollment by username and school year (ignoring active dates).
     *
     * @return array<string, mixed>|null
     */
    public function findAnyStudent(string $username, int $syear): ?array
    {
        $rows = $this->data->query(
            'SELECT s.USERNAME, s.STUDENT_ID, s.LAST_LOGIN, s.FAILED_LOGIN, s.PASSWORD, se.START_DATE
             FROM "students" s
             INNER JOIN "student_enrollment" se ON se.STUDENT_ID = s.STUDENT_ID
             WHERE se.SYEAR = :syear
               AND (CURRENT_DATE <= se.END_DATE OR se.END_DATE IS NULL)
               AND UPPER(s.USERNAME) = UPPER(:username)',
            [':syear' => $syear, ':username' => $username]
        );
        return $rows[0] ?? null;
    }

    /**
     * Update staff last login and clear failed login counter.
     */
    public function updateStaffLogin(int $staffId): void
    {
        $this->data->execute(
            'UPDATE "staff" SET LAST_LOGIN = CURRENT_TIMESTAMP, FAILED_LOGIN = NULL WHERE STAFF_ID = :id',
            [':id' => $staffId]
        );
    }

    /**
     * Update student last login and clear failed login counter.
     */
    public function updateStudentLogin(int $studentId): void
    {
        $this->data->execute(
            'UPDATE "students" SET LAST_LOGIN = CURRENT_TIMESTAMP, FAILED_LOGIN = NULL WHERE STUDENT_ID = :id',
            [':id' => $studentId]
        );
    }

    /**
     * Increment failed login counter for staff.
     */
    public function incrementStaffFailedLogin(string $username, int $syear): void
    {
        $this->data->execute(
            'UPDATE "staff" SET FAILED_LOGIN = COALESCE(FAILED_LOGIN,0) + 1 WHERE UPPER(USERNAME) = UPPER(:u) AND SYEAR = :s',
            [':u' => $username, ':s' => $syear]
        );
    }

    /**
     * Increment failed login counter for students.
     */
    public function incrementStudentFailedLogin(string $username): void
    {
        $this->data->execute(
            'UPDATE "students" SET FAILED_LOGIN = COALESCE(FAILED_LOGIN,0) + 1 WHERE UPPER(USERNAME) = UPPER(:u)',
            [':u' => $username]
        );
    }

    /**
     * Record login attempt in access_log.
     */
    public function recordAccessLog(array $data): void
    {
        $sql = 'INSERT INTO "access_log" (CREATED_AT, USER_AGENT, IP_ADDRESS, STATUS, SYEAR, USERNAME, PROFILE)
                VALUES (CURRENT_TIMESTAMP, :ua, :ip, :status, :syear, :username, :profile)';
        $this->data->execute($sql, [
            ':ua'      => substr((string)($data['user_agent'] ?? ''), 0, 500),
            ':ip'      => substr((string)($data['ip'] ?? ''), 0, 50),
            ':status'  => (string)($data['status'] ?? ''),
            ':syear'   => (int)($data['syear'] ?? 0),
            ':username'=> (string)($data['username'] ?? ''),
            ':profile' => (string)($data['profile'] ?? ''),
        ]);
    }

    /**
     * Check failed login ban within the last 10 minutes for a given IP + User-Agent.
     *
     * @return array{banned: bool, failed: int}
     */
    public function failedLoginBan(string $ip, string $userAgent): array
    {
        $rows = $this->data->query(
            'SELECT
                COUNT(CASE WHEN STATUS IS NULL OR STATUS = :banned THEN 1 END) AS BANNED_COUNT,
                COUNT(CASE WHEN STATUS IS NULL OR STATUS = :banned2 THEN 1 END) AS FAILED_COUNT
             FROM "access_log"
             WHERE CREATED_AT > (CURRENT_TIMESTAMP - INTERVAL :window)
               AND USER_AGENT = :ua
               AND IP_ADDRESS = :ip',
            [
                ':banned'  => 'B',
                ':banned2' => 'B',
                ':window'  => '10 minute',
                ':ua'      => $userAgent,
                ':ip'      => $ip,
            ]
        );
        $row = $rows[0] ?? ['BANNED_COUNT' => 0, 'FAILED_COUNT' => 0];
        return [
            'banned' => (int)($row['BANNED_COUNT'] ?? 0) > 0,
            'failed' => (int)($row['FAILED_COUNT'] ?? 0),
        ];
    }
}
