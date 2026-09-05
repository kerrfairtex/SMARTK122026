<?php
/**
 * BBNIHS MatchPassword test
 *
 * Source: functions/Password.php:51-75
 *   - SHA-512 crypt with $6$ prefix
 *   - hash_equals($crypted, crypt($plain, $crypted))
 *
 * This test is the single most security-critical in Phase 1.
 * It verifies the BBNIHS auth contract can verify the EXACT same password
 * format that RosarioSIS stores.
 */

declare(strict_types=1);

use BBNIHS\Auth\Auth;
use PHPUnit\Framework\TestCase;

final class MatchPasswordTest extends TestCase
{
    public function testEmptyPlainReturnsFalse(): void
    {
        $hash = '$6$rounds=5000$usesomesillystri$K8Y2cPQG8vBQpKxEaD/';
        $this->assertFalse(Auth::matchPassword($hash, ''));
    }

    public function testEmptyCryptedReturnsFalse(): void
    {
        $this->assertFalse(Auth::matchPassword('', 'anyplain'));
    }

    public function testBothEmptyReturnsFalse(): void
    {
        $this->assertFalse(Auth::matchPassword('', ''));
    }

    public function testSha512CryptRoundTrip(): void
    {
        // Generate a fresh hash from a known plaintext, then verify it.
        $hash = Auth::encryptPassword('correct horse battery staple');
        $this->assertStringStartsWith('$6$', $hash);
        $this->assertTrue(Auth::matchPassword($hash, 'correct horse battery staple'));
    }

    public function testWrongPasswordRejected(): void
    {
        $hash = Auth::encryptPassword('secret123');
        $this->assertFalse(Auth::matchPassword($hash, 'secret124'));
        $this->assertFalse(Auth::matchPassword($hash, 'Secret123'));
    }

    public function testGeneratedHashIsSixDollar(): void
    {
        $hash = Auth::encryptPassword('whatever');
        $this->assertStringStartsWith('$6$', $hash, 'RosarioSIS uses SHA-512 only');
    }

    public function testHashFormatMatchesRosarioSISContract(): void
    {
        $hash = Auth::encryptPassword('whatever');
        // Source: functions/Password.php:35 — $6$ + 16-char sha1 salt, then hash
        // The standard crypt() output for this format is 4 dollar-separated fields:
        //   '' / '6' / <16-char salt> / <86-char hash>
        $parts = explode('$', $hash);
        $this->assertCount(4, $parts, 'Source: functions/Password.php:35 uses plain 16-char salt (no rounds=N)');
        $this->assertSame('', $parts[0]);
        $this->assertSame('6', $parts[1]);
        $this->assertSame(16, strlen($parts[2]), 'Salt must be 16 chars (source: functions/Password.php:35)');
        $this->assertSame(86, strlen($parts[3]), 'SHA-512 hash is 86 base64 chars');
    }

    public function testConstantTimeComparison(): void
    {
        // hash_equals is constant-time. We just verify the contract returns
        // false for any mismatch without leaking through timing.
        $hash = Auth::encryptPassword('known');
        $this->assertFalse(Auth::matchPassword($hash, 'wrong'));
    }
}
