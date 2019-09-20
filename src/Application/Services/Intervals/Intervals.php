<?php

namespace CloudBeds\Application\Services\Intervals;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Domain\Services\Intervals\IntervalsManager;
use Exception;

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

    /**
     * @param IntervalCreateRequest $request
     * @return Interval
     * @throws Exception
     */
    public function create(IntervalCreateRequest $request): Interval
    {
        return $this->intervalsManager->create($request);
    }

    public function update(IntervalUpdateRequest $request)
    {
    }

    public function delete(IntervalDeleteRequest $request)
    {
    }
}
