<?php

declare(strict_types=1);

namespace Lib\Core;

class Response
{
    private mixed $content;
    private int $statusCode = 200;
    private array $headers = [];

    public function __construct(mixed $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function make(mixed $content = '', int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        // JSON_INVALID_UTF8_SUBSTITUTE: si un string trae un multibyte cortado
        // por la mitad, json_encode() devolveria false y el body saldria vacio
        // con status 200 (jQuery lo reportaria como error de parse sin codigo).
        // Con el flag se sustituyen los bytes invalidos y la respuesta nunca
        // queda vacia.
        $encoded = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($encoded === false) {
            $encoded = '{"success":false,"message":"JSON encode error."}';
        }

        return new self($encoded, $status, $headers);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function back(int $status = 302): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return self::redirect($referer, $status);
    }

    public static function error(string $message, int $status = 500): self
    {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            return self::json(['success' => false, 'message' => $message], $status);
        }

        return new self($message, $status);
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if ($this->content !== null && $this->content !== '') {
            echo $this->content;
        }

        exit;
    }

    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getStatus(): int
    {
        return $this->statusCode;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }
}
