<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Http\Response;

class IntervalsController extends Controller
{

    public function __construct()
    {
    }

    public function indexAction()
    {
        return new Response("hehe2");
    }
}
