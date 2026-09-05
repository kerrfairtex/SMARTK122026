<?php
/**
 * BBNIHS Config Service
 *
 * Single source of truth for configuration values.
 * Replaces the scattered `Config()` function calls and `$RosarioModules` global
 * in the RosarioSIS application layer.
 *
 * Grounded evidence:
 *   - Generated runtime config: /var/www/html/config.inc.php (docker-entrypoint.sh:21-36)
 *   - Render env keys: render.yaml lines 12-30
 *   - Fallback host: docker-entrypoint.sh:7
 *   - Database connection: database.inc.php:25-103
 *   - Modules list: Warehouse.php:541-542 (RosarioModules = unserialize(Config('MODULES')))
 *   - Theme: render.yaml:25
 *
 * Phase 1 build — additive, isolated from RosarioSIS globals.
 */

declare(strict_types=1);

namespace BBNIHS\Config;

final class Config
{
    /** @var array<string,mixed> */
    private array $values;

    /**
     * @param array<string,mixed> $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
    }

    /**
     * Build the canonical BBNIHS config from environment.
     *
     * Source-of-truth mapping:
     *   DB_SERVER  -> database.host
     *   DB_PORT    -> database.port
     *   DB_NAME    -> database.name
     *   DB_USER    -> database.user
     *   DB_PASSWORD-> database.password
     *   SUPABASE_SSL_MODE -> database.sslmode
     *   THEME      -> app.theme
     *   DEFAULT_SYEAR -> app.default_syear
     */
    public static function fromEnv(): self
    {
        $server = getenv('DB_SERVER') ?: (getenv('DATABASE_SERVER') ?: 'db.ebyepweqwihdvjecrufk.supabase.co');
        $port   = getenv('DB_PORT')   ?: (getenv('DATABASE_PORT')   ?: '5432');
        $name   = getenv('DB_NAME')   ?: (getenv('DATABASE_NAME')   ?: 'postgres');
        $user   = getenv('DB_USER')   ?: (getenv('DATABASE_USER')   ?: 'postgres');
        $pass   = getenv('DB_PASSWORD') ?: (getenv('DATABASE_PASSWORD') ?: '');
        $ssl    = getenv('SUPABASE_SSL_MODE') ?: 'require';
        $theme  = getenv('THEME')      ?: 'FlatSIS';
        $syear  = getenv('DEFAULT_SYEAR') ?: '2026';

        return new self([
            'app' => [
                'name'         => 'SmartCampus K-12 / BBNIHS',
                'theme'        => $theme,
                'default_syear'=> (int)$syear,
                'locale'       => 'en_US.utf8',
            ],
            'database' => [
                'host'     => $server,
                'port'     => (int)$port,
                'name'     => $name,
                'user'     => $user,
                'password' => $pass,
                'sslmode'  => $ssl,
                // Source: database.inc.php:80
                // search_path MUST lead with the kerrfairtex schema
                'search_path' => 'kerrfairtex,public',
            ],
            'session' => [
                // Source: Warehouse.php:226 — name retained for transition only
                'name'      => 'RosarioSIS',
                'samesite'  => getenv('SESSION_COOKIE_SAMESITE') ?: 'Lax',
                'httponly'  => true,
                // Source: Warehouse.php:239-240 — conditional on HTTPS detection
                'secure'    => null, // resolved at runtime by Session service
                'lifetime'  => 0,
            ],
            'modules' => [
                // Source: Warehouse.php:524-539 — RosarioCoreModules
                'core' => [
                    'School_Setup','Students','Users','Scheduling','Grades',
                    'Attendance','Eligibility','Discipline','Accounting',
                    'Student_Billing','Food_Service','Resources','Custom',
                    'SmartCampus',
                ],
                // Source: Warehouse.php:558-559 — RosarioPlugins
                'core_plugins' => ['Content_Security_Policy','Moodle'],
            ],
            'paths' => [
                'docroot'        => '/var/www/html',
                'landing'        => 'public/index.php',
                'login_route'    => 'login.php',
                'rosariosis_root'=> 'index.php',
            ],
        ]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $cursor = $this->values;
        foreach ($segments as $seg) {
            if (!is_array($cursor) || !array_key_exists($seg, $cursor)) {
                return $default;
            }
            $cursor = $cursor[$seg];
        }
        return $cursor;
    }

    public function has(string $key): bool
    {
        return $this->get($key, self::MISSING) !== self::MISSING;
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    private const MISSING = '__BBNIHS_MISSING__';
}
