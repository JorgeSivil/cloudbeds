<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class UnitTestCase extends TestCase
{
    /**
     * @param string $classFqdn
     * @param string $methodName
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected function getProtectedMethod(string $classFqdn, string $methodName): ReflectionMethod
    {
        $class = new ReflectionClass($classFqdn);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
