<?php
/**
 * BBNIHS Context Service
 *
 * Replaces Warehouse.php:788-791 emitted values and the UserSchool /
 * UserSyear / UserMP family from functions/Current.php.
 *
 * Source-grounded facts:
 *   - JS template injects:  Warehouse.php:788-791
 *     studentId, staffId, school, mp
 *   - UserSchool / UserSyear / UserMP  functions/Current.php
 *   - Auto-redirect if no UserSchool  CHANGES_V9_10.md:383
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS\Context;

use BBNIHS\Session\Session;
use BBNIHS\Config\Config;

final class Context
{
    public function __construct(
        private readonly Session $session,
        private readonly Config $config,
    ) {}

    public function school(): ?int
    {
        $v = $this->session->get('UserSchool');
        return is_numeric($v) ? (int)$v : null;
    }

    public function syear(): ?int
    {
        $v = $this->session->get('UserSyear');
        if (is_numeric($v)) { return (int)$v; }
        // Source: Warehouse.php:269-273 — DefaultSyear seed
        $d = $this->config->get('app.default_syear');
        return is_numeric($d) ? (int)$d : null;
    }

    public function mp(): ?int
    {
        $v = $this->session->get('UserMP');
        return is_numeric($v) ? (int)$v : null;
    }

    public function staffId(): ?int
    {
        $v = $this->session->get('STAFF_ID');
        return is_numeric($v) ? (int)$v : null;
    }

    public function studentId(): ?int
    {
        $v = $this->session->get('STUDENT_ID');
        return is_numeric($v) ? (int)$v : null;
    }

    public function profile(): ?string
    {
        $sid = $this->staffId();
        $tid = $this->studentId();
        if ($sid) {
            $v = $this->session->get('PROFILE');
            return is_string($v) ? $v : null;
        }
        if ($tid) {
            return 'student';
        }
        return null;
    }

    public function setSchool(int $id): void
    {
        $this->session->set('UserSchool', $id);
    }

    public function setSyear(int $syear): void
    {
        $this->session->set('UserSyear', $syear);
    }

    public function setMp(int $mp): void
    {
        $this->session->set('UserMP', $mp);
    }

    /**
     * @return array{school: ?int, syear: ?int, mp: ?int, staffId: ?int, studentId: ?int, profile: ?string}
     */
    public function toArray(): array
    {
        return [
            'school'    => $this->school(),
            'syear'     => $this->syear(),
            'mp'        => $this->mp(),
            'staffId'   => $this->staffId(),
            'studentId' => $this->studentId(),
            'profile'   => $this->profile(),
        ];
    }
}
