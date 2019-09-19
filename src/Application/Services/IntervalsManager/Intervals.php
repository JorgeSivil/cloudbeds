<?php

namespace CloudBeds\Application\Services\IntervalsManager;

use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalUpdateRequest;
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
        return $this->intervalsManager->create($request);
    }

    public function update(IntervalUpdateRequest $request)
    {

    }

    public function delete(IntervalDeleteRequest $request)
    {

    }
}
