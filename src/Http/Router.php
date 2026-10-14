<?php

declare(strict_types=1);

namespace FilebrowserNg\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $path   = $request->getPath();

        foreach ($this->routes as [$routeMethod, $routePath, $handler]) {
            if ($routeMethod !== $method) {
                continue;
            }
            $pattern = $this->pathToPattern($routePath);
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($request, $params);
                return;
            }
        }

        Response::notFound();
    }

    private function pathToPattern(string $path): string
    {
        $escaped = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $escaped . '$#';
    }
}
