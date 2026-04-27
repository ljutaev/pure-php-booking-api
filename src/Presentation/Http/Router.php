<?php

declare(strict_types=1);

namespace App\Presentation\Http;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function put(string $path, callable $handler): void
    {
        $this->routes['PUT'][$path] = $handler;
    }

    public function delete(string $path, callable $handler): void
    {
        $this->routes['DELETE'][$path] = $handler;
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

        if ($pathExistsUnderOtherMethod) {
            return JsonResponse::methodNotAllowed();
        }

        return JsonResponse::notFound('Route not found');
    }
}
