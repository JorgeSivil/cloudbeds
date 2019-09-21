<?php

/** @noinspection PhpDocMissingThrowsInspection,PhpUnhandledExceptionInspection */

namespace Tests\Integration\Application\Services;

use CloudBeds\Application\Services\Intervals\Intervals;
use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalGetRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Entities\Interval;
use DateTime;
use Tests\IntegrationTestCase;

class IntervalsTest extends IntegrationTestCase
{
    /**
     *
     */
    public function testExistingIntervalIsUpdatedSuccessfully()
    {
        /** @var Intervals $service */
        $service = $this->app->getContainer()->get(Intervals::class);
        $intervalCreateRequest = new IntervalCreateRequest(
            $from = new DateTime('2019-09-19 15:00:00'),
            $to = new DateTime('2019-09-19 15:30:00'),
            $price = '200.40'
        );
        $service->create($intervalCreateRequest);

        $intervalUpdateRequest = new IntervalUpdateRequest(
            $from,
            $to,
            $newFrom = new DateTime('2019-09-19 15:00:00'),
            $newTo = new DateTime('2019-09-19 16:00:00'),
            $price = '250'
        );
        $service->update($intervalUpdateRequest);

        // Get original interval
        $intervalGetRequest = new IntervalGetRequest($from, $to);
        $response = $service->get($intervalGetRequest);

        // Get updated interval
        $intervalGetRequest = new IntervalGetRequest($newFrom, $newTo);
        $response2 = $service->get($intervalGetRequest);

        /** @var Interval $interval */
        $interval = $response2->getData()['interval'];

        $this->assertFalse($response->getSuccess());
        $this->assertTrue($response2->getSuccess());
        $this->assertEquals($newFrom, $interval->getFrom());
        $this->assertEquals($newTo, $interval->getTo());
        $this->assertEquals($price . '.00', $interval->getPrice());
    }

    /**
     * Test that existing interval update request that overlaps another interval also updates the overlapped interval.
     * e.g. xxxyyy => xxxxx => results in xxxxxy
     */
    public function testExistingIntervalsAreUpdatedSuccessfullyWhenUpdateOverlaps()
    {
        /** @var Intervals $service */
        $service = $this->app->getContainer()->get(Intervals::class);
        $intervalCreateRequest = new IntervalCreateRequest(
            $from = new DateTime('2019-09-19 15:00:00'),
            $to = new DateTime('2019-09-19 15:30:00'),
            $price = '200.00'
        );
        $intervalCreateRequest2 = new IntervalCreateRequest(
            $from2 = new DateTime('2019-09-19 15:31:00'),
            $to2 = new DateTime('2019-09-19 16:00:00'),
            $price2 = '250.00'
        );
        $service->create($intervalCreateRequest);
        $service->create($intervalCreateRequest2);

        $intervalUpdateRequest = new IntervalUpdateRequest(
            $from,
            $to,
            $newFrom = new DateTime('2019-09-19 15:00:00'),
            $newTo = new DateTime('2019-09-19 15:45:00'),
            $price = '200.00'
        );
        $service->update($intervalUpdateRequest);

        $response = $service->getAllInTimeRange(new IntervalGetRequest($from, $to2));
        $this->assertTrue($response->getSuccess());

        /** @var Interval[] $intervals */
        $intervals = $response->getData()['intervals'];

        $this->assertEquals(2, count($intervals));
        $this->assertEquals($from, $intervals[0]->getFrom());
        $this->assertEquals($newTo, $intervals[0]->getTo());
        $this->assertEquals($price, $intervals[0]->getPrice());
        $this->assertEquals($newTo->modify('+1 second'), $intervals[1]->getFrom());
        $this->assertEquals($to2, $intervals[1]->getTo());
        $this->assertEquals($price2, $intervals[1]->getPrice());
    }

    /**
     * @dataProvider creationTestDataProvider
     * @param Interval $intervalToCreate
     * @param Interval[] $existingIntervalsSetup
     * @param array $expectedIntervals
     */
    public function testIntervalCreation(
        Interval $intervalToCreate,
        array $existingIntervalsSetup,
        array $expectedIntervals
    ) {
        /** @var Intervals $service */
        $service = $this->app->getContainer()->get(Intervals::class);
        foreach ($existingIntervalsSetup as $interval) {
            $service->create(
                new IntervalCreateRequest($interval->getFrom(), $interval->getTo(), $interval->getPrice())
            );
        }
        $service->create(
            new IntervalCreateRequest(
                $intervalToCreate->getFrom(),
                $intervalToCreate->getTo(),
                $intervalToCreate->getPrice()
            )
        );
        $resultingIntervals = $service->getAll()->getData()['intervals'];
        $this->assertEquals($expectedIntervals, $resultingIntervals);
    }

    public function creationTestDataProvider()
    {
        return [
            'Interval is created' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $price = '200.00'
                ),
                [],
                [
                    new Interval($from, $to, $price)
                ]
            ],
            'Interval is created with integer price' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $price = '200'
                ),
                [],
                [
                    new Interval($from, $to, $price . '.00')
                ]
            ],
            'Interval is created with one decimal' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $price = '200.1'
                ),
                [],
                [
                    new Interval($from, $to, $price . '0')
                ]
            ],
            /*
            * Test that xxx   turns into zzzzz when creating z. x and z have the same price.
            *           zzzzz
            */
            'Interval covers an existing interval #1' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 16:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '200.00')
                ],
                [
                    new Interval($from, $to, $price)
                ]
            ],
            /*
            * Test that xxxxx remains xxxxx when creating z. x and z have the same properties.
            *           zzzzz
            */
            'Interval covers an existing interval #2' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 16:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '200.00')
                ],
                [
                    new Interval($from, $to, $price)
                ]
            ],
            /*
            * Test that xxxxx turns into zzzzz when creating z, as z has different price.
            *           zzzzz
            */
            'Interval covers an existing interval #3' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 16:00:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '200.00')
                ],
                [
                    new Interval($from, $to, $price)
                ]
            ],
            /*
            * Test that xxxyyyy turns into zzzzzyy when creating z. x and z have the same price
            *           zzzzz
            */
            'Interval covers an existing interval #4' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:45:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 15:31:00'), new DateTime('2019-09-19 16:00:00'), '300.00')
                ],
                [
                    new Interval($from, $to, $price),
                    new Interval(new DateTime('2019-09-19 15:45:01'), new DateTime('2019-09-19 16:00:00'), '300.00')
                ]
            ],
            /*
            * Test that xxxyyyy    turns into zzzzzzzzz when creating z.
            *           zzzzzzzzz
            */
            'Interval covers an existing interval #5' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 17:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 15:31:00'), new DateTime('2019-09-19 16:00:00'), '300.00')
                ],
                [
                    new Interval($from, $to, $price),
                ]
            ],
            /*
            * Test that xxxyyyy    turns into zzzzzzzzzzz when creating z.
            *         zzzzzzzzzzz
            */
            'Interval covers an existing interval #6' => [
                new Interval(
                    $from = new DateTime('2019-09-19 14:00:00'),
                    $to = new DateTime('2019-09-19 17:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 15:31:00'), new DateTime('2019-09-19 16:00:00'), '300.00')
                ],
                [
                    new Interval($from, $to, $price),
                ]
            ],
            /*
            * Test that xxxyyyy    turns into xzzzzzzzz when creating z.
            *            zzzzzzzz
            */
            'Interval covers an existing interval #7' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:20:00'),
                    $to = new DateTime('2019-09-19 17:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 15:31:00'), new DateTime('2019-09-19 16:00:00'), '300.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:19:59'), '250.00'),
                    new Interval($from, $to, $price),
                ]
            ],
            /*
            * Test that xxxyyyy turns into zzzzzzz when creating z.
            *           zzzzzzz
            */
            'Interval covers an existing interval #8' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 17:00:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '300.00')
                ],
                [
                    new Interval($from, $to, $price),
                ]
            ],
            /*
            * Test that xxxxyyyy turns into xxzzzzyy when creating z.
            *             zzzz
            */
            'Interval covers an existing interval #9' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:30:00'),
                    $to = new DateTime('2019-09-19 16:30:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '300.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:29:59'), '250.00'),
                    new Interval($from, $to, $price),
                    new Interval(new DateTime('2019-09-19 16:30:01'), new DateTime('2019-09-19 17:00:00'), '300.00'),
                ]
            ],
            /*
            * Test that xxxxyyyy turns into xxxxxxyy when creating z, as z and x have the same price.
            *             zzzz
            */
            'Interval covers an existing interval #10' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:30:00'),
                    $to = new DateTime('2019-09-19 16:30:00'),
                    $price = '250.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '300.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), $to, '250.00'),
                    new Interval(new DateTime('2019-09-19 16:30:01'), new DateTime('2019-09-19 17:00:00'), '300.00'),
                ]
            ],
            /*
            * Test that xxxxyyyy turns into xxyyyyyyy when creating z, as z and y have the same price.
            *             zzzz
            */
            'Interval covers an existing interval #11' => [
                new Interval(
                    $from = new DateTime('2019-09-19 15:30:00'),
                    $to = new DateTime('2019-09-19 16:30:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '300.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:29:59'), '250.00'),
                    new Interval($from, new DateTime('2019-09-19 17:00:00'), '300.00'),
                ]
            ],
            /*
            * Test that xxxxyyyy turns into xxxxzzzzz when creating z.
            *               zzzzz
            */
            'Interval covers an existing interval #12' => [
                new Interval(
                    $from = new DateTime('2019-09-19 16:00:01'),
                    $to = new DateTime('2019-09-19 17:30:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '200.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval($from, $to, '300.00'),
                ]
            ],
            /*
            * Test that xxxxyyyy turns into zzzzzyyyy when creating z.
            *          zzzzz
            */
            'Interval covers an existing interval #13' => [
                new Interval(
                    $from = new DateTime('2019-09-19 14:30:00'),
                    $to = new DateTime('2019-09-19 16:00:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 16:00:00'), '250.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '200.00')
                ],
                [
                    new Interval($from, $to, '300.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 17:00:00'), '200.00')
                ]
            ]
        ];
    }
}
