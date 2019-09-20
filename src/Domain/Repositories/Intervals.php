<?php

namespace CloudBeds\Domain\Repositories;

use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Infrastructure\Services\DatabaseConnector\MySql;
use DateTime;
use Exception;
use PDO;

class Intervals
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
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
     * @return Interval[]
     */
    public function getAllInTimeRange(DateTime $from, DateTime $to)
    {
        return [];
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
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
            ':price' => $price
        ]);
        if (!$result) {
            throw new Exception('Failed to store new interval in database.');
        }
        return new Interval($from, $to, $price);
    }

    public function getByPk(DateTime $from, DateTime $to)
    {
        $query = sprintf('SELECT * FROM %s WHERE `from` = :from AND `to` = :to', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $result = $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        return $result ? $pst->fetch(PDO::FETCH_ASSOC)[0] : null;
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
