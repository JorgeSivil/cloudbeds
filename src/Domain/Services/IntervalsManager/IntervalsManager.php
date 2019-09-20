<?php

namespace CloudBeds\Domain\Services\IntervalsManager;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Entities\Interval;
use CloudBeds\Domain\Repositories\IntervalsRepository;
use DateTime;
use Exception;

class IntervalsManager
{
    protected $intervalsRepository;
    protected $secondsToleranceToMerge = 60;

    public function __construct(IntervalsRepository $intervalsRepository)
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
        $searchDatetimeFrom = new DateTime($request->getFrom()->format(DATE_ATOM));
        $searchDatetimeTo = new DateTime($request->getTo()->format(DATE_ATOM));
        /** @var Interval[] $intervals */
        // This allows us to merge intervals properly
        $intervals = $this->intervalsRepository->getAllInTimeRange(
            $searchDatetimeFrom->modify(sprintf('-%d seconds', $this->secondsToleranceToMerge)),
            $searchDatetimeTo->modify(sprintf('+%d seconds', $this->secondsToleranceToMerge))
        );

        $deleteRequests = [];
        $updateRequests = [];
        $createRequests = [];

        foreach ($intervals as $interval) {
            // Existing interval is completely covered by new interval.
            if ($interval->getFrom() > $request->getFrom() && $interval->getTo() > $request->getTo()) {
                $deleteRequests[] = new IntervalDeleteRequest($interval->getFrom(), $interval->getTo());
                continue;
            }

            // New interval is completely covered by existing interval.
            if ($interval->getFrom() <= $request->getFrom()
                && $interval->getTo() >= $request->getTo()
                && $interval->getPrice() === $request->getPrice()
            ) {
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
                continue;
            }

            // Existing interval overlaps new interval by right. Change only existing DateTime To.
            if ($interval->getTo() >= $request->getFrom()) {
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

            // Existing interval overlaps new interval by left. Change only existing DateTime From.
            if ($interval->getFrom() <= $request->getTo()) {
                $newFrom = clone $request->getTo();
                $intervalsToUpdate[] = new IntervalUpdateRequest(
                    $interval->getFrom(),
                    $interval->getTo(),
                    $newFrom->modify('+1 second'),
                    $request->getTo(),
                    $interval->getPrice()
                );
                continue;
            }

            // Default case
            $createRequests[] = $request;
        }

        // All intervals are now continuous, if any. We still have to merge them.

        $operations = $this->mergeContinuousIntervals($updateRequests, $createRequests, $deleteRequests);

        try {
            $interval = $this->intervalsRepository->create($request->getFrom(), $request->getTo(), $request->getPrice());
        } catch (Exception $e) {

        }

        return $interval;
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
                if ($request1->getPrice() === $request2->getPrice()) {
                    $secondsDifference = abs(
                        $request1->getNewTo()->getTimestamp() - $request2->getNewFrom()->getTimestamp()
                    );
                    if ($secondsDifference <= $this->secondsToleranceToMerge) {
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
                if ($request1->getPrice() === $request2->getPrice()) {
                    $secondsDifference = abs(
                        $request1->getNewTo()->getTimestamp() - $request2->getFrom()->getTimestamp()
                    );
                    if ($secondsDifference <= $this->secondsToleranceToMerge) {
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
                    if ($secondsDifference <= $this->secondsToleranceToMerge) {
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

    public function update(IntervalUpdateRequest $request)
    {
    }

    public function delete(IntervalDeleteRequest $request)
    {
    }
}
