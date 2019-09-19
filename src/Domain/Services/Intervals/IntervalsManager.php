<?php

namespace CloudBeds\Domain\Services\Intervals;

use CloudBeds\Application\Services\IntervalsManager\IntervalCreateRequest;
use CloudBeds\Application\Services\IntervalsManager\IntervalDeleteRequest;
use CloudBeds\Application\Services\IntervalsManager\IntervalGetRequest;
use CloudBeds\Application\Services\IntervalsManager\IntervalUpdateRequest;
use CloudBeds\Infrastructure\Services\DatabaseConnector\MySql;

class IntervalsManager
{
    protected $dbConnection;

    public function __construct(MySql $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function get(IntervalGetRequest $request)
    {

    }

    public function create(IntervalCreateRequest $request)
    {

    }

    public function update(IntervalUpdateRequest $request)
    {

    }

    public function delete(IntervalDeleteRequest $request)
    {

    }
}
