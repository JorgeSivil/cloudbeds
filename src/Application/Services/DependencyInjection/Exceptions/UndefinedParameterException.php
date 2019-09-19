<?php

namespace CloudBeds\Application\Services\DependencyInjection\Exceptions;

use Exception;
use ReflectionParameter;
use Throwable;

class UndefinedParameterException extends Exception
{
    public function __construct(ReflectionParameter $parameter, $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'The value of the required parameter \'%s\' requested in class \'%s\', method \'%s\' is not set.',
                $parameter->getName(),
                $parameter->getDeclaringClass(),
                $parameter->getDeclaringFunction()
            ),
            $code,
            $previous
        );
    }
}
