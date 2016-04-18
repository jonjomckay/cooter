<?php
namespace Cooter\Framework\Router;

class Route
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $controller;

    /**
     * @var string
     */
    private $function;

    /**
     * Route constructor.
     * @param string $url
     * @param string $method
     * @param string $controller
     * @param string $function
     */
    public function __construct($url, $method, $controller, $function)
    {
        $this->url = $url;
        $this->method = $method;
        $this->controller = $controller;
        $this->function = $function;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }
}