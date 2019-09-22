<?php

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Response\HttpResponse;
use CloudBeds\Application\Services\Response\InternalApiResponse;
use Exception;

class Controller
{
    /**
     * @param string $viewName
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function loadView(string $viewName, array $data = []): string
    {
        $fileName = $this->getViewFilename($viewName);
        $content = $this->loadViewfile($fileName, $data);
        $data['content'] = $content;

        $fileName = $this->getViewFilename('Layout');

        return $this->loadViewfile($fileName, $data);
    }

    /**
     * @param $fileName
     * @param $data
     * @return false|string
     * @throws Exception
     */
    private function loadViewfile($fileName, $data)
    {
        if (!is_file($fileName)) {
            throw new Exception(sprintf("Failed to load view file %s", $fileName));
        }
        ob_start();
        extract($data);
        include $fileName;
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    protected function getViewFilename($viewName): string
    {
        return sprintf('%s/Application/Views/%s.php', APP_SRC_ROOT, $viewName);
    }

    /**
     * @param string $message
     * @param array $data
     *
     * @return HttpResponse
     */
    protected function apiSuccess(string $message, array $data = []): HttpResponse
    {
        $internalResponse = new InternalApiResponse(true, $data, $message);
        return new HttpResponse(json_encode($internalResponse->toArray(), true), ['Content-Type: application/json']);
    }

    /**
     * @param string $message
     * @param array $errors
     *
     * @param int $httpCode
     * @return HttpResponse
     */
    protected function apiError(string $message, array $errors = [], int $httpCode = 400): HttpResponse
    {
        $internalResponse = new InternalApiResponse(false, [], $message, $errors);
        $headers = [
            'Content-Type: application/json',
            $header = sprintf('HTTP/1.1 %d %s', $httpCode, HttpResponse::$statusTexts[$httpCode])
        ];
        return new HttpResponse(json_encode($internalResponse->toArray(), true), $headers);
    }

}
