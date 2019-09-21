<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Http\Response;
use Exception;

class MainController extends Controller
{
    /**
     * @return Response
     * @throws Exception
     */
    public function indexAction()
    {
        return new Response($this->loadView('MainIndex'));
    }
}
