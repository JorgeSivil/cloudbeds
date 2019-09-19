<?php

namespace Tests\Integration\Application\Services;

use CloudBeds\Application\Services\IntervalsManager\Intervals;
use CloudBeds\Application\Services\IntervalsManager\Requests\IntervalCreateRequest;
use DateTime;
use Tests\IntegrationTestCase;

class IntervalsManagerTests extends IntegrationTestCase
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
        $service->create($intervalCreationRequest);
    }
}
