<?php

namespace CloudBeds;

define('APP_SRC_ROOT', realpath(dirname(__FILE__)));
define('APP_ROOT', realpath(dirname(__FILE__, 2)));
define('APP_NAMESPACE', __NAMESPACE__);
require_once(APP_ROOT . '/vendor/autoload.php');

use CloudBeds\Application\Services\DependencyInjection\DependencyInjectionContainer;
use CloudBeds\Application\Services\Router\Router;
use ReflectionException;

class App
{

    protected $container = null;

    public function __construct()
    {
        $this->container = new DependencyInjectionContainer();
    }

    /**
     * @throws Application\Services\DependencyInjection\Exceptions\ClassNotFoundException
     * @throws Application\Services\DependencyInjection\Exceptions\ClassNotInstantiableException
     * @throws Application\Services\DependencyInjection\Exceptions\UndefinedParameterException
     * @throws ReflectionException
     */
    public function run()
    {
        /** @var Router $router */
        $router = $this->container->get('CloudBeds\Application\Services\Router\Router');
        $router->run();
    }
}
