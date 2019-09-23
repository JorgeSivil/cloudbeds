<?php

declare(strict_types=1);

namespace CloudBeds\Application\Controllers;

use CloudBeds\Application\Services\Intervals\Intervals;
use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
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
            $dateRange = $this->parseDatesFromRequest(['from', 'to']);
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

    public function indexAction_put()
    {
        try {
            $dateRange = $this->parseDatesFromRequest(['from', 'to', 'newFrom', 'newTo']);
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
        if (!isset($dateRange['newFrom'])) {
            $errors[] = 'Parameter \'newFrom\' is required.';
        }
        if (!isset($dateRange['newTo'])) {
            $errors[] = 'Parameter \'newTo\' is required.';
        }
        if (!isset($_REQUEST['price'])) {
            $errors[] = 'Parameter \'price\' must be numeric.';
        }
        if (isset($_REQUEST['price']) && !is_numeric($_REQUEST['price'])) {
            $errors[] = 'Parameter \'price\' must be numeric.';
        }

        if ($errors) {
            return $this->apiError(
                'Error trying to update interval.',
                $errors
            );
        }

        $response = $this->intervalsService->update(
            new IntervalUpdateRequest(
                $dateRange['from'],
                $dateRange['to'],
                $dateRange['newFrom'],
                $dateRange['newTo'],
                $_REQUEST['price']
            )
        );
        if (!$response->getSuccess()) {
            return $this->apiError('Error trying to update interval.', $response->getErrors(), 500);
        }

        return $this->apiSuccess('Interval updated.');
    }

    public function indexAction_delete()
    {
        try {
            $dateRange = $this->parseDatesFromRequest(['from', 'to']);
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

        if ($errors) {
            return $this->apiError(
                'Error trying to delete interval.',
                $errors
            );
        }

        $response = $this->intervalsService->delete(
            new IntervalDeleteRequest($dateRange['from'], $dateRange['to'])
        );
        if (!$response->getSuccess()) {
            return $this->apiError('Error trying to delete interval.', $response->getErrors(), 500);
        }

        return $this->apiSuccess('Interval deleted.');
    }

    /**
     * @return HttpResponse
     * @throws Exception
     */
    public function indexAction_post()
    {
        try {
            $dateRange = $this->parseDatesFromRequest(['from', 'to']);
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

        return $this->apiSuccess('Interval created.');
    }

    /**
     * @param array $paramsNames
     * @return array
     * @throws Exception
     */
    private function parseDatesFromRequest(array $paramsNames): array
    {
        $ret = [];
        foreach ($paramsNames as $name) {
            if (isset($_REQUEST[$name]) && is_string($_REQUEST[$name])) {
                try {
                    $ret[$name] = new DateTime($_REQUEST[$name]);
                } catch (Exception $e) {
                    throw new Exception(sprintf('The \'%s\' parameter does not have a valid date time', $name));
                }
            }
        }

        return $ret;
    }
}
