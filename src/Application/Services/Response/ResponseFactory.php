<?php

namespace CloudBeds\Application\Services\Response;

use CloudBeds\Application\Services\Response\Interfaces\InternalApiResponseInterface;

class ResponseFactory
{
    /**
     * @param bool $status
     * @param array $data
     * @param string|null $message
     * @param array $errors
     * @return InternalApiResponseInterface
     */
    public static function makeInternalApiResponse(
        $status = false,
        array $data = [],
        string $message = null,
        array $errors = []
    ): InternalApiResponseInterface {
        return new InternalApiResponse($status, $data, $message, $errors);
    }
}
