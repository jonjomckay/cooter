<?php
namespace Cooter\Framework;

use Cooter\Framework\Router\Route;
use Franzl\Middleware\Whoops\WhoopsRunner;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\RouteCollection;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

class Application
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Route[]
     */
    private $routes = [];

    /**
     * @var callable[]
     */
    private $middleware = [];

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->container = new Container();
    }

    public function addMiddleware(callable $middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function addRoute($url, $method = 'GET', $controller, $function)
    {
        $this->routes[$method . $url] = new Route($url, $method, $controller, $function);
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function start()
    {
        $this->container->delegate(new ReflectionContainer());
        $this->container->share('response', Response::class);
        $this->container->share('request', function () {
            return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        });

        $routes = new RouteCollection($this->container);
        foreach ($this->routes as $route) {
            $routes->map($route->getMethod(), $route->getUrl(), [$route->getController(), $route->getFunction()]);
        }

        foreach ($this->middleware as $middleware) {
            $routes->middleware($middleware);
        }

        try {
            $response = $routes->dispatch($this->container->get('request'), $this->container->get('response'));

            $emitter = new SapiEmitter();
            $emitter->emit($response);
        } catch (\Exception $exception) {
            WhoopsRunner::handle($exception);
        }
    }
}