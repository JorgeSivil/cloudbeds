<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Intervals\Intervals;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Response\HttpResponse;
use CloudBeds\Domain\Entities\Interval;
use DateTime;
use Exception;

class IntervalsController extends Controller
{

    /**
     * @var Intervals
     */
    protected $intervalsService;

    public function __construct(Intervals $intervalsService)
    {
        $this->intervalsService = $intervalsService;
    }

    public function indexAction()
    {
        return new HttpResponse("hehe2");
    }

    /**
     * Get all available Intervals.
     * from and to are optional parameters specifying a date range. These dates are inclusive of any range between this
     * range, even if they are not completely contained into the range, but only a part.
     * If from is not specified, then earliest date possible will be used. (1/1/1970).
     * If to is not specified, then current date time will be used (now).
     * @return HttpResponse
     * @throws Exception
     */
    public function allAction()
    {
        $intervalsList = [];
        $response = null;
        try {
            $dateRange = $this->parseDatesFromRequest();
        } catch (Exception $e) {
            return $this->apiError(
                'Invalid dates specified.',
                ['Either \'from\' or \'from\' date time strings supplied are invalid.'],
                400
            );
        }
        $dateFrom = isset($dateRange['from']) ? $dateRange['from'] : (new DateTime())->setTimestamp(0);
        $dateTo = isset($dateRange['to']) ? $dateRange['to'] : new DateTime();
        $intervalGetRequest = new IntervalGetRequest($dateFrom, $dateTo);
        $response = $this->intervalsService->getAllInTimeRange($intervalGetRequest);

        if ($response->getSuccess() === false) {
            $this->apiError('Error fetching all intervals.', $response->getErrors(), 500);
        }

        /** @var Interval $interval */
        foreach ($response->getData()['intervals'] as $interval) {
            $intervalsList[] = $interval->toArray();
        }
        return $this->apiSuccess('All intervals fetched successfully.', ['intervals' => $intervalsList]);
    }

    /**
     * @return array
     * @throws Exception
     */
    private function parseDatesFromRequest(): array
    {
        $from = null;
        $to = null;
        if (isset($_REQUEST['from']) && is_string($_REQUEST['from'])) {
            $from = new DateTime($_REQUEST['from']);
        }
        if (isset($_REQUEST['to']) && is_string($_REQUEST['to'])) {
            $to = new DateTime($_REQUEST['to']);
        }

        return [
            'from' => $from,
            'to' => $to
        ];
    }
}
