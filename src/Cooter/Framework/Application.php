<?php
namespace Cooter\Framework;

use Franzl\Middleware\Whoops\FormatNegotiator;
use Franzl\Middleware\Whoops\WhoopsRunner;
use League\Container\Container;
use League\Container\ContainerAwareTrait;
use League\Container\ReflectionContainer;
use League\Event\EmitterTrait;
use League\Route\Http\Exception as HttpException;
use League\Route\RouteCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Stratigility\MiddlewarePipe;

class Application
{
    use ContainerAwareTrait;
    use EmitterTrait;

    /**
     * @var RouteCollection
     */
    protected $router;

    protected $beforeMiddleware = [];

    protected $afterMiddleware = [];

    public function getContainer()
    {
        if (!isset($this->container)) {
            $this->setContainer(new Container());
        }

        return $this->container;
    }

    public function getRouter()
    {
        if (!isset($this->router)) {
            $this->router = new RouteCollection($this->getContainer());
        }

        return $this->router;
    }

    public function getEventEmitter()
    {
        return $this->getEmitter();
    }

    public function addBeforeMiddleware(callable $middleware)
    {
        $this->beforeMiddleware[] = $middleware;
    }

    public function addAfterMiddleware(callable $middleware)
    {
        $this->afterMiddleware[] = $middleware;
    }

    public function addRoute($path, $method, $controller, $action)
    {
        $this->getRouter()->map($method, $path, [$controller, $action]);
    }

    public function start()
    {
        $this->container->delegate(new ReflectionContainer());
        $this->container->share('response', Response::class);
        $this->container->share('request', function () {
            return ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
        });

        /** @var ServerRequestInterface $request */
        $request = $this->container->get('request');

        /** @var ResponseInterface $response */
        $response = $this->container->get('response');

        $emitter = new SapiEmitter();

        $middleware = new MiddlewarePipe();

        foreach ($this->beforeMiddleware as $beforeMiddleware) {
            $middleware->pipe('/', $beforeMiddleware);
        }

        $middleware->pipe('/', function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
            try {
                $this->emit('request.received', $request);

                $response = $this->router->dispatch($request, $response);

                $this->emit('response.created', $request, $response);

                return $next($request, $response);
            } catch (\Throwable $t) {
                return $next($request, $response, $t);
            }
        });

        foreach ($this->afterMiddleware as $afterMiddleware) {
            $middleware->pipe('/', $afterMiddleware);
        }

        $middleware->pipe('/', function (\Throwable $throwable, ServerRequestInterface $request, ResponseInterface $response, callable $next) {
            $format = FormatNegotiator::getPreferredFormat($request);

            switch ($format) {
                case 'json':
                    $response = new Response\JsonResponse([
                        'error' => $throwable->getMessage()
                    ]);

                    $response = $response->withHeader('Content-Type', 'application/json');
                    break;
                default:
                    $response = WhoopsRunner::handle($throwable, $request);
                    break;
            }

            if ($throwable instanceof HttpException) {
                $response = $response->withStatus($throwable->getStatusCode());
            }

            return $next($request, $response);
        });

        $result = $middleware($request, $response);

        $emitter->emit($result);
    }
}
