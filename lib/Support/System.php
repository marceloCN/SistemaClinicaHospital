<?php

declare(strict_types=1);

namespace Lib\Support;

use Lib\Core\Database;

class System
{
    public const ROUTE_TOGGLE_PREFIX = 'route.enabled.';
    public const MIDDLEWARE_TOGGLE_PREFIX = 'middleware.enabled.';
    public const HOME_ROUTE_KEY = 'home.route';
    public const DEFAULT_HOME_ROUTE = 'inicio';

    private const PROTECTED_MIDDLEWARE = ['Auth'];

    private static ?array $cache = null;

    public static function all(): array
    {
        self::load();
        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        self::load();

        $keyColumn = Database::quoteIdentifier('key');
        $valueColumn = Database::quoteIdentifier('value');

        if (Database::isPgsql()) {
            $stmt = Database::connection()->prepare(
                "INSERT INTO system_settings ({$keyColumn}, {$valueColumn}, updated_at)
                 VALUES (:key, :value, NOW())
                 ON CONFLICT ({$keyColumn}) DO UPDATE SET {$valueColumn} = EXCLUDED.{$valueColumn}, updated_at = NOW()"
            );

            $stmt->execute([
                'key' => $key,
                'value' => $value,
            ]);

            self::$cache[$key] = $value;

            return;
        }

        $stmt = Database::connection()->prepare(
            "INSERT INTO system_settings ({$keyColumn}, {$valueColumn}, updated_at)
             VALUES (:key, :value, NOW())
             ON DUPLICATE KEY UPDATE {$valueColumn} = :value_update, updated_at = NOW()"
        );

        $stmt->execute([
            'key' => $key,
            'value' => $value,
            'value_update' => $value,
        ]);

        self::$cache[$key] = $value;
    }

    public static function remove(string $key): void
    {
        self::load();

        $keyColumn = Database::quoteIdentifier('key');

        $stmt = Database::connection()->prepare("DELETE FROM system_settings WHERE {$keyColumn} = :key");
        $stmt->execute(['key' => $key]);

        unset(self::$cache[$key]);
    }

    public static function routeEnabled(string $key): bool
    {
        return self::get(self::ROUTE_TOGGLE_PREFIX . $key, '1') !== '0';
    }

    public static function setRouteEnabled(string $key, bool $enabled): void
    {
        self::set(self::ROUTE_TOGGLE_PREFIX . $key, $enabled ? '1' : '0');
    }

    public static function middlewareEnabled(string $name): bool
    {
        if (self::isProtectedMiddleware($name)) {
            return true;
        }

        return self::get(self::MIDDLEWARE_TOGGLE_PREFIX . $name, '1') !== '0';
    }

    public static function setMiddlewareEnabled(string $name, bool $enabled): bool
    {
        if (self::isProtectedMiddleware($name)) {
            return false;
        }

        self::set(self::MIDDLEWARE_TOGGLE_PREFIX . $name, $enabled ? '1' : '0');
        return true;
    }

    public static function isProtectedMiddleware(string $name): bool
    {
        return in_array($name, self::PROTECTED_MIDDLEWARE, true);
    }

    public static function homeRoute(): string
    {
        return (string) self::get(self::HOME_ROUTE_KEY, self::DEFAULT_HOME_ROUTE);
    }

    public static function setHomeRoute(string $name): void
    {
        self::set(self::HOME_ROUTE_KEY, $name);
    }

    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function load(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = [];

        try {
            $keyColumn = Database::quoteIdentifier('key');
            $valueColumn = Database::quoteIdentifier('value');

            $rows = Database::connection()
                ->query("SELECT {$keyColumn}, {$valueColumn} FROM system_settings")
                ->fetchAll();

            foreach ($rows as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        } catch (\Throwable $e) {
            error_log('System settings load failed: ' . $e->getMessage());
        }
    }
}
