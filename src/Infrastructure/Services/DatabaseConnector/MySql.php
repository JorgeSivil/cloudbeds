<?php

namespace CloudBeds\Infrastructure\Services\DatabaseConnector;

use CloudBeds\Application\Config\Config;
use Exception;
use PDO;

class MySql
{
    /**
     * @var PDO
     */
    protected static $connection;

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * MySql constructor.
     * @param Config $config
     * @throws Exception
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->checkRequiredConfigsOrFail($config);
    }

    /**
     * @return PDO
     */
    public function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = $this->createConnection();
        }
        return self::$connection;
    }

    protected function createConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $this->config->get('db.host'),
            $this->config->get('db.name'),
            $this->config->get('db.charset')
        );
        $pdo = new PDO($dsn, $this->config->get('db.username'), $this->config->get('db.password'));
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    /**
     * @param Config $config
     * @return void
     * @throws Exception
     */
    protected function checkRequiredConfigsOrFail(Config $config): void
    {
        if (!($config->has('db.username')
            && $config->has('db.password')
            && $config->has('db.host')
            && $config->has('db.name')
            && $config->has('db.charset')
        )) {
            throw new Exception('Missing required database configs.');
        }
    }
}
