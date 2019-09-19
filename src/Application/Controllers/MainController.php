<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Http\Response;

class MainController extends Controller
{
    public function indexAction()
    {
        return new Response("hehe");
    }
}
