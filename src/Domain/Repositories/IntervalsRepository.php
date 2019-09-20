<?php

namespace CloudBeds\Domain\Repositories;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Infrastructure\Services\DatabaseConnector\MySql;
use DateTime;
use Exception;
use PDO;

class IntervalsRepository
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
     * @return Interval
     * @throws Exception
     */
    public function getAllInTimeRange(DateTime $from, DateTime $to)
    {
        $query = sprintf('SELECT * FROM %s WHERE `from` >= :from AND `to` <= :to', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $result = $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        $ret = [];
        if ($result) {
            foreach ($pst->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ret[] = new Interval($row['from'], $row['to'], $row['price']);
            }
        }

        return $ret;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param string $price
     * @return Interval
     * @throws Exception
     */
    public function create(DateTime $from, DateTime $to, string $price): Interval
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

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return Interval|null
     */
    public function getByPk(DateTime $from, DateTime $to)
    {
        $query = sprintf('SELECT * FROM %s WHERE `from` = :from AND `to` = :to', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $result = $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        $ret = null;
        if ($result) {
            $row = $pst->fetch(PDO::FETCH_ASSOC)[0];
            $ret = new Interval($row['from'], $row['to'], $row['price']);
        }
        return $ret;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param DateTime $newFrom
     * @param DateTime $newTo
     * @param string $price
     */
    public function update(DateTime $from, DateTime $to, DateTime $newFrom, DateTime $newTo, string $price): void
    {
        $query = sprintf(
            'UPDATE `%s` SET `from` = :newFrom, `to` = :newTo, `price` = :price WHERE `from` = :from AND `to` = :to',
            $this->tableName,
        );
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute([
            ':newFrom' => $newFrom->format(self::DATETIME_FORMAT),
            ':newTom' => $newTo->format(self::DATETIME_FORMAT),
            ':price' => $price,
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     */
    public function delete(DateTime $from, DateTime $to): void
    {
        $query = sprintf('DELETE FROM `%s` WHERE `from` = :from AND `to` = :to', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
    }

    /**
     * @param IntervalCreateRequest[] $createRequests
     * @return array
     */
    public function createAll(array $createRequests)
    {
        $valuesConstruction = str_repeat('(?, ?, ?),', count($createRequests) - 1) . '(?, ?, ?)';
        $query = sprintf('INSERT INTO `%s` (`from`, `to`, `price`) VALUES (%s)', $this->tableName, $valuesConstruction);
        $values = [];
        $ret = [];
        /** @var IntervalCreateRequest $createRequest */
        foreach ($createRequests as $createRequest) {
            $values[] = $createRequest->getFrom()->format(self::DATETIME_FORMAT);
            $values[] = $createRequest->getTo()->format(self::DATETIME_FORMAT);
            $values[] = $createRequest->getPrice();
            $ret[] = new Interval($createRequest->getFrom(), $createRequest->getTo(), $createRequest->getPrice());
        }
        $controlTransactionCommit = false;
        if (!$this->dbConnection->getConnection()->inTransaction()) {
            $this->dbConnection->getConnection()->beginTransaction();
            $controlTransactionCommit = true;
        }
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute($values);
        if ($controlTransactionCommit) {
            $this->dbConnection->getConnection()->commit();
        }
        return $ret;
    }

    /**
     * @param IntervalDeleteRequest[] $deleteRequests
     */
    public function deleteAll(array $deleteRequests = [])
    {
        $inConstruction = str_repeat('(?,?),', count($deleteRequests) - 1) . '(?, ?)';
        $query = sprintf('DELETE FROM `%s` WHERE (`from`, `to`) IN (%s)', $this->tableName, $inConstruction);
        $pkValues = [];
        /** @var IntervalDeleteRequest $deleteRequest */
        foreach ($deleteRequests as $deleteRequest) {
            $pkValues[] = $deleteRequest->getFrom()->format(self::DATETIME_FORMAT);
            $pkValues[] = $deleteRequest->getTo()->format(self::DATETIME_FORMAT);
        }
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute($pkValues);
    }

    /**
     * No mass update can be done in MySQL without ugly CASE... so make 1 query for each update :(
     * @param IntervalUpdateRequest[] $updateRequests
     */
    public function updateAll(array $updateRequests = [])
    {
        foreach ($updateRequests as $updateRequest) {
            $this->update(
                $updateRequest->getFrom(),
                $updateRequest->getTo(),
                $updateRequest->getNewFrom(),
                $updateRequest->getNewTo(),
                $updateRequest->getPrice()
            );
        }
    }

    /**
     * @param array $updateRequests
     * @param array $createRequests
     * @param array $deleteRequests
     * @throws Exception
     */
    public function doMassOperations(
        array $updateRequests = [],
        array $createRequests = [],
        array $deleteRequests = []
    ) {
        $this->dbConnection->getConnection()->beginTransaction();
        try {
            $this->deleteAll($deleteRequests);
            $this->updateAll($updateRequests);
            $this->createAll($createRequests);
        } catch (Exception $e) {
            $this->dbConnection->getConnection()->rollBack();
            throw $e;
        }
        $this->dbConnection->getConnection()->commit();
    }

}
