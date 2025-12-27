<?php
namespace App\Core;

class Router {
    private $routes = [];
    
    public function add($method, $path, $handler) {
        $this->routes[$method][$path] = $handler;
    }
    
    public function dispatch($request, $method) {
        if (isset($this->routes[$method][$request])) {
            return call_user_func($this->routes[$method][$request]);
        }
        
        http_response_code(404);
        return json_encode(['error' => 'Endpoint not found']);
    }
}