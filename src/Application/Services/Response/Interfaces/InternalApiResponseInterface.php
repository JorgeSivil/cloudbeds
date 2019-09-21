<?php

namespace CloudBeds\Application\Services\Response\Interfaces;

interface InternalApiResponseInterface
{
    /**
     * @return bool The operation result status.
     */
    public function getSuccess(): bool;

    /**
     * @return string[] The errors, if any.
     */
    public function getErrors(): array;

    /**
     * @return array The extra data, if any.
     */
    public function getData(): array;

    /**
     * @return string The message, if any.
     */
    public function getMessage(): string;

    /**
     * Sets the response attributes
     *
     * @param bool $status
     * @param array $data
     * @param string $message
     * @param array $errors
     *
     * @return InternalApiResponseInterface
     */
    public function setAttributes($status = false, $data = [], $message = '', $errors = []): self;

    /**
     * Add error to errors array
     * @param string $errorMessage
     *
     * @return self
     */
    public function addError(string $errorMessage): self;

    /**
     * @param string[] $errors
     * @return InternalApiResponseInterface
     */
    public function setErrors(array $errors): self;

    /**
     * @param array $data
     * @return InternalApiResponseInterface
     */
    public function setData(array $data): self;

    /**
     * Set status
     * @param bool $status
     *
     * @return self
     */
    public function setSuccess(bool $status): self;

    /**
     * Set message
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message): self;

    /**
     * @return string The response timestamp
     */
    public function getTimestamp(): string;
}
