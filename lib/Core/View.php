<?php

declare(strict_types=1);

namespace Lib\Core;

class View
{
    private static array $shared = [];
    private static array $sections = [];
    private static ?string $currentSection = null;
    private static array $viewsPath = [
        __DIR__ . '/../../System/views/',
        __DIR__ . '/../../App/views/',
    ];

    private const LAYOUT_PLACEHOLDERS = [
        'head', 'link', 'src', 'title', 'session', 'content',
        'carousel', 'about', 'services', 'video', 'noticias', 'main', 'testimonial',
    ];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function getShared(?string $key = null): mixed
    {
        if ($key === null) {
            return self::$shared;
        }

        return self::$shared[$key] ?? null;
    }

    public static function exists(string $view): bool
    {
        return is_file(self::path($view));
    }

    public static function render(string $view, array $data = []): string
    {
        $path = self::path($view);

        if (!is_file($path)) {
            throw new \RuntimeException("View [{$view}] not found at [{$path}].");
        }

        $variables = array_merge(self::$shared, $data);

        extract($variables);

        ob_start();
        include $path;
        $content = ob_get_clean();

        return self::replacePlaceholders($content, $variables);
    }

    private static function replacePlaceholders(string $content, array $data): string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $content = str_replace('{' . $key . '}', (string) $value, $content);
            }
        }

        foreach (self::LAYOUT_PLACEHOLDERS as $key) {
            if (!array_key_exists($key, $data)) {
                $content = str_replace('{' . $key . '}', '', $content);
            }
        }

        return $content;
    }

    public static function component(string $view, array $data = []): string
    {
        return self::render($view, $data);
    }

    public static function make(string $view, array $data = []): string
    {
        return self::render($view, $data);
    }

    public static function section(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    public static function endSection(): void
    {
        if (self::$currentSection === null) {
            throw new \RuntimeException('No active section.');
        }

        self::$sections[self::$currentSection] = ob_get_clean();
        self::$currentSection = null;
    }

    public static function yield(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    public static function asset(string $path): string
    {
        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }

    private static function path(string $view): string
    {
        $relative = str_replace('.', '/', $view) . '.php';

        foreach (self::$viewsPath as $base) {
            if (is_file($base . $relative)) {
                return $base . $relative;
            }
        }

        return self::$viewsPath[0] . $relative;
    }
}
