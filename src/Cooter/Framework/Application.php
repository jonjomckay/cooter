<?php
namespace Cooter\Framework;

use Cooter\Framework\Router\Route;
use Franzl\Middleware\Whoops\WhoopsRunner;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Route\RouteCollection;
use League\Route\Strategy\StrategyInterface;
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
     * @var RouteCollection
     */
    private $routes;

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
        $this->routes = new RouteCollection($this->container);
    }

    public function addMiddleware(callable $middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function addRoute($url, $method = 'GET', $controller, $function)
    {
        $this->routes->map($method, $url, [$controller, $function]);
    }

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->routes->setStrategy($strategy);
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

        foreach ($this->middleware as $middleware) {
            $this->routes->middleware($middleware);
        }

        try {
            $response = $this->routes->dispatch($this->container->get('request'), $this->container->get('response'));

            $emitter = new SapiEmitter();
            $emitter->emit($response);
        } catch (\Exception $exception) {
            WhoopsRunner::handle($exception);
        }
    }
}