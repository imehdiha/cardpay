<?php
declare(strict_types=1);

namespace CardPay\Core;

use Throwable;

final class Router
{
    /** @var array<int,array{string,string,callable,array<int,callable>}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $this->routes[] = [strtoupper($method), $pattern, $handler, $middleware];
    }
    public function get(string $pattern, callable $handler, array $middleware = []): void { $this->add('GET', $pattern, $handler, $middleware); }
    public function post(string $pattern, callable $handler, array $middleware = []): void { $this->add('POST', $pattern, $handler, $middleware); }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as [$method, $pattern, $handler, $middleware]) {
            if ($method !== $request->method) continue;
            $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
            if (!preg_match('#^' . $regex . '$#', $request->path, $matches)) continue;
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $next = fn(Request $req) => $handler($req, $params);
            foreach (array_reverse($middleware) as $layer) {
                $current = $next;
                $next = fn(Request $req) => $layer($req, $current);
            }
            return $next($request);
        }
        return $request->isJson() || str_starts_with($request->path, '/api/')
            ? Response::error('not_found', 'The requested endpoint was not found.', 404)
            : new Response('<h1>404</h1>', 404, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
