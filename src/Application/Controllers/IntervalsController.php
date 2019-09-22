<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Intervals\Intervals;
use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
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
                [$e->getMessage()],
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
     * @return HttpResponse
     * @throws Exception
     */
    public function createAction_post()
    {
        try {
            $dateRange = $this->parseDatesFromRequest();
        } catch (Exception $e) {
            return $this->apiError(
                'Invalid dates specified.',
                [$e->getMessage()],
                400
            );
        }

        $errors = [];
        if (!isset($dateRange['from'])) {
            $errors[] = 'Parameter \'from\' is required.';
        }
        if (!isset($dateRange['to'])) {
            $errors[] = 'Parameter \'to\' is required.';
        }
        if (!isset($_POST['price'])) {
            $errors[] = 'Parameter \'price\' is required.';
        }
        if (isset($_POST['price']) && !is_numeric($_POST['price'])) {
            $errors[] = 'Parameter \'price\' must be numeric.';
        }
        if ($errors) {
            return $this->apiError(
                'Error trying to create new interval.',
                $errors
            );
        }

        $response = $this->intervalsService->create(
            new IntervalCreateRequest($dateRange['from'], $dateRange['to'], $_POST['price'])
        );
        if (!$response->getSuccess()) {
            return $this->apiError('Error trying to create new interval.', $response->getErrors(), 500);
        }
        return $this->apiSuccess('New interval created successfully.');
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
            try {
                $from = new DateTime($_REQUEST['from']);
            } catch (Exception $e) {
                throw new Exception('The \'from\' parameter does not have a valid date time');
            }
        }
        if (isset($_REQUEST['to']) && is_string($_REQUEST['to'])) {
            try {
                $to = new DateTime($_REQUEST['to']);
            } catch (Exception $e) {
                throw new Exception('The \'to\' parameter does not have a valid date time');
            }
        }

        return [
            'from' => $from,
            'to' => $to
        ];
    }
}
