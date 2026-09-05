<?php
/**
 * BBNIHS CSRF test
 *
 * Source: Warehouse.php:275-340
 *   - bin2hex(openssl_random_pseudo_bytes(16))
 *   - hash_equals for comparison
 *   - X-CSRF-Token header support
 *   - Enforced for POST/PUT/PATCH/DELETE with modfunc or save/delete
 */

declare(strict_types=1);

use BBNIHS\Csrf\Csrf;
use BBNIHS\Audit\Audit;
use BBNIHS\Session\Session;
use BBNIHS\Config\Config;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    private function makeCsrf(): Csrf
    {
        $config = Config::fromEnv();
        $session = new Session($config);
        $audit = new Audit($session);
        return new Csrf($session, $audit);
    }

    public function testTokenIsGeneratedAndNonEmpty(): void
    {
        $csrf = $this->makeCsrf();
        $token = $csrf->token();
        $this->assertNotEmpty($token);
        $this->assertSame(32, strlen($token), '16 bytes hex-encoded = 32 chars');
    }

    public function testTokenIsStableWithinSession(): void
    {
        $csrf = $this->makeCsrf();
        $first = $csrf->token();
        $second = $csrf->token();
        $this->assertSame($first, $second);
    }

    public function testValidateAcceptsMatchingRequestToken(): void
    {
        $csrf = $this->makeCsrf();
        $token = $csrf->token();
        $this->assertTrue($csrf->validate(['HTTP_HOST' => ''], ['token' => $token]));
    }

    public function testValidateAcceptsMatchingHeaderToken(): void
    {
        $csrf = $this->makeCsrf();
        $token = $csrf->token();
        $this->assertTrue($csrf->validate(
            ['HTTP_X_CSRF_TOKEN' => $token],
            []
        ));
    }

    public function testValidateRejectsMissingToken(): void
    {
        $csrf = $this->makeCsrf();
        $this->assertFalse($csrf->validate([], []));
        $this->assertFalse($csrf->validate(['REQUEST_METHOD' => 'POST'], []));
    }

    public function testValidateRejectsWrongToken(): void
    {
        $csrf = $this->makeCsrf();
        $csrf->token();
        $this->assertFalse($csrf->validate([], ['token' => str_repeat('a', 32)]));
    }

    public function testShouldEnforceOnlyForStateChangingRequestsWithTrigger(): void
    {
        $csrf = $this->makeCsrf();
        $this->assertFalse($csrf->shouldEnforce(['REQUEST_METHOD' => 'GET'], []));
        $this->assertFalse($csrf->shouldEnforce(['REQUEST_METHOD' => 'HEAD'], []));
        $this->assertFalse($csrf->shouldEnforce(['REQUEST_METHOD' => 'POST'], []));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'POST'], ['modfunc' => 'save']));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'POST'], ['modfunc' => 'delete']));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'POST'], ['save' => '1']));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'POST'], ['delete' => '1']));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'PUT'], ['modfunc' => 'update']));
        $this->assertTrue($csrf->shouldEnforce(['REQUEST_METHOD' => 'DELETE'], []));
    }

    public function testEmitMetaTagContainsToken(): void
    {
        $csrf = $this->makeCsrf();
        $tag = $csrf->emitMetaTag();
        $this->assertStringContainsString('<meta name="token"', $tag);
        $this->assertStringContainsString($csrf->token(), $tag);
    }
}
