<?php

declare(strict_types=1);

namespace Lib\Support;

class Session
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $https = $_SERVER['HTTPS'] ?? 'off';
        $lifetime = (int) config('app.session_lifetime', 120) * 60;

        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path' => '/',
            'secure' => $https !== '' && $https !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function appKey(): string
    {
        return 'app_' . md5(config('app.name', 'app'));
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        $appKey = self::appKey();

        return $_SESSION[$appKey][$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $appKey = self::appKey();
        $_SESSION[$appKey][$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        $appKey = self::appKey();

        return array_key_exists($key, $_SESSION[$appKey] ?? []);
    }

    public static function forget(string $key): void
    {
        self::start();
        $appKey = self::appKey();
        unset($_SESSION[$appKey][$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);
        return $value;
    }

    public static function flush(): void
    {
        self::start();
        $appKey = self::appKey();
        $_SESSION[$appKey] = [];
    }

    public static function regenerate(bool $deleteOldSession = true): void
    {
        self::start();
        session_regenerate_id($deleteOldSession);
    }

    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if ((bool) ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return (bool) self::get('loged', false);
    }

    public static function user(): ?array
    {
        return self::get('user');
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            self::set('_flash_' . $key, $value);
            return null;
        }

        return self::pull('_flash_' . $key);
    }
}
