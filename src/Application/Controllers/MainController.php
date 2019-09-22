<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Response\HttpResponse;
use Exception;

class MainController extends Controller
{
    /**
     * @return HttpResponse
     * @throws Exception
     */
    public function indexAction()
    {
        return new HttpResponse($this->loadView('MainIndex'));
    }
}
