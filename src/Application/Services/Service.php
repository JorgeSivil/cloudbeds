<?php

namespace CloudBeds\Application\Services;

use CloudBeds\Application\Services\Response\Interfaces\InternalApiResponseInterface;
use CloudBeds\Application\Services\Response\ResponseFactory;

/**
 * Class Service
 * @package CloudBeds\Application\Services
 */
class Service
{

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * @param string $message
     * @param array $data
     *
     * @return InternalApiResponseInterface
     */
    protected function success(string $message, array $data = []): InternalApiResponseInterface
    {
        return $this->responseFactory::makeInternalApiResponse(true, $data, $message);
    }

    /**
     * @param string $message
     * @param array $data
     * @param array $errors
     *
     * @return InternalApiResponseInterface
     */
    protected function error(string $message, array $data, array $errors): InternalApiResponseInterface
    {
        return $this->responseFactory::makeInternalApiResponse(false, $data, $message, $errors);
    }
}
