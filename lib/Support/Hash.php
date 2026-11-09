<?php

declare(strict_types=1);

namespace Lib\Support;

class Hash
{
    public static function make(string $value): string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public static function check(string $value, string $hashedValue): bool
    {
        return password_verify($value, $hashedValue);
    }

    public static function needsRehash(string $hashedValue): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT);
    }
}
