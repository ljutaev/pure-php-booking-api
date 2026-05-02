<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /** @var array<string, list<array{pattern: string, params: list<string>, handler: callable}>> */
    private array $parametrized = [];

    public function get(string $path, callable $handler): void
    {
        $this->register('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->register('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->register('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->register('DELETE', $path, $handler);
    }

    private function register(string $method, string $path, callable $handler): void
    {
        if (!str_contains($path, '{')) {
            $this->routes[$method][$path] = $handler;

            return;
        }

        preg_match_all('/\{([^}]+)\}/', $path, $matches);
        /** @var list<string> $paramNames */
        $paramNames = $matches[1];

        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->parametrized[$method][] = [
            'pattern' => $pattern,
            'params'  => $paramNames,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): JsonResponse
    {
        $pathExistsUnderOtherMethod = false;

        foreach ($this->routes as $method => $routes) {
            if (isset($routes[$request->uri])) {
                if ($method === $request->method) {
                    return ($routes[$request->uri])($request);
                }
                $pathExistsUnderOtherMethod = true;
            }
        }

        foreach ($this->parametrized as $method => $entries) {
            foreach ($entries as $entry) {
                if (!preg_match($entry['pattern'], $request->uri, $m)) {
                    continue;
                }

                if ($method === $request->method) {
                    array_shift($m);
                    /** @var array<string, string> $pathParams */
                    $pathParams = array_combine($entry['params'], $m);

                    return ($entry['handler'])($request->withPathParams($pathParams));
                }

                $pathExistsUnderOtherMethod = true;
            }
        }

        if ($pathExistsUnderOtherMethod) {
            return JsonResponse::methodNotAllowed();
        }

        return JsonResponse::notFound('Route not found');
    }
}
