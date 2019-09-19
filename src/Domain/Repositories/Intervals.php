<?php

namespace CloudBeds\Domain\Repositories;

use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Infrastructure\Services\DatabaseConnector\MySql;
use DateTime;
use Exception;

class Intervals
{
    /**
     * @var MySql
     */
    protected $dbConnection;

    /**
     * @var string
     */
    protected $tableName = 'intervals';

    /**
     * Intervals constructor.
     * @param MySql $dbConnection
     */
    public function __construct(MySql $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     */
    public function getAllInTimeRange(DateTime $from, DateTime $to)
    {
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param string $price
     * @return Interval
     * @throws Exception
     */
    public function create(DateTime $from, DateTime $to, string $price) : Interval
    {
        $query = sprintf('INSERT INTO %s VALUES (:from, :to, :price)', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $result = $pst->execute([
            ':from' => $from->format(DATE_ATOM),
            ':to' => $to->format(DATE_ATOM),
            ':price' => $price
        ]);
        if (!$result) {
            throw new Exception('Failed to store new interval in database.');
        }
        return new Interval($from, $to, $price);
    }

    public function getByPk(DateTime $from, DateTime $to)
    {

    }

    public function update(DateTime $from, DateTime $to, DateTime $newFrom, DateTime $newTo, string $price)
    {

    }

    public function delete(DateTime $from, DateTime $to)
    {

    }

    /**
     * @param array $intervals
     */
    public function updateAll(array $intervals)
    {

    }

    /**
     * @param array $intervals
     */
    public function deleteAll(array $intervals)
    {

    }
}
