<?php 

    namespace core;

    class Router
    {
        private $routes = [];

        public function add($method, $path, $handler) {
            $this->routes[] = [
                'method'  => $method,
                'path'    => $path,
                'handler' => $handler
            ];
        }

        public function dispatch($uri, $method) {

            
            $path = parse_url($uri, PHP_URL_PATH);

            $basePath = '/CoachProV3/public';
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }

            $path = ($path === '' || $path === false) ? '/' : $path;

            foreach ($this->routes as $route) {

                if ($route['path'] === $path && $route['method'] === $method) {
                    $handler = $route['handler'];

                    $controllerClass = '';
                    $action = '';

                    if (is_array($handler) && count($handler) >= 2) {
                        $controllerClass = $handler[0];
                        $action = $handler[1];
                    } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                        $parts = explode('@', $handler);
                        $controllerClass = $parts[0];
                        $action = $parts[1];
                    }

                    // If after checking both formats $action is still empty, the route definition is wrong
                    if (empty($controllerClass) || empty($action)) {
                        die("Error: Invalid handler defined for route '$path'. Expected [Controller, method] or 'Controller@method'.");
                    }

                    if (class_exists($controllerClass)) {
                        $controller = new $controllerClass();
                        
                        if (method_exists($controller, $action)) {
                            return $controller->$action();
                        }
                        
                        die("Method '$action' does not exist in $controllerClass");
                    }

                    die("Controller class '$controllerClass' not found.");
                }
            }

            http_response_code(404);
            echo '404 - Page not found';
        }
    }
