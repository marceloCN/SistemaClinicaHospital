<?php

declare(strict_types=1);

namespace lib;

use Lib\Core\Request as BaseRequest;

/**
 * @deprecated Use Lib\Core\Request instead.
 */
class Request
{
    private static ?BaseRequest $instance = null;

    private static function instance(): BaseRequest
    {
        if (self::$instance === null) {
            self::$instance = BaseRequest::createFromGlobals();
        }
        return self::$instance;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        return self::instance()->{$name}(...$arguments);
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        return self::instance()->input($key, $default);
    }

    public static function all(): array
    {
        return self::instance()->all();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::instance()->get($key, $default);
    }

    public static function post(string $key, mixed $default = null): mixed
    {
        return self::instance()->post($key, $default);
    }

    public static function method(): string
    {
        return self::instance()->method();
    }

    public static function json(): array
    {
        return self::instance()->json();
    }
}
