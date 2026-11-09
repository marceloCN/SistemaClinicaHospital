<?php

declare(strict_types=1);

namespace Lib\Core;

class RouteRegistrar
{
    public function __construct(
        private string $method,
        private string $uri
    ) {
    }

    public function name(string $name): self
    {
        Route::setName($this->method, $this->uri, $name);
        return $this;
    }

    public function middleware(array|string $middleware): self
    {
        Route::addMiddleware($this->method, $this->uri, $middleware);
        return $this;
    }
}
