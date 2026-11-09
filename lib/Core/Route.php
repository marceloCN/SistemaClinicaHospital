<?php

declare(strict_types=1);

namespace Lib\Core;

use Lib\Core\Request;
use Lib\Core\Response;
use Lib\Support\AuthorizationException;
use Lib\Support\System;
use Lib\Support\ValidationException;

class Route
{
    private static array $routes = [];
    private static array $groupStack = [];
    private static ?string $currentController = null;
    private static array $currentMiddleware = [];

    public static function get(string $uri, callable|array|string $action): RouteRegistrar
    {
        return self::register('GET', $uri, $action);
    }

    public static function post(string $uri, callable|array|string $action): RouteRegistrar
    {
        return self::register('POST', $uri, $action);
    }

    public static function put(string $uri, callable|array|string $action): RouteRegistrar
    {
        return self::register('PUT', $uri, $action);
    }

    public static function patch(string $uri, callable|array|string $action): RouteRegistrar
    {
        return self::register('PATCH', $uri, $action);
    }

    public static function delete(string $uri, callable|array|string $action): RouteRegistrar
    {
        return self::register('DELETE', $uri, $action);
    }

    private static function register(string $method, string $uri, callable|array|string $action): RouteRegistrar
    {
        $uri = self::normalizeUri($uri);
        $prefix = self::getGroupPrefix();

        if ($prefix !== '') {
            $uri = trim($prefix . '/' . ltrim($uri, '/'), '/');
        }

        if (is_string($action) && self::$currentController !== null) {
            $action = [self::$currentController, $action];
        }

        self::$routes[$method][$uri] = [
            'action' => $action,
            'name' => null,
            'middleware' => array_merge(self::$currentMiddleware, self::getGroupMiddleware()),
        ];

        return new RouteRegistrar($method, $uri);
    }

    public static function controller(string $controller, callable $callback): void
    {
        $previous = self::$currentController;
        self::$currentController = $controller;
        $callback();
        self::$currentController = $previous;
    }

    public static function middleware(array|string $middleware, callable $callback): void
    {
        $previous = self::$currentMiddleware;
        self::$currentMiddleware = array_merge(
            self::$currentMiddleware,
            is_string($middleware) ? [$middleware] : $middleware
        );
        $callback();
        self::$currentMiddleware = $previous;
    }

    public static function prefix(string $prefix, callable $callback): void
    {
        self::$groupStack[] = ['prefix' => $prefix, 'middleware' => []];
        $callback();
        array_pop(self::$groupStack);
    }

    public static function group(array $attributes, callable $callback): void
    {
        $prefix = $attributes['prefix'] ?? '';
        $middleware = $attributes['middleware'] ?? [];

        self::$groupStack[] = [
            'prefix' => $prefix,
            'middleware' => is_string($middleware) ? [$middleware] : $middleware,
        ];

        $callback();

        array_pop(self::$groupStack);
    }

    private static function getGroupPrefix(): string
    {
        $prefix = '';
        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix = trim($prefix . '/' . trim($group['prefix'], '/'), '/');
            }
        }
        return $prefix;
    }

    private static function getGroupMiddleware(): array
    {
        $middleware = [];
        foreach (self::$groupStack as $group) {
            if (!empty($group['middleware'])) {
                $middleware = array_merge($middleware, $group['middleware']);
            }
        }
        return array_values(array_unique($middleware));
    }

    public static function setName(string $method, string $uri, string $name): void
    {
        if (isset(self::$routes[$method][$uri])) {
            self::$routes[$method][$uri]['name'] = $name;
        }
    }

    public static function addMiddleware(string $method, string $uri, array|string $middleware): void
    {
        if (!isset(self::$routes[$method][$uri])) {
            return;
        }

        $middleware = is_string($middleware) ? [$middleware] : $middleware;

        self::$routes[$method][$uri]['middleware'] = array_values(array_unique(array_merge(
            self::$routes[$method][$uri]['middleware'],
            $middleware
        )));
    }

    public static function route(string $name, array $params = []): ?string
    {
        foreach (self::$routes as $method => $routes) {
            foreach ($routes as $uri => $route) {
                if ($route['name'] === $name) {
                    $url = '/' . $uri;
                    foreach ($params as $key => $value) {
                        $url = str_replace('{' . $key . '}', (string) $value, $url);
                    }
                    return $url;
                }
            }
        }

        return null;
    }

    public static function findByName(string $name): ?array
    {
        foreach (self::$routes as $method => $routes) {
            foreach ($routes as $uri => $route) {
                if ($route['name'] === $name) {
                    return ['method' => $method, 'uri' => $uri, 'route' => $route];
                }
            }
        }

        return null;
    }

    public static function isEnabled(string $name): bool
    {
        $found = self::findByName($name);

        if ($found === null) {
            return true;
        }

        return System::routeEnabled($found['route']['name'] ?? $found['uri']);
    }

    public static function all(): array
    {
        $all = [];

        foreach (self::$routes as $method => $routes) {
            foreach ($routes as $uri => $route) {
                $all[] = [
                    'method' => $method,
                    'uri' => $uri,
                    'name' => $route['name'],
                    'middleware' => $route['middleware'],
                ];
            }
        }

        usort($all, fn (array $a, array $b): int => [$a['uri'], $a['method']] <=> [$b['uri'], $b['method']]);

        return $all;
    }

    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = trim($request->uri(), '/');

        if (!isset(self::$routes[$method])) {
            return self::notFound($request);
        }

        foreach (self::$routes[$method] as $route => $routeData) {
            $pattern = self::compileRoute($route);

            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }

            if (!System::routeEnabled($routeData['name'] ?? $route)) {
                continue;
            }

            $params = array_slice($matches, 1);

            // Middleware
            foreach ($routeData['middleware'] as $middleware) {
                $parts = explode(':', $middleware);
                $name = $parts[0];
                $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

                if (!System::middlewareEnabled($name)) {
                    continue;
                }

                $class = 'System\\Middleware\\' . ucfirst($name);

                if (!class_exists($class)) {
                    $class = 'App\\Middleware\\' . ucfirst($name);
                }

                if (!class_exists($class)) {
                    throw new \RuntimeException("Middleware [{$name}] not found.");
                }

                $instance = new $class();
                $result = $instance->handle($request, ...$args);

                if ($result === false) {
                    return Response::make('', 403);
                }

                if ($result instanceof Response) {
                    return $result;
                }
            }

            $action = $routeData['action'];

            try {
                if (is_array($action)) {
                    [$controller, $methodName] = $action;
                    $controllerInstance = new $controller();

                    if (method_exists($controllerInstance, 'setRequest')) {
                        $controllerInstance->setRequest($request);
                    }

                    $arguments = self::resolveArguments(
                        new \ReflectionMethod($controllerInstance, $methodName),
                        $request,
                        $params
                    );

                    $response = $controllerInstance->{$methodName}(...$arguments);
                } elseif ($action instanceof \Closure) {
                    $arguments = self::resolveArguments(new \ReflectionFunction($action), $request, $params);
                    $response = $action(...$arguments);
                } elseif (is_callable($action)) {
                    $response = $action($request, ...$params);
                } else {
                    return self::notFound($request);
                }
            } catch (AuthorizationException $e) {
                return Response::json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->status());
            } catch (ValidationException $e) {
                $errors = $e->errors();
                $first = null;

                foreach ($errors as $messages) {
                    $first = is_array($messages) ? ($messages[0] ?? null) : $messages;
                    if ($first !== null) {
                        break;
                    }
                }

                return Response::json([
                    'success' => false,
                    'message' => $first ?? 'Datos inválidos.',
                    'errors' => $errors,
                ], 422);
            }

            return self::convertToResponse($response, $request);
        }

        return self::notFound($request);
    }

    /**
     * Resuelve los argumentos de un método controlador o closure:
     * - `FormRequest` (subclase): se instancia con la petición real, se valida
     *   (authorize + rules) y se inyecta.
     * - `Request` (o subclase no FormRequest): se inyecta la petición real.
     * - Parámetro sin tipo llamado `$request`: se inyecta la petición real.
     * - Resto: parámetros de ruta en orden (con cast a int/float/bool si aplica).
     */
    private static function resolveArguments(\ReflectionFunctionAbstract $function, Request $request, array $params): array
    {
        $arguments = [];
        $routeIndex = 0;

        foreach ($function->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                foreach (array_slice($params, $routeIndex) as $value) {
                    $arguments[] = $value;
                }
                break;
            }

            $type = $parameter->getType();
            $namedType = $type instanceof \ReflectionNamedType ? $type : null;
            $typeName = $namedType?->getName();

            if ($namedType !== null && !$namedType->isBuiltin()) {
                if (is_a($typeName, FormRequest::class, true)) {
                    $formRequest = $typeName::createFrom($request);
                    $formRequest->validateResolved();
                    $arguments[] = $formRequest;
                    continue;
                }

                if (is_a($typeName, Request::class, true)) {
                    $arguments[] = $request;
                    continue;
                }
            }

            if ($typeName === null && $parameter->getName() === 'request') {
                $arguments[] = $request;
                continue;
            }

            if (array_key_exists($routeIndex, $params)) {
                $arguments[] = self::castRouteParameter((string) $params[$routeIndex], $typeName);
                $routeIndex++;
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $arguments[] = null;
        }

        return $arguments;
    }

    private static function castRouteParameter(string $value, ?string $type): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }

    private static function compileRoute(string $route): string
    {
        $segments = preg_split('#(\{[a-zA-Z0-9_]+\})#', $route, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pattern = '';

        foreach ($segments as $segment) {
            $pattern .= preg_match('#^\{[a-zA-Z0-9_]+\}$#', $segment)
                ? '([^/]+)'
                : preg_quote($segment, '#');
        }

        return '#^' . $pattern . '$#';
    }

    private static function convertToResponse(mixed $response, Request $request): Response
    {
        if ($response instanceof Response) {
            return $response;
        }

        if (is_array($response) || is_object($response)) {
            return Response::json($response);
        }

        return Response::make((string) $response);
    }

    private static function notFound(Request $request): Response
    {
        if ($request->isAjax() || str_contains($request->header('Accept', ''), 'application/json')) {
            return Response::json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $content = View::exists('error.error404') ? View::render('error.error404') : '404 Not Found';

        $content = str_replace('{{route}}', htmlspecialchars($request->path(), ENT_QUOTES, 'UTF-8'), $content);

        return Response::make($content, 404);
    }

    private static function normalizeUri(string $uri): string
    {
        return trim($uri, '/');
    }
}
