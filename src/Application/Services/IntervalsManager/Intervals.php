<?php

namespace CloudBeds\Application\Services\IntervalsManager;

use CloudBeds\Domain\Services\Intervals\IntervalsManager;

class Intervals
{

    /**
     * @var IntervalsManager
     */
    protected $intervalsManager;

    public function __construct(IntervalsManager $intervalsManager)
    {
        $this->intervalsManager = $intervalsManager;
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
