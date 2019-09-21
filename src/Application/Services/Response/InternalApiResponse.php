<?php

namespace CloudBeds\Application\Services\Response;

use CloudBeds\Application\Services\Response\Interfaces\InternalApiResponseInterface;
use CloudBeds\Domain\Interfaces\Arrayable;
use InvalidArgumentException;

/**
 * This class defines a standard response that can be used across any class.
 */
class InternalApiResponse implements InternalApiResponseInterface, Arrayable
{
    /**
     * Status of the response. True in case of success or false in case of failure
     *
     * @var bool
     */
    private $success;

    /**
     * Array that contains the response data
     *
     * @var array
     */
    private $data;

    /**
     * Message that will be send containing any additional information about the response
     *
     * @var string
     */
    private $message;

    /**
     * Array that contains any error thrown on the response
     *
     * @var array
     */
    private $errors;

    /**
     * @var string The response timestamp.
     */
    private $timestamp;

    public function __construct($status = false, $data = [], $message = null, $errors = [])
    {
        $this->success = $status;
        $this->data = $data;
        $this->message = $message;
        $this->timestamp = date("c");
        $this->setErrors($errors);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->data,
            'errors' => $this->errors,
            'timestamp' => $this->timestamp,
        ];
    }

    /**
     * @param bool $status
     * @param array $data
     * @param string $message
     * @param array $errors
     * @return InternalApiResponseInterface
     */
    public function setAttributes(
        $status = false,
        $data = [],
        $message = '',
        $errors = []
    ): InternalApiResponseInterface {
        $this->success = $status;
        $this->data = $data;
        $this->message = $message;
        $this->setErrors($errors);

        return $this;
    }

    /**
     * @param string $errorMessage
     * @return InternalApiResponseInterface
     */
    public function addError(string $errorMessage): InternalApiResponseInterface
    {
        $this->errors[] = $errorMessage;
        return $this;
    }

    /**
     * @param bool $success
     * @return InternalApiResponseInterface
     */
    public function setSuccess(bool $success): InternalApiResponseInterface
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param string $message
     * @return InternalApiResponseInterface
     */
    public function setMessage(string $message): InternalApiResponseInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return bool The operation result status.
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return string[] The errors, if any.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return array The extra data, if any.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return string The message, if any.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string[] $errors
     * @return InternalApiResponseInterface
     */
    public function setErrors(array $errors): InternalApiResponseInterface
    {
        foreach ($errors as $error) {
            if (!is_string($error)) {
                throw new InvalidArgumentException('Parameter $errors must be an array of strings.');
            }
        }
        $this->errors = $errors;
        return $this;
    }

    /**
     * @param array $data
     * @return InternalApiResponseInterface
     */
    public function setData(array $data): InternalApiResponseInterface
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return string The response timestamp
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }
}
