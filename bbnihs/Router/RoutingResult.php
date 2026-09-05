<?php
/**
 * BBNIHS Routing Result value object
 */

declare(strict_types=1);

namespace BBNIHS\Router;

final class RoutingResult
{
    /**
     * @param array<string,mixed> $params
     */
    public function __construct(
        public readonly string $route,
        public readonly array $params = [],
    ) {}

    public function is(string $route): bool
    {
        return $this->route === $route;
    }
}
