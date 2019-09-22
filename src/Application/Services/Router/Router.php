<?php

namespace CloudBeds\Application\Services\Router;

use CloudBeds\Application\Services\Response\HttpResponse;
use Psr\Container\ContainerInterface;

class Router
{
    protected $dependencyContainer = null;
    protected $currentControllerInstance = null;
    protected $currentAction = '';

    public function __construct(ContainerInterface $container)
    {
        $this->dependencyContainer = $container;
    }

    protected function processPath(string $path = '')
    {
        $this->currentControllerInstance = null;
        $this->currentAction = '';

        if (!$path) {
            $path = $_SERVER['REQUEST_URI'];
        }
        $path = trim($path, '/');

        $verb = strtolower($_SERVER['REQUEST_METHOD']);

        $explodedPath = explode('?', $path); // Remove query string
        $explodedPath = explode('/', $explodedPath[0]);
        $controllerName = $explodedPath[0] ? ucfirst($explodedPath[0]) : 'Main';
        $this->currentAction = isset($explodedPath[1]) ? lcfirst($explodedPath[1]) : 'index';
        $this->currentAction .= 'Action';
        if ($verb !== 'get') {
            $this->currentAction .= '_' . $verb;
        }
        if ('put' === $verb) {
            parse_str(file_get_contents('php://input'), $_REQUEST);
        }
        $this->currentControllerInstance = $this->getController($controllerName);
    }

    /**
     * @param string $controllerName
     * @return object The instantiated controller
     */
    protected function getController(string $controllerName)
    {
        $controllerFqcn = sprintf('%s\\Application\\Controllers\\%sController', APP_NAMESPACE, $controllerName);

        return $this->dependencyContainer->get($controllerFqcn);
    }

    public function run(string $path = '')
    {
        $this->processPath($path);
        if (!method_exists($this->currentControllerInstance, $this->currentAction)) {
            $this->fail(HttpResponse::HTTP_NOT_FOUND);
        }
        $response = call_user_func([$this->currentControllerInstance, $this->currentAction]);
        if (!($response instanceof HttpResponse)) {
            $this->fail(
                500,
                sprintf('Controller action response must be an instance of %s class', HttpResponse::class)
            );
        }
        $this->sendHeaders($response->getHeaders());
        echo $response->getResponse();
    }

    protected function fail($httpCode, $message = '', $statusText = '')
    {
        $this->sendHttpCodeHeader($httpCode, $statusText);
        if ($message) {
            echo $message;
        }
        die(1);
    }

    protected function sendHttpCodeHeader(int $httpCode, $statusText = ''): void
    {
        $header = sprintf('HTTP/1.1 %d %s', $httpCode, HttpResponse::$statusTexts[$httpCode] ?? $statusText);
        $this->sendHeaders([$header]);
    }

    protected function sendHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            header($header);
        }
    }
}
