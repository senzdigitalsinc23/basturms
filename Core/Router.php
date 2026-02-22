<?php

namespace App\Core;

use Closure;
use Exception;

class Router
{
    protected array $routes = [];
    protected Container $container;
    protected array $globalMiddleware = [];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function getApi(string $version, string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('GET', "/api/{$version}{$uri}", $action, $middleware);
    }

    public function postApi(string $version, string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('POST', "/api/{$version}{$uri}", $action, $middleware);
    }

    public function middleware(array $middleware): void
    {
        $this->globalMiddleware = array_merge($this->globalMiddleware, $middleware);
    }

    public function put(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, array $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    protected function addRoute(string $method, string $uri, array $action, array $middleware = [], array $docs = []): void
    {
        // Convert URI with placeholders like {id} to regex
        // Example: /api/v1/uploads/file/{id} => #^/api/v1/uploads/file/(?P<id>[^/]+)$#
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method'     => $method,
            'uri'        => $uri,
            'regex'      => $regex,
            'action'     => $action,
            'middleware' => $middleware,
            'docs'       => $docs
        ];
    }

    public function dispatch(Request $request, Response $response): Response
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        // Find route
        $route = null;
        $params = [];

        foreach ($this->routes as $r) {
            if ($r['method'] === $method) {
                if ($r['uri'] === $uri) {
                    $route = $r;
                    break;
                } elseif (preg_match($r['regex'], $uri, $matches)) {
                    $route = $r;
                    // Extract named parameters from regex matches
                    foreach ($matches as $key => $value) {
                        if (is_string($key)) {
                            $params[$key] = $value;
                        }
                    }
                    break;
                }
            }
        }

        // For OPTIONS preflight requests...
        if (!$route && $method === 'OPTIONS') {
            foreach ($this->routes as $r) {
                if ($r['uri'] === $uri || preg_match($r['regex'], $uri)) {
                    $route = $r;
                    break;
                }
            }
        }

        if (!$route) {
            // 404: Route not found
            $response->setStatusCode(404);
            $response->setHeader('Content-Type', 'application/json');
            $response->setContent(json_encode(['success' => false, 'message' => 'Route not found']));
            return $response;
        }

        // Build final controller callable
        $controllerHandler = function(Request $req, Response $res) use ($route, $params) {
            [$controllerClass, $action] = $route['action'];
            $controller = $this->container->resolve($controllerClass);
            $result = $controller->$action($req, $res, $params);

            if (is_string($result)) {
                $res->setContent($result);
                return $res;
            }

            if ($result instanceof Response) {
                return $result;
            }

            throw new Exception("Controller must return string or Response instance");
        };

        // Combine global middleware...
        $middlewares = array_merge($this->globalMiddleware, $route['middleware']);
        return $this->applyMiddleware($middlewares, $request, $response, $controllerHandler);
    }

    protected function applyMiddleware(array $middlewares, Request $request, Response $response, callable $handler)
    {
        $dispatcher = array_reduce(
            array_reverse($middlewares),
            function ($next, $middlewareClass) {
                return function (Request $request, Response $response) use ($next, $middlewareClass) {
                    $middleware = new $middlewareClass();
                    return $middleware->handle($request, $response, $next);
                };
            },
            $handler
        );

        return $dispatcher($request, $response);
    }

    /**
     * Placeholder for URI generation by name. Not implemented; returns null.
     */
    public function generateUri(string $name, array $params = []): ?string
    {
        return null;
    }

}
