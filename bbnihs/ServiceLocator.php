<?php
/**
 * BBNIHS Service Locator
 *
 * Lightweight, file-based DI. No Pimple / Symfony container required.
 * Each service is constructed lazily on first request and cached.
 *
 * Phase 1 build — additive.
 */

declare(strict_types=1);

namespace BBNIHS;

final class ServiceLocator
{
    /** @var array<string,object> */
    private static array $instances = [];

    /** @var array<string,\Closure> */
    private static array $factories = [];

    public static function register(string $id, \Closure $factory): void
    {
        self::$factories[$id] = $factory;
        unset(self::$instances[$id]);
    }

    public static function get(string $id): object
    {
        if (isset(self::$instances[$id])) {
            return self::$instances[$id];
        }
        if (!isset(self::$factories[$id])) {
            throw new \RuntimeException("Service not registered: $id");
        }
        self::$instances[$id] = (self::$factories[$id])();
        return self::$instances[$id];
    }

    public static function has(string $id): bool
    {
        return isset(self::$factories[$id]) || isset(self::$instances[$id]);
    }

    public static function reset(): void
    {
        self::$instances = [];
        self::$factories = [];
    }
}
