<?php
namespace Cooter\Framework\Router;

class RouteCollection
{

    /**
     * @var Route[]
     */
    private $routes = [];

    public function addRoute($url, $method = 'GET', $controller, $function)
    {
        $this->routes[$method . $url] = new Route($url, $method, $controller, $function);
    }

    /**
     * @param $method
     * @param $path
     * @return Route
     */
    public function getRoute($method, $path)
    {
        return $this->routes[$method . $path];
    }
}