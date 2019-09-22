<?php

namespace CloudBeds\Application\Services\Intervals;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Application\Services\Response\Interfaces\InternalApiResponseInterface;
use CloudBeds\Domain\Services\IntervalsManager\IntervalsManager;
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

    /**
     * @param IntervalGetRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function get(IntervalGetRequest $request)
    {
        return $this->intervalsManager->get($request);
    }

    /**
     * @param IntervalGetRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function getAllInTimeRange(IntervalGetRequest $request)
    {
        return $this->intervalsManager->getAllInTimeRange($request);
    }

    /**
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function getAll()
    {
        return $this->intervalsManager->getAll();
    }

    /**
     * @param IntervalCreateRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function create(IntervalCreateRequest $request): InternalApiResponseInterface
    {
        return $this->intervalsManager->create($request);
    }

    /**
     * @param IntervalUpdateRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function update(IntervalUpdateRequest $request)
    {
        return $this->intervalsManager->update($request);
    }

    /**
     * @param IntervalDeleteRequest $request
     * @return InternalApiResponseInterface
     */
    public function delete(IntervalDeleteRequest $request)
    {
        return $this->intervalsManager->delete($request);
    }
}
