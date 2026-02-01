<?php
namespace Core;

class Router
{
    private array $staticRoutes = [];
    private array $dynamicRoutes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        if (str_contains($path, '{')) {
            $this->dynamicRoutes[$method][] = ['pattern' => $path, 'handler' => $handler];
            return;
        }

        $this->staticRoutes[$method][$path] = $handler;
    }

    public function group(string $prefix, callable $callback): void
    {
        $originalAdd = [$this, 'add'];
        $groupedAdd = function (string $method, string $path, callable $handler) use ($originalAdd) {
            $originalAdd($method, $path, $handler);
        };
        $callback($groupedAdd);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        $handler = $this->staticRoutes[$method][$path] ?? null;
        if ($handler === null) {
            $match = $this->matchDynamic($method, $path);
            if ($match !== null) {
                $handler = $match['handler'];
                $request->setParams($match['params']);
            }
        }

        if ($handler === null) {
            $message = class_exists(Lang::class) ? Lang::get('FFCMS_NOT_FOUND') : 'Not Found';
            if (class_exists(Freegate::class)) {
                Freegate::logNotFound($path);
            }
            return new Response($message, 404, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        $response = $handler($request);
        if ($response instanceof Response) {
            return $response;
        }

        return new Response((string)$response, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    private function matchDynamic(string $method, string $path): ?array
    {
        $routes = $this->dynamicRoutes[$method] ?? [];
        foreach ($routes as $route) {
            $pattern = $route['pattern'];
            $regex = preg_replace('/\\{[a-zA-Z0-9_]+\\}/', '([^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            preg_match_all('/\\{([a-zA-Z0-9_]+)\\}/', $pattern, $keys);
            $params = [];
            foreach ($keys[1] as $index => $key) {
                $params[$key] = $matches[$index + 1] ?? '';
            }

            return [
                'handler' => $route['handler'],
                'params' => $params,
            ];
        }

        return null;
    }
}
