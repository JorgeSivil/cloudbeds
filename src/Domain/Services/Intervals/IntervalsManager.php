<?php

namespace CloudBeds\Domain\Services\Intervals;

use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Domain\Repositories\Intervals;
use Exception;

class IntervalsManager
{
    protected $intervalsRepository;

    public function __construct(Intervals $intervalsRepository)
    {
        $this->intervalsRepository = $intervalsRepository;
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
        $interval = $this->intervalsRepository->create($request->getFrom(), $request->getTo(), $request->getPrice());
        return $interval;
    }

    public function update(IntervalUpdateRequest $request)
    {
    }

    public function delete(IntervalDeleteRequest $request)
    {
    }
}
