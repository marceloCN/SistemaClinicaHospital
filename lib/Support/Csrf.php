<?php

declare(strict_types=1);

namespace Lib\Support;

class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function validate(?string $token): bool
    {
        Session::start();

        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function regenerate(): void
    {
        Session::start();
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
    }
}
