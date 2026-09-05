<?php
/**
 * BBNIHS Session test
 *
 * Source: Warehouse.php:220-267
 *   - session_name('RosarioSIS')   transitional
 *   - cookie https-only conditional on HTTPS detection
 *   - session_start()
 *   - CSRF token seeded once
 *   - DefaultSyear copied once
 */

declare(strict_types=1);

use BBNIHS\Session\Session;
use BBNIHS\Config\Config;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private function makeSession(): Session
    {
        return new Session(Config::fromEnv());
    }

    public function testGenerateTokenIs32Hex(): void
    {
        $s = $this->makeSession();
        $t = $s->generateToken();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $t);
    }

    public function testGenerateTokenIsRandom(): void
    {
        $s = $this->makeSession();
        $a = $s->generateToken();
        $b = $s->generateToken();
        $this->assertNotSame($a, $b);
    }

    public function testTokenEntropyIsHigh(): void
    {
        $s = $this->makeSession();
        $tokens = [];
        for ($i = 0; $i < 1000; $i++) {
            $tokens[] = $s->generateToken();
        }
        $unique = count(array_unique($tokens));
        $this->assertSame(1000, $unique, 'All 1000 generated tokens should be unique');
    }
}
