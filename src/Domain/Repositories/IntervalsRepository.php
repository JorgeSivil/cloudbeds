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
     * @var bool True if this class is controlling the database transaction, false if it was originated in other place
     */
    protected $controlTransactionFlag = false;

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
     * @param bool $strict False to include intervals not entirely between the range, i.e. intervals
     * that overlap by only a part.
     * @return Interval[]
     * @throws Exception
     */
    public function getAllInTimeRange(DateTime $from, DateTime $to, bool $strict = true)
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE (`from` BETWEEN :from AND :to) %s (`to` BETWEEN :from AND :to) '
            . 'OR `to` > :to and `from` < :from',
            $this->tableName,
            $strict ? 'AND' : 'OR'
        );

        $pst = $this->dbConnection->getConnection()->prepare($query);
        $result = $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        $ret = [];
        if ($result) {
            foreach ($pst->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ret[] = new Interval(new DateTime($row['from']), new DateTime($row['to']), $row['price']);
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
     * @return array
     * @throws Exception
     */
    public function getAll()
    {
        $query = sprintf('SELECT `from`, `to`, `price` FROM %s ORDER BY `from`', $this->tableName);
        $ret = [];
        foreach ($this->dbConnection->getConnection()->query($query) as $row) {
            $ret[] = new Interval(new DateTime($row['from']), new DateTime($row['to']), $row['price']);
        }

        return $ret;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return Interval|null
     * @throws Exception
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
        if ($result && $row = $pst->fetch(PDO::FETCH_ASSOC)) {
            $ret = new Interval(new DateTime($row['from']), new DateTime($row['to']), $row['price']);
        }

        return $ret;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param DateTime $newFrom
     * @param DateTime $newTo
     * @param string $price
     * @return bool
     * @throws Exception
     */
    public function update(DateTime $from, DateTime $to, DateTime $newFrom, DateTime $newTo, string $price): bool
    {
        $query = sprintf(
            'UPDATE `%s` SET `from` = :newFrom, `to` = :newTo, `price` = :price WHERE `from` = :from AND `to` = :to;',
            $this->tableName,
        );
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute([
            ':newFrom' => $newFrom->format(self::DATETIME_FORMAT),
            ':newTo' => $newTo->format(self::DATETIME_FORMAT),
            ':price' => $price,
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        return $pst->rowCount() === 1;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @return bool
     * @throws Exception
     */
    public function delete(DateTime $from, DateTime $to): bool
    {
        $query = sprintf('DELETE FROM `%s` WHERE `from` = :from AND `to` = :to', $this->tableName);
        $pst = $this->dbConnection->getConnection()->prepare($query);
        $pst->execute([
            ':from' => $from->format(self::DATETIME_FORMAT),
            ':to' => $to->format(self::DATETIME_FORMAT),
        ]);
        return $pst->rowCount() === 1;
    }

    /**
     * @param IntervalCreateRequest[] $createRequests
     * @return array
     * @throws Exception
     */
    public function createAll(array $createRequests)
    {
        if (!$createRequests) {
            return [];
        }
        $valuesConstruction = str_repeat('(?, ?, ?),', count($createRequests) - 1) . '(?, ?, ?)';
        $query = sprintf('INSERT INTO `%s` (`from`, `to`, `price`) VALUES %s', $this->tableName, $valuesConstruction);
        $values = [];
        $ret = [];
        /** @var IntervalCreateRequest $createRequest */
        foreach ($createRequests as $createRequest) {
            $values[] = $createRequest->getFrom()->format(self::DATETIME_FORMAT);
            $values[] = $createRequest->getTo()->format(self::DATETIME_FORMAT);
            $values[] = $createRequest->getPrice();
            $ret[] = new Interval($createRequest->getFrom(), $createRequest->getTo(), $createRequest->getPrice());
        }
        try {
            $this->beginTransaction();
            $pst = $this->dbConnection->getConnection()->prepare($query);
            $pst->execute($values);
            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw $e;
        }

        return $ret;
    }

    /**
     * @param IntervalDeleteRequest[] $deleteRequests
     * @return bool
     * @throws Exception
     */
    public function deleteAll(array $deleteRequests = [])
    {
        if (!$deleteRequests) {
            return true;
        }
        $inConstruction = str_repeat('(?,?),', count($deleteRequests) - 1) . '(?, ?)';
        $query = sprintf('DELETE FROM `%s` WHERE (`from`, `to`) IN (%s)', $this->tableName, $inConstruction);
        $pkValues = [];
        /** @var IntervalDeleteRequest $deleteRequest */
        foreach ($deleteRequests as $deleteRequest) {
            $pkValues[] = $deleteRequest->getFrom()->format(self::DATETIME_FORMAT);
            $pkValues[] = $deleteRequest->getTo()->format(self::DATETIME_FORMAT);
        }
        try {
            $this->beginTransaction();
            $pst = $this->dbConnection->getConnection()->prepare($query);
            $pst->execute($pkValues);
            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw $e;
        }

        return true;
    }

    /**
     * No mass update can be done in MySQL without ugly CASE... so make 1 query for each update :(
     * @param IntervalUpdateRequest[] $updateRequests
     * @return bool
     * @throws Exception
     */
    public function updateAll(array $updateRequests = [])
    {
        if (!$updateRequests) {
            return true;
        }
        foreach ($updateRequests as $updateRequest) {
            $this->update(
                $updateRequest->getFrom(),
                $updateRequest->getTo(),
                $updateRequest->getNewFrom(),
                $updateRequest->getNewTo(),
                $updateRequest->getPrice()
            );
        }

        return true;
    }

    /**
     * @param array $updateRequests
     * @param array $createRequests
     * @param array $deleteRequests
     * @return bool
     * @throws Exception
     */
    public function doMassOperations(
        array $updateRequests = [],
        array $createRequests = [],
        array $deleteRequests = []
    ) {
        $this->beginTransaction();
        try {
            $this->deleteAll($deleteRequests);
            $this->updateAll($updateRequests);
            $this->createAll($createRequests);
            $this->commitTransaction();
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw $e;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function beginTransaction(): bool
    {
        if (!$this->dbConnection->getConnection()->inTransaction()) {
            $this->dbConnection->getConnection()->beginTransaction();
            $this->controlTransactionFlag = true;
        }

        return $this->controlTransactionFlag;
    }

    /**
     * @return void
     */
    protected function commitTransaction(): void
    {
        if ($this->controlTransactionFlag) {
            $this->dbConnection->getConnection()->commit();
            $this->controlTransactionFlag = false;
        }
    }

    protected function rollBackTransaction(): void
    {
        if ($this->controlTransactionFlag) {
            $this->dbConnection->getConnection()->rollBack();
            $this->controlTransactionFlag = false;
        }
    }
}
