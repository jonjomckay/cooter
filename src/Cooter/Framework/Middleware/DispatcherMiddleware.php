<?php
namespace Cooter\Framework\Middleware;

use Cooter\Framework\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DispatcherMiddleware
{
    private $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(Request $request, Response $response, callable $out)
    {
        return $out($request, $this->dispatcher->dispatch($request));
    }
}