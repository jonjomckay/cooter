<?php
namespace Cooter\Framework;

use Cooter\Framework\Router\RouteCollection;
use League\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

class Dispatcher
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouteCollection
     */
    private $routes;

    public function __construct(ContainerInterface $container, RouteCollection $routes)
    {
        $this->container = $container;
        $this->routes = $routes;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws RuntimeException
     */
    public function dispatch(ServerRequestInterface $request)
    {
        $route = $this->routes->getRoute($request->getMethod(), $request->getUri()->getPath());
        if ($route == null) {
            throw new RuntimeException('The requested route could not be found');
        }

        $controller = $this->container->get($route->getController());
        $method = $route->getFunction();

        return $controller->$method($request);
    }
}