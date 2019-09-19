<?php

namespace CloudBeds\Application\Services\DependencyInjection\Exceptions;

use Exception;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class ClassNotFoundException extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $className, $code = 0, Throwable $previous = null)
    {
        parent::__construct(sprintf('The class \'%s\' is not instantiable.', $className), $code, $previous);
    }
}
