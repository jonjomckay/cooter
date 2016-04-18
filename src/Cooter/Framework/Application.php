<?php
namespace Cooter\Framework;

use Cooter\Framework\Middleware\DispatcherMiddleware;
use Cooter\Framework\Middleware\ErrorMiddleware;
use Cooter\Framework\Router\RouteCollection;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\MiddlewarePipe;

class Application
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var RouteCollection
     */
    private $routes;

    /**
     * @var MiddlewarePipe
     */
    private $middleware;

    /**
     * Application constructor.
     */
    public function __construct()
    {
        $this->container = new Container();
        $this->middleware = new MiddlewarePipe();
        $this->routes = new RouteCollection();
    }

    public function addMiddleware($path, $middleware = null)
    {
        $this->middleware->pipe($path, $middleware);
    }

    public function addRoute($url, $method = 'GET', $controller, $function)
    {
        $this->routes->addRoute($url, $method, $controller, $function);
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

        $dispatcher = new Dispatcher($this->container, $this->routes);

        $this->middleware->pipe(new DispatcherMiddleware($dispatcher));
        $this->middleware->pipe(new ErrorMiddleware());

        $request = ServerRequestFactory::fromGlobals();

        $middleware = $this->middleware;
        $response = $middleware($request, new Response());

        $emitter = new SapiEmitter();
        $emitter->emit($response);
    }
}