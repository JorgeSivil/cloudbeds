<?php

namespace CloudBeds\Domain\Services\IntervalsManager;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Application\Services\Response\Interfaces\InternalApiResponseInterface;
use CloudBeds\Application\Services\Response\ResponseFactory;
use CloudBeds\Application\Services\Service;
use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Domain\Repositories\IntervalsRepository;
use DateTime;
use Exception;

class IntervalsManager extends Service
{
    protected $intervalsRepository;
    protected $secondsDiffTolerance = 60;

    /**
     * IntervalsManager constructor.
     * @param IntervalsRepository $intervalsRepository
     * @param ResponseFactory $responseFactory
     */
    public function __construct(IntervalsRepository $intervalsRepository, ResponseFactory $responseFactory)
    {
        $this->intervalsRepository = $intervalsRepository;
        parent::__construct($responseFactory);
    }

    /**
     * @param IntervalGetRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function get(IntervalGetRequest $request)
    {
        $interval = $this->intervalsRepository->getByPk($request->getFrom(), $request->getTo());
        if ($interval) {
            return $this->success('Interval successfully fetched.', ['interval' => $interval]);
        }

        return $this->error('Not found.', [], ['No interval found matching given dates.']);
    }

    /**
     * @param IntervalGetRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function getAllInTimeRange(IntervalGetRequest $request)
    {
        $intervals = $this->intervalsRepository->getAllInTimeRange($request->getFrom(), $request->getTo(), false);
        if ($intervals) {
            return $this->success('Intervals successfully fetched.', ['intervals' => $intervals]);
        }

        return $this->error('Not found.', [], ['No intervals found matching given dates.']);
    }

    /**
     * @param IntervalUpdateRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function update(IntervalUpdateRequest $request)
    {
        /** @var Interval[] $intervals */
        $intervals = $this->getAllIntervalsWithinTimeDifferenceTolerance(
            $request->getNewFrom(),
            $request->getNewTo(),
            $this->secondsDiffTolerance
        );

        $intervalsFound = count($intervals);

        $isSameInterval = false;
        if ($intervalsFound === 1) {
            /** @var Interval $interval */
            $interval = $intervals[0];
            if ($interval->getFrom() == $request->getFrom() && $interval->getTo() == $request->getTo()) {
                $isSameInterval = true;
            }
        }
        if ($intervalsFound === 0 || $isSameInterval) {
            $this->intervalsRepository->update(
                $request->getFrom(),
                $request->getTo(),
                $request->getNewFrom(),
                $request->getNewTo(),
                $request->getPrice()
            );

            return $this->success('Interval successfully updated.');
        }

        // Cover the case were an interval is moved and now collides with existing intervals.
        // TODO: Re-write logic for Update not using Create.
        $deleteRequest = new IntervalDeleteRequest($request->getFrom(), $request->getTo());
        $this->delete(($deleteRequest));

        $createRequest = new IntervalCreateRequest($request->getNewFrom(), $request->getNewTo(), $request->getPrice());
        $response = $this->create($createRequest);

        return $response
            ? $this->success('Interval successfully updated.')
            : $this->error('Failure trying to update interval.', [], $response->getErrors());
    }

    /**
     * @param IntervalCreateRequest $request
     * @return InternalApiResponseInterface
     * @throws Exception
     */
    public function create(IntervalCreateRequest $request): InternalApiResponseInterface
    {
        /** @var Interval[] $intervals */
        $intervals = $this->getAllIntervalsWithinTimeDifferenceTolerance(
            $request->getFrom(),
            $request->getTo(),
            $this->secondsDiffTolerance
        );

        $deleteRequests = [];
        $updateRequests = [];
        $createRequests = [];

        $shouldCreate = true;
        $updatedCoveredInterval = false;
        foreach ($intervals as $interval) {
            // Existing interval is completely covered by new interval. Existing can be updated to match new interval.
            if ($interval->getFrom() >= $request->getFrom()
                && $interval->getTo() <= $request->getTo()
            ) {
                if ($updatedCoveredInterval) { // An interval was already selected to be updated to cover new interval.
                    $deleteRequests[] = new IntervalDeleteRequest($interval->getFrom(), $interval->getTo());
                } else {
                    $updateRequests[] = new IntervalUpdateRequest(
                        $interval->getFrom(),
                        $interval->getTo(),
                        $request->getFrom(),
                        $request->getTo(),
                        $request->getPrice()
                    );
                    $updatedCoveredInterval = true;
                }
                $shouldCreate = false;
                continue;
            }

            // New interval is completely covered by existing interval with same price. Do nothing.
            if ($interval->getFrom() <= $request->getFrom()
                && $interval->getTo() >= $request->getTo()
                && $interval->getPrice() === $request->getPrice()
            ) {
                $shouldCreate = false;
                continue;
            }

            // Same interval datetimes, different price. Update instead of deletion and creation
            if ($interval->getFrom() == $request->getFrom()
                && $interval->getTo() == $request->getTo()
                && $interval->getPrice() != $request->getPrice()
            ) {
                $updateRequests[] = new IntervalUpdateRequest(
                    $request->getFrom(),
                    $request->getTo(),
                    $request->getFrom(),
                    $request->getTo(),
                    $request->getPrice()
                );
                $shouldCreate = false;
                continue;
            }

            // Existing interval overlaps new interval by new interval's left. Change only existing DateTime To.
            if ($interval->getFrom() <= $request->getFrom() && $interval->getTo() >= $request->getFrom()) {
                $newTo = clone $request->getFrom();
                $updateRequests[] = new IntervalUpdateRequest(
                    $interval->getFrom(),
                    $interval->getTo(),
                    $interval->getFrom(),
                    $newTo->modify('-1 second'),
                    $interval->getPrice()
                );
                continue;
            }

            // Existing interval overlaps new interval by new interval's right. Change only existing DateTime From.
            if ($interval->getFrom() <= $request->getTo() && $interval->getTo() >= $request->getTo()) {
                $newFrom = clone $request->getTo();
                $updateRequests[] = new IntervalUpdateRequest(
                    $interval->getFrom(),
                    $interval->getTo(),
                    $newFrom->modify('+1 second'),
                    $interval->getTo(),
                    $interval->getPrice()
                );
                continue;
            }

            $updateRequests[] = new IntervalUpdateRequest(
                $interval->getFrom(),
                $interval->getTo(),
                $interval->getFrom(),
                $interval->getTo(),
                $interval->getPrice()
            );
        }

        // Default case
        if ($shouldCreate) {
            $createRequests[] = $request;
        }

        // All intervals are now continuous, if any. We still have to merge them.
        $operations = $this->mergeContinuousIntervals($updateRequests, $createRequests, $deleteRequests);

        try {
            $this->intervalsRepository->doMassOperations(
                $operations['updateRequests'],
                $operations['createRequests'],
                $operations['deleteRequests']
            );
        } catch (Exception $e) {
            return $this->error(
                'Failure trying to create new interval.',
                [],
                [$e->getMessage()]
            );
        }

        return $this->success('New interval successfully created.');
    }

    /**
     * Merge intervals that have linear continuity and same price.
     * It is assumed that there are no overlaps between intervals.
     * @param IntervalUpdateRequest[] $updateRequests
     * @param IntervalCreateRequest[] $createRequests
     * @param array $deleteRequests
     * @return array
     */
    protected function mergeContinuousIntervals(
        array $updateRequests = [],
        array $createRequests = [],
        array $deleteRequests = []
    ) {
        $merged = false;
        $ret = [
            'updateRequests' => $updateRequests,
            'createRequests' => $createRequests,
            'deleteRequests' => $deleteRequests,
        ];
        // Between updates
        for ($i = 0; $i < sizeof($updateRequests); $i++) {
            $request1 = $updateRequests[$i];
            for ($j = $i + 1; $j < sizeof($updateRequests); $j++) {
                $request2 = $updateRequests[$j];
                if ($request1->getPrice() == $request2->getPrice()) {
                    $secondsDifference = abs(
                        $request1->getNewTo()->getTimestamp() - $request2->getNewFrom()->getTimestamp()
                    );
                    if ($secondsDifference <= $this->secondsDiffTolerance) {
                        $updateRequests[$i] = new IntervalUpdateRequest(
                            $request1->getFrom(),
                            $request1->getTo(),
                            $request1->getNewFrom(),
                            $request2->getNewTo(),
                            $request1->getPrice()
                        );
                        $deleteRequests[] = new IntervalDeleteRequest($request2->getFrom(), $request2->getTo());
                        unset($updateRequests[$j]);
                        $updateRequests = array_values($updateRequests); // re-index
                        $merged = true;
                        break 2;
                    }
                }
            }
        }

        // Between updates and creations
        for ($i = 0; $i < sizeof($updateRequests); $i++) {
            $request1 = $updateRequests[$i];
            for ($j = 0; $j < sizeof($createRequests); $j++) {
                $request2 = $createRequests[$j];
                if ($request1->getPrice() == $request2->getPrice()) {
                    $secondsDifference = abs(
                        $request1->getNewTo()->getTimestamp() - $request2->getFrom()->getTimestamp()
                    );
                    if ($secondsDifference <= $this->secondsDiffTolerance) {
                        $updateRequests[$i] = new IntervalUpdateRequest(
                            $request1->getFrom(),
                            $request1->getTo(),
                            $request1->getNewFrom(),
                            $request2->getTo(),
                            $request1->getPrice()
                        );
                        unset($createRequests[$j]);
                        $merged = true;
                        break 2;
                    }
                    $secondsDifference = abs(
                        $request1->getNewFrom()->getTimestamp() - $request2->getTo()->getTimestamp()
                    );
                    if ($secondsDifference <= $this->secondsDiffTolerance) {
                        $updateRequests[$i] = new IntervalUpdateRequest(
                            $request1->getFrom(),
                            $request1->getTo(),
                            $request2->getFrom(),
                            $request1->getNewTo(),
                            $request1->getPrice()
                        );
                        unset($createRequests[$j]);
                        $merged = true;
                        break 2;
                    }
                }
            }
        }
        if ($merged) {
            return $this->mergeContinuousIntervals($updateRequests, $createRequests, $deleteRequests);
        }

        return $ret;
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     * @param $secondsDiffTolerance
     * @return Interval[]
     * @throws Exception
     */
    protected function getAllIntervalsWithinTimeDifferenceTolerance(
        DateTime $from,
        DateTime $to,
        $secondsDiffTolerance
    ) {
        $searchDatetimeFrom = clone $from;
        $searchDatetimeTo = clone $to;
        /** @var Interval[] $intervals */
        // This allows us to merge intervals properly
        return $this->intervalsRepository->getAllInTimeRange(
            $searchDatetimeFrom->modify(sprintf('-%d seconds', $secondsDiffTolerance)),
            $searchDatetimeTo->modify(sprintf('+%d seconds', $secondsDiffTolerance)),
            false
        );
    }

    /**
     * @throws Exception
     */
    public function getAll()
    {
        return $this->success(
            'All intervals successfully fetched.',
            ['intervals' => $this->intervalsRepository->getAll()]
        );
    }

    public function delete(IntervalDeleteRequest $request)
    {
        $errors = [];
        $response = false;
        try {
            $response = $this->intervalsRepository->delete($request->getFrom(), $request->getTo());
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
        return $response
            ? $this->success('Interval successfully updated.')
            : $this->error('Failure trying to update interval.', [], $errors);
    }
}
