<?php
namespace Cooter\Framework;

use Franzl\Middleware\Whoops\FormatNegotiator;
use Franzl\Middleware\Whoops\WhoopsRunner;
use League\Container\Container;
use League\Container\ContainerAwareTrait;
use League\Container\ReflectionContainer;
use League\Event\EmitterTrait;
use League\Route\RouteCollection;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

class Application
{
    use ContainerAwareTrait;
    use EmitterTrait;

    /**
     * @var RouteCollection
     */
    protected $router;

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

        $response = $this->container->get('response');

        $emitter = new SapiEmitter();

        try {
            $this->emit('request.received', $request);

            $response = $this->router->dispatch($request, $response);

            $this->emit('response.created', $request, $response);

            $emitter->emit($response);
        } catch (\Throwable $throwable) {
            $format = FormatNegotiator::getPreferredFormat($request);

            switch ($format) {
                case 'json':
                    $errorResponse = new Response\JsonResponse([
                        'error' => $throwable->getMessage()
                    ]);

                    $errorResponse = $errorResponse->withHeader('Content-Type', 'application/json');
                    break;
                default:
                    $errorResponse = WhoopsRunner::handle($throwable, $request);
                    break;
            }

            $emitter->emit($errorResponse);
        }
    }
}
