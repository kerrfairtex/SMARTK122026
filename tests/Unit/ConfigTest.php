<?php
/**
 * BBNIHS Config tests
 *
 * Source-grounded:
 *   - database.inc.php:80  -- search_path=kerrfairtex,public
 *   - render.yaml:25       THEME=FlatSIS
 *   - docker-entrypoint.sh:7  fallback DB_SERVER
 *   - Warehouse.php:226    session_name('RosarioSIS')
 *   - Warehouse.php:236    SESSION_COOKIE_SAMESITE default 'Lax'
 */

declare(strict_types=1);

use BBNIHS\Config\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testFromEnvUsesDefaultsWhenUnset(): void
    {
        putenv('DB_SERVER');
        putenv('DB_PORT');
        putenv('DB_NAME');
        putenv('DB_USER');
        putenv('DB_PASSWORD');
        putenv('SUPABASE_SSL_MODE');
        putenv('THEME');
        putenv('DEFAULT_SYEAR');

        $cfg = Config::fromEnv();

        $this->assertSame('db.ebyepweqwihdvjecrufk.supabase.co', $cfg->get('database.host'));
        $this->assertSame(5432, $cfg->get('database.port'));
        $this->assertSame('postgres', $cfg->get('database.name'));
        $this->assertSame('postgres', $cfg->get('database.user'));
        $this->assertSame('', $cfg->get('database.password'));
        $this->assertSame('require', $cfg->get('database.sslmode'));
    }

    public function testSearchPathIsKerrfairtex(): void
    {
        $cfg = Config::fromEnv();
        $this->assertSame('kerrfairtex,public', $cfg->get('database.search_path'));
    }

    public function testThemeFromEnv(): void
    {
        putenv('THEME=FlatSIS');
        $cfg = Config::fromEnv();
        $this->assertSame('FlatSIS', $cfg->get('app.theme'));
    }

    public function testDefaultSyear(): void
    {
        putenv('DEFAULT_SYEAR=2026');
        $cfg = Config::fromEnv();
        $this->assertSame(2026, $cfg->get('app.default_syear'));
    }

    public function testSessionCookieParams(): void
    {
        $cfg = Config::fromEnv();
        $this->assertSame('RosarioSIS', $cfg->get('session.name'));
        $this->assertTrue($cfg->get('session.httponly'));
        $this->assertSame('Lax', $cfg->get('session.samesite'));
    }

    public function testCoreModulesList(): void
    {
        $cfg = Config::fromEnv();
        $core = $cfg->get('modules.core');
        $this->assertContains('School_Setup', $core);
        $this->assertContains('Students', $core);
        $this->assertContains('SmartCampus', $core);
        $this->assertCount(14, $core);
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $cfg = Config::fromEnv();
        $this->assertNull($cfg->get('nope.nada.nil'));
        $this->assertSame('fallback', $cfg->get('nope.nada.nil', 'fallback'));
    }

    public function testHas(): void
    {
        $cfg = Config::fromEnv();
        $this->assertTrue($cfg->has('database.host'));
        $this->assertTrue($cfg->has('app.theme'));
        $this->assertFalse($cfg->has('nothing.here'));
    }
}
