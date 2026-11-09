<?php

declare(strict_types=1);

use Lib\Support\Csrf;
use Lib\Support\Session;

function config(string $key, mixed $default = null): mixed
{
    static $cache = [];

    [$file, $dotKey] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($cache[$file])) {
        $path = __DIR__ . '/../config/' . $file . '.php';
        $cache[$file] = is_file($path) ? require $path : [];
    }

    if ($dotKey === null) {
        return $cache[$file];
    }

    $value = $cache[$file];
    foreach (explode('.', $dotKey) as $segment) {
        if (is_array($value) && array_key_exists($segment, $value)) {
            $value = $value[$segment];
        } else {
            return $default;
        }
    }

    return $value;
}

function env(string $key, mixed $default = null): mixed
{
    return \Lib\Support\Env::get($key, $default);
}

function session(string|array|null $key = null, mixed $value = null): mixed
{
    if ($key === null) {
        return new class {
            public function get(string $key, mixed $default = null): mixed
            {
                return Session::get($key, $default);
            }

            public function set(string $key, mixed $value): void
            {
                Session::set($key, $value);
            }

            public function flash(string $key, mixed $value = null): mixed
            {
                return Session::flash($key, $value);
            }
        };
    }

    if (is_array($key)) {
        foreach ($key as $k => $v) {
            Session::set($k, $v);
        }
        return null;
    }

    if (func_num_args() === 1) {
        return Session::get($key);
    }

    Session::set($key, $value);
    return null;
}

function csrf_token(): string
{
    return Csrf::token();
}

function csrf_field(): string
{
    return Csrf::field();
}

function asset(string $path): string
{
    $path = ltrim($path, '/');
    $url = rtrim(config('app.url'), '/') . '/' . $path;
    $file = BASE_PATH . '/public/' . $path;

    if (is_file($file)) {
        $url .= '?v=' . filemtime($file);
    }

    return $url;
}

/**
 * URL de la imagen del hero: prefiere el derivative .webp si existe en
 * public/ y si no devuelve la imagen original. URLs http(s) van tal cual.
 */
function imagenHeroUrl(string $ruta): string
{
    $ruta = trim($ruta);

    if ($ruta === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $ruta) === 1) {
        return $ruta;
    }

    $ruta = ltrim($ruta, '/');
    $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $ruta);

    if (is_string($webp) && $webp !== $ruta && is_file(BASE_PATH . '/public/' . $webp)) {
        return asset($webp);
    }

    return asset($ruta);
}

function url(string $path = ''): string
{
    return asset($path);
}

function route(string $name, array $params = []): ?string
{
    return \Lib\Core\Route::route($name, $params);
}

function redirect(string $to, int $status = 302): \Lib\Core\Response
{
    $url = str_starts_with($to, 'http') ? $to : asset($to);
    return \Lib\Core\Response::redirect($url, $status);
}

function back(int $status = 302): \Lib\Core\Response
{
    return \Lib\Core\Response::back($status);
}

function json(mixed $data, int $status = 200): \Lib\Core\Response
{
    return \Lib\Core\Response::json($data, $status);
}

function view(string $view, array $data = []): string
{
    return \Lib\Core\View::render($view, $data);
}

function old(string $key, mixed $default = ''): mixed
{
    return Session::pull('_flash__old_' . $key, $default);
}

function errors(?string $key = null): array|string|null
{
    static $errors = null;

    if ($errors === null) {
        $errors = Session::pull('_flash__errors', []);
        if (!is_array($errors)) {
            $errors = [];
        }
    }

    if ($key === null) {
        return $errors;
    }

    return $errors[$key][0] ?? null;
}

function hasError(string $key): bool
{
    $errors = errors();
    return is_array($errors) && isset($errors[$key]);
}

function e(string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function dd(mixed ...$vars): void
{
    foreach ($vars as $var) {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
    exit;
}

function now(string $format = 'Y-m-d H:i:s'): string
{
    return date($format);
}
