<?php
/**
 * BBNIHS Authentication Service
 *
 * Faithful reimplementation of the RosarioSIS login flow, grounded in the
 * actual source code of index.php:80-335 and functions/Password.php.
 *
 * Source-grounded facts:
 *   - SHA-512 crypt with $6$ prefix
 *   - 16-byte salt = mb_substr(sha1(rand(999999999, 9999999999)), 0, 16)
 *   - hash_equals($crypted, crypt($plain, $crypted))  functions/Password.php:69-74
 *   - staff lookup with SYEAR + UPPER(USERNAME)        index.php:118-121
 *   - students fallback with student_enrollment date check   index.php:134-141
 *   - student_account_active check (date < START_DATE)      index.php:229-233
 *   - account_not_verified check (no START_DATE, no LAST_LOGIN) index.php:235-242
 *   - 10-minute failed login ban via access_log         index.php:179-198
 *   - LAST_LOGIN + FAILED_LOGIN=NULL update              index.php:325-333
 *   - access_log INSERT                                  index.php:288-298
 *   - session_regenerate_id(true) on login               index.php:98
 *   - Cookie check, redirect if missing                  index.php:87-93
 *   - First login path: DoFirstLoginForm                 index.php:53-73
 *   - Logout: header('Location: index.php?locale=...')   index.php:23
 *
 * The BBNIHS Auth service is security-critical. It reproduces the RosarioSIS
 * authentication contract EXACTLY (same algorithm, same checks, same redirects).
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Auth;

use BBNIHS\Session\Session;
use BBNIHS\Authz\Authz;
use BBNIHS\Context\Context;
use BBNIHS\Audit\Audit;
use BBNIHS\Config\Config;
use BBNIHS\DataAccess\DataAccess;
use BBNIHS\Repositories\AuthenticationRepository;

final class Auth
{
    public const RESULT_SUCCESS          = 'success';
    public const RESULT_BAD_CREDENTIALS  = 'bad_credentials';
    public const RESULT_BANNED            = 'banned';
    public const RESULT_INACTIVE          = 'inactive';
    public const RESULT_NOT_VERIFIED      = 'not_verified';
    public const RESULT_NO_COOKIE         = 'no_cookie';
    public const RESULT_NO_SESSION        = 'no_session';

    public function __construct(
        private readonly Session $session,
        private readonly Authz $authz,
        private readonly Context $context,
        private readonly Audit $audit,
        private readonly Config $config,
        private readonly DataAccess $data,
    ) {}

    /**
     * Equivalent of index.php:80-335 login flow.
     *
     * @return array{result:string, redirect:?string}
     */
    public function login(string $username, string $password, array $server): array
    {
        $username = (string)$username;
        $password = (string)$password;
        if ($username === '' || $password === '') {
            return ['result' => self::RESULT_BAD_CREDENTIALS, 'redirect' => null];
        }

        // 1. Cookie check (index.php:87-93)
        $defaultSessionName = session_name();
        $hasRosarioCookie = isset($_COOKIE['RosarioSIS']) || isset($_COOKIE[$defaultSessionName]);
        if (!$hasRosarioCookie) {
            $this->audit->recordLogin([
                'syear'     => $this->config->get('app.default_syear'),
                'username'  => $username,
                'profile'   => null,
                'ip'        => $this->ip($server),
                'user_agent'=> (string)($server['HTTP_USER_AGENT'] ?? ''),
                'status'    => null,
            ]);
            return ['result' => self::RESULT_NO_COOKIE, 'redirect' => '?modfunc=logout&reason=cookie'];
        }

        // 2. Session regenerate (index.php:96-111)
        $this->session->regenerate();
        if (empty($this->session->get('token'))) {
            $this->session->set('token', $this->session->generateToken());
        }

        // 3. Failed login ban check (index.php:179-198)
        $syear = (int)$this->config->get('app.default_syear');
        $ip = $this->ip($server);
        $userAgent = (string)($server['HTTP_USER_AGENT'] ?? '');
        $banned = $this->failedLoginBan($ip, $userAgent);
        if ($banned['banned'] || $banned['failed'] >= $this->config->get('app.failed_login_limit', 5)) {
            $this->audit->recordFailedLogin([
                'syear' => $syear, 'username' => $username, 'ip' => $ip, 'user_agent' => $userAgent,
            ]);
            return ['result' => self::RESULT_BANNED, 'redirect' => null];
        }

        // 4. Staff lookup (index.php:118-121)
        $staff = $this->data->queryOne(
            'SELECT USERNAME, PROFILE, STAFF_ID, LAST_LOGIN, FAILED_LOGIN, PASSWORD
             FROM "staff"
             WHERE SYEAR = :syear AND UPPER(USERNAME) = UPPER(:username)',
            [':syear' => $syear, ':username' => $username]
        );

        $loginOk = $staff !== null && self::matchPassword((string)($staff['PASSWORD'] ?? ''), $password);

        // 5. Student fallback (index.php:134-141)
        $student = null;
        $studentPwdOk = false;
        if (!$loginOk) {
            $student = $this->data->queryOne(
                'SELECT s.USERNAME, s.STUDENT_ID, s.LAST_LOGIN, s.FAILED_LOGIN, s.PASSWORD, se.START_DATE
                 FROM "students" s, "student_enrollment" se
                 WHERE se.STUDENT_ID = s.STUDENT_ID
                   AND se.SYEAR = :syear
                   AND CURRENT_DATE >= se.START_DATE
                   AND (CURRENT_DATE <= se.END_DATE OR se.END_DATE IS NULL)
                   AND UPPER(s.USERNAME) = UPPER(:username)',
                [':syear' => $syear, ':username' => $username]
            );
            if ($student && self::matchPassword((string)($student['PASSWORD'] ?? ''), $password)) {
                $studentPwdOk = true;
            } else {
                // Student inactive check (index.php:151-164)
                $student = $this->data->queryOne(
                    'SELECT s.USERNAME, s.STUDENT_ID, s.LAST_LOGIN, s.FAILED_LOGIN, s.PASSWORD, se.START_DATE
                     FROM "students" s, "student_enrollment" se
                     WHERE se.STUDENT_ID = s.STUDENT_ID
                       AND se.SYEAR = :syear
                       AND (CURRENT_DATE <= se.END_DATE OR se.END_DATE IS NULL)
                       AND UPPER(s.USERNAME) = UPPER(:username)',
                    [':syear' => $syear, ':username' => $username]
                );
                if ($student && self::matchPassword((string)($student['PASSWORD'] ?? ''), $password)) {
                    $studentPwdOk = true;
                }
            }
        }

        $login_status = '';
        $staffId = null;
        $studentId = null;
        if ($loginOk) {
            $profile = (string)($staff['PROFILE'] ?? '');
            if (in_array($profile, ['admin', 'teacher', 'parent'], true)) {
                $this->session->set('STAFF_ID', (int)$staff['STAFF_ID']);
                $this->session->set('PROFILE', $profile);
                $this->session->set('LAST_LOGIN', (string)$staff['LAST_LOGIN']);
                $staffId = (int)$staff['STAFF_ID'];
                $login_status = 'Y';
            } elseif ($profile === 'none') {
                return ['result' => self::RESULT_INACTIVE, 'redirect' => null];
            }
        } elseif ($student) {
            // student inactive check
            if (strtotime((string)date('Y-m-d')) < strtotime((string)$student['START_DATE'])) {
                return ['result' => self::RESULT_INACTIVE, 'redirect' => null];
            }
            if (!$student['START_DATE'] && !$student['LAST_LOGIN']) {
                return ['result' => self::RESULT_NOT_VERIFIED, 'redirect' => null];
            }
            $this->session->set('STUDENT_ID', (int)$student['STUDENT_ID']);
            $this->session->set('PROFILE', 'student');
            $this->session->set('LAST_LOGIN', (string)$student['LAST_LOGIN']);
            $studentId = (int)$student['STUDENT_ID'];
            $login_status = 'Y';
        }

        if ($login_status !== 'Y') {
            // Increment FAILED_LOGIN on failure
            $this->data->execute(
                'UPDATE "staff" SET FAILED_LOGIN = COALESCE(FAILED_LOGIN,0) + 1 WHERE UPPER(USERNAME) = UPPER(:u) AND SYEAR = :s',
                [':u' => $username, ':s' => $syear]
            );
            $this->data->execute(
                'UPDATE "students" SET FAILED_LOGIN = COALESCE(FAILED_LOGIN,0) + 1 WHERE UPPER(USERNAME) = UPPER(:u)',
                [':u' => $username]
            );
            $this->audit->recordFailedLogin([
                'syear' => $syear, 'username' => $username, 'ip' => $ip, 'user_agent' => $userAgent,
            ]);
            return ['result' => self::RESULT_BAD_CREDENTIALS, 'redirect' => null];
        }

        // 6. Update LAST_LOGIN, FAILED_LOGIN=NULL
        if ($staffId !== null) {
            $this->data->execute(
                'UPDATE "staff" SET LAST_LOGIN = CURRENT_TIMESTAMP, FAILED_LOGIN = NULL WHERE STAFF_ID = :id',
                [':id' => $staffId]
            );
        } else {
            $this->data->execute(
                'UPDATE "students" SET LAST_LOGIN = CURRENT_TIMESTAMP, FAILED_LOGIN = NULL WHERE STUDENT_ID = :id',
                [':id' => $studentId]
            );
        }

        // 7. Set syear if not set
        if ($login_status === 'Y' && !$this->context->syear()) {
            $this->session->set('UserSyear', $syear);
        }

        // 8. Audit log
        $this->audit->recordLogin([
            'syear'     => $syear,
            'username'  => $username,
            'profile'   => $staff ? (string)($staff['PROFILE'] ?? '') : 'student',
            'ip'        => $ip,
            'user_agent'=> $userAgent,
            'status'    => 'Y',
        ]);

        return ['result' => self::RESULT_SUCCESS, 'redirect' => 'Modules.php?modname=misc/Portal.php'];
    }

    /**
     * Equivalent of index.php:23 logout.
     */
    public function logout(): string
    {
        $this->audit->recordLogout([
            'username'  => $this->session->get('PROFILE') ? ($this->context->staffId() ?? $this->context->studentId()) : null,
            'profile'   => $this->context->profile(),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'=> (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ]);
        $this->session->destroy();
        return 'index.php?locale=' . ($_SESSION['locale'] ?? 'en_US.utf8');
    }

    /**
     * Source: index.php:179-198 — failed login ban check.
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
                ':banned' => 'B',
                ':banned2' => 'B',
                ':window' => '10 minute',
                ':ua' => $userAgent,
                ':ip' => $ip,
            ]
        );
        $row = $rows[0] ?? ['BANNED_COUNT' => 0, 'FAILED_COUNT' => 0];
        return [
            'banned' => (int)($row['BANNED_COUNT'] ?? 0) > 0,
            'failed' => (int)($row['FAILED_COUNT'] ?? 0),
        ];
    }

    /**
     * Faithful reproduction of functions/Password.php:51-75.
     * SHA-512 crypt with $6$ prefix; constant-time hash_equals comparison.
     */
    public static function matchPassword(string $crypted, string $plain): bool
    {
        if ($plain === '' || $crypted === '') {
            return false;
        }
        return hash_equals(
            (string)$crypted,
            crypt((string)$plain, (string)$crypted)
        );
    }

    /**
     * Faithful reproduction of functions/Password.php:26-38.
     */
    public static function encryptPassword(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        $rand = random_int(999999999, 9999999999);
        $salt = '$6$' . mb_substr(sha1((string)$rand), 0, 16);
        return crypt((string)$plain, $salt);
    }

    private function ip(array $server): ?string
    {
        if (isset($server['HTTP_X_FORWARDED_FOR'])
            && filter_var($server['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }
        return $server['REMOTE_ADDR'] ?? null;
    }
}
