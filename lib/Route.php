<?php

declare(strict_types=1);

namespace lib;

use Lib\Core\Route as BaseRoute;

/**
 * @deprecated Use Lib\Core\Route instead.
 */
class Route
{
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return BaseRoute::{$name}(...$arguments);
    }

    public static function route(string $name, array $params = []): ?string
    {
        return BaseRoute::route($name, $params);
    }

    public static function redirect(string $to): void
    {
        $url = BaseRoute::route($to) ?? $to;
        header("Location: {$url}");
        exit;
    }
}
