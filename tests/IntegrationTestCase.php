<?php

namespace Tests;

use CloudBeds\App;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    /**
     * @var App
     */
    protected $app;

    /**
     * IntegrationTestCase constructor.
     */
    public function __construct()
    {
        $this->app = new App();
        parent::__construct();
    }

    /**
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }
}
