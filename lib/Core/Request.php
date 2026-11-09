<?php

declare(strict_types=1);

namespace Lib\Core;

class Request
{
    protected array $query;
    protected array $body;
    protected array $cookies;
    protected array $server;
    protected array $files;
    protected ?array $json = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $_POST;
        $this->cookies = $_COOKIE;
        $this->server = $_SERVER;
        $this->files = $_FILES;
    }

    public static function createFromGlobals(): self
    {
        return new self();
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = strtok($this->server['REQUEST_URI'] ?? '/', '?');
        return '/' . ltrim($uri, '/');
    }

    public function path(): string
    {
        return $this->uri();
    }

    public function fullUrl(): string
    {
        $scheme = $this->server['REQUEST_SCHEME'] ?? 'http';

        return $scheme
            . '://' . ($this->server['HTTP_HOST'] ?? 'localhost')
            . ($this->server['REQUEST_URI'] ?? '/');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isPatch(): bool
    {
        return $this->isMethod('PATCH');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isAjax(): bool
    {
        return ($this->server['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_contains($this->header('Accept', ''), 'application/json');
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body, $this->json());
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($this->json === null) {
            $input = file_get_contents('php://input');
            $this->json = $input !== '' ? (json_decode($input, true) ?? []) : [];
        }

        if ($key === null) {
            return $this->json;
        }

        return $this->json[$key] ?? $default;
    }

    public function has(string|array $key): bool
    {
        $data = $this->all();

        if (is_array($key)) {
            foreach ($key as $k) {
                if (!array_key_exists($k, $data)) {
                    return false;
                }
            }
            return true;
        }

        return array_key_exists($key, $data);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return $value !== null && $value !== '';
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key])
            && $this->files[$key]['error'] !== UPLOAD_ERR_NO_FILE;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtoupper(str_replace('-', '_', $key));

        // PHP expone Content-Type / Content-Length como CONTENT_TYPE / CONTENT_LENGTH
        // (sin el prefijo HTTP_), a diferencia del resto de cabeceras.
        if (in_array($normalized, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
            return $this->server[$normalized] ?? $default;
        }

        return $this->server['HTTP_' . $normalized] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function referer(): string
    {
        return $this->server['HTTP_REFERER'] ?? '';
    }
}
