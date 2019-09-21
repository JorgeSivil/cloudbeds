<?php

namespace CloudBeds\Application\Services\DependencyInjection;

use CloudBeds\Application\Services\DependencyInjection\Exceptions\ClassNotFoundException;
use CloudBeds\Application\Services\DependencyInjection\Exceptions\ClassNotInstantiableException;
use CloudBeds\Application\Services\DependencyInjection\Exceptions\UndefinedParameterException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class DependencyInjectionContainer implements ContainerInterface
{
    /**
     * @var array
     */
    protected $classInstances = [];

    /**
     * @var array
     */
    protected $binds = [];

    public function __construct()
    {
        $this->binds[ContainerInterface::class] = self::class;
        $this->classInstances[self::class] = $this;
    }

    public function registerInstance(string $className, object $object)
    {
        $this->classInstances[$className] = $object;
    }

    /**
     * @param string $className
     * @return object
     * @throws ClassNotFoundException
     * @throws ClassNotInstantiableException
     * @throws ReflectionException
     * @throws UndefinedParameterException
     */
    protected function getConcrete(string $className): object
    {
        $resolved = [];
        $constructor = null;
        $parameters = [];

        $reflection = new ReflectionClass($className);

        if ($reflection->isInstantiable() === false) {
            throw new ClassNotInstantiableException($className);
        }

        $constructor = $reflection->getConstructor();
        if (!is_null($constructor)) {
            $parameters = $constructor->getParameters();
        }

        if (is_null($constructor) || empty($parameters)) {
            return $reflection->newInstance();
        }

        foreach ($parameters as $parameter) {
            $resolved[] = $this->resolveParameter($parameter);
        }

        return $reflection->newInstanceArgs($resolved); // return new instance with dependencies resolved
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed|object
     * @throws ClassNotFoundException
     * @throws ClassNotInstantiableException
     * @throws ReflectionException
     * @throws UndefinedParameterException
     */
    protected function resolveParameter(ReflectionParameter $parameter)
    {
        if ($parameter->getClass() !== null) {
            return $this->get($parameter->getClass()->getName());
        } else { // Parameter is primitive
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            } else {
                throw new UndefinedParameterException($parameter);
            }
        }
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $className Identifier of the entry to look for.
     *
     * @return mixed Entry.
     * @throws ClassNotFoundException
     * @throws ClassNotInstantiableException
     * @throws ReflectionException
     * @throws UndefinedParameterException
     */
    public function get($className): object
    {
        $className = isset($this->binds[$className]) ? $this->binds[$className] : $className;
        if (isset($this->classInstances[$className])) {
            $object = $this->classInstances[$className];
        } else {
            if (!class_exists($className)) {
                throw new ClassNotFoundException(sprintf('Class \'%s\' could not be found.', $className));
            }
            $object = $this->getConcrete((string)$className);
        }

        return $object;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($id)` returning true does not mean that `get($id)` will not throw an exception.
     * It does however mean that `get($id)` will not throw a `NotFoundExceptionInterface`.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($id)
    {
        try {
            if ($this->get($id)) {
                return true;
            }
        } catch (ClassNotFoundException $e) {
        } catch (ClassNotInstantiableException $e) {
        } catch (UndefinedParameterException $e) {
        } catch (ReflectionException $e) {
        }

        return false;
    }
}
