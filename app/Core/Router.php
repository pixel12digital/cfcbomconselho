<?php

namespace App\Core;

class Router
{
    private $routes = [];
    private $middlewares = [];

    public function get($path, $handler, $middlewares = [])
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post($path, $handler, $middlewares = [])
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put($path, $handler, $middlewares = [])
    {
        $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete($path, $handler, $middlewares = [])
    {
        $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    private function addRoute($method, $path, $handler, $middlewares)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch()
    {
        // Carregar rotas
        $this->loadRoutes();
        
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remover prefixo do projeto
        $uri = str_replace('/cfc-v.1/public_html', '', $uri);
        
        // Se a URI é /index.php ou termina com /index.php, remover
        $uri = str_replace('/index.php', '', $uri);
        
        // Normalizar para /
        $uri = $uri ?: '/';

        foreach ($this->routes as $route) {
            $pattern = $this->convertToRegex($route['path']);
            
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                array_shift($matches); // Remove full match
                
                // Executar middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    if (!$middlewareInstance->handle()) {
                        return;
                    }
                }

                // Executar handler
                if (is_array($route['handler'])) {
                    $controller = new $route['handler'][0]();
                    $handlerMethod = $route['handler'][1];
                    call_user_func_array([$controller, $handlerMethod], $matches);
                } else {
                    call_user_func_array($route['handler'], $matches);
                }
                return;
            }
        }

        // 404
        http_response_code(404);
        if (file_exists(APP_PATH . '/Views/errors/404.php')) {
            include APP_PATH . '/Views/errors/404.php';
        } else {
            echo "404 - Página não encontrada";
        }
    }

    private function convertToRegex($path)
    {
        $pattern = preg_replace('/\{(\w+)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function loadRoutes()
    {
        global $router;
        $router = $this;
        require_once APP_PATH . '/routes/web.php';
    }
}
