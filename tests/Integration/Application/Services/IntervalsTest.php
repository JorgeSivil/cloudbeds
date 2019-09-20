<?php

namespace Tests\Integration\Application\Services;

use CloudBeds\Application\Services\Intervals\Intervals;
use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Domain\Repositories\Intervals as IntervalsRepository;
use DateTime;
use Tests\IntegrationTestCase;

class IntervalsTest extends IntegrationTestCase
{

    public function testIntervalIsCreated()
    {
        /** @var Intervals $service */
        $service = $this->app->getContainer()->get(Intervals::class);
        $intervalCreationRequest = new IntervalCreateRequest(
            new DateTime('2019-09-19T15:00:00Z'),
            new DateTime('2019-09-19T15:01:00Z'),
            '200.40'
        );
        $response = $service->create($intervalCreationRequest);
        $this->assertEquals(
            $intervalCreationRequest->getFrom()->format(IntervalsRepository::DATETIME_FORMAT),
            $response->getFrom()->format(IntervalsRepository::DATETIME_FORMAT)
        );
    }

    public function testIntervalSameIntervalTwiceIsCreatedWithoutFailure()
    {
        /** @var Intervals $service */
        $service = $this->app->getContainer()->get(Intervals::class);
        $intervalCreationRequest = new IntervalCreateRequest(
            new DateTime('2019-09-19T15:00:00Z'),
            new DateTime('2019-09-19T15:01:00Z'),
            '200.40'
        );
        $response = $service->create($intervalCreationRequest);
        $response2 = $service->create($intervalCreationRequest);
        $this->assertEquals(
            $intervalCreationRequest->getFrom()->format(IntervalsRepository::DATETIME_FORMAT),
            $response->getFrom()->format(IntervalsRepository::DATETIME_FORMAT)
        );
    }
}
