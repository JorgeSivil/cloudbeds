<?php

namespace Tests;

use CloudBeds\App;
use CloudBeds\Infrastructure\Services\DatabaseConnector\MySql;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    /**
     * @var App
     */
    protected $app;

    /** @var MySql */
    protected $dbConnection;

    public function setUp(): void
    {
        $this->app = new App();
        /** @var MySql $dbConnection */
        $this->dbConnection = $this->app->getContainer()->get(MySql::class);
        $this->dbConnection->getConnection()->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->dbConnection->getConnection()->rollBack();
        parent::tearDown();
    }

    /**
     * @return App
     */
    public function getApp(): App
    {
        return $this->app;
    }
}
