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

    /**
     * @dataProvider updateTestDataProvider
     * @param IntervalUpdateRequest $intervalUpdateRequest
     * @param Interval[] $existingIntervalsSetup
     * @param array $expectedIntervals
     */
    public function testIntervalUpdate(
        IntervalUpdateRequest $intervalUpdateRequest,
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
        $service->update($intervalUpdateRequest);
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
            * Test that xxxxyyyy turns into xxxxzzzzz when creating z.
            *               zzzzz
            */
            'Interval covers an existing interval #13 (integer price)' => [
                new Interval(
                    $from = new DateTime('2019-09-19 16:00:01'),
                    $to = new DateTime('2019-09-19 17:30:00'),
                    $price = '300'
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

    public function updateTestDataProvider()
    {
        return [
            'Interval is updated' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:30:00'),
                    $newTo = new DateTime('2019-09-19 15:40:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00')
                ],
                [
                    new Interval($newFrom, $newTo, $price)
                ]
            ],
            'Interval is updated #2' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 15:50:00'),
                    $price = '200.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00')
                ],
                [
                    new Interval($newFrom, $newTo, $price)
                ]
            ],
            /**
             * Test that xxxxxyyyyy turns into xxxxxxxyyy
             *           xxxxxxx
             */
            'Interval is updated and overlaps another interval #1' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 15:45:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:30:01'), new DateTime('2019-09-19 16:00:00'), '200.00')
                ],
                [
                    new Interval($newFrom, $newTo, $price),
                    new Interval(new DateTime('2019-09-19 15:45:01'), new DateTime('2019-09-19 16:00:00'), '200.00')
                ]
            ],
            /**
             * Test that xxxxxyyyyy turns into xxxxxxxxxx
             *           xxxxxxxxxx
             */
            'Interval is updated and overlaps another interval #2' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 16:00:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:30:01'), new DateTime('2019-09-19 16:00:00'), '200.00')
                ],
                [
                    new Interval($newFrom, $newTo, $price),
                ]
            ],
            /**
             * Test that xxxxxyyyyyzzzz turns into xxxxxxxxxxzz
             *           xxxxxxxxxxxx
             */
            'Interval is updated and overlaps another interval #3' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 16:15:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:30:01'), new DateTime('2019-09-19 16:00:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 16:30:00'), '100.00')
                ],
                [
                    new Interval($newFrom, $newTo, $price),
                    new Interval(new DateTime('2019-09-19 16:15:01'), new DateTime('2019-09-19 16:30:00'), '100.00')
                ]
            ],
            /**
             * Test that vvvvxxxxxyyyyyzzzz turns into vvxxxxxxxxxxzzz
             *             xxxxxxxxxxxxx
             */
            'Interval is updated and overlaps another interval #4' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 14:45:00'),
                    $newTo = new DateTime('2019-09-19 16:15:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 14:59:59'), '400.00'),
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:30:01'), new DateTime('2019-09-19 16:00:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 16:30:00'), '100.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 14:44:59'), '400.00'),
                    new Interval($newFrom, $newTo, $price),
                    new Interval(new DateTime('2019-09-19 16:15:01'), new DateTime('2019-09-19 16:30:00'), '100.00')
                ]
            ],
            /**
             * Test that vvvvxxxxxyyyyyzzzz turns into xxxxxxxxxxxxxxx as now x has the same prices as v and z
             *             xxxxxxxxxxxxx
             */
            'Interval is updated and overlaps another interval #5' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 14:45:00'),
                    $newTo = new DateTime('2019-09-19 16:15:00'),
                    $price = '400.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 14:59:59'), '400.00'),
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:30:01'), new DateTime('2019-09-19 16:00:00'), '200.00'),
                    new Interval(new DateTime('2019-09-19 16:00:01'), new DateTime('2019-09-19 16:30:00'), '400.00')
                ],
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 16:30:00'), $price),
                ]
            ],
            /**
             * Test that xxxxzzzz turns into xxxxzzzz as now x and z have the same price.
             *           xxxx
             */
            'Interval is updated price and can be merged' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 15:30:00'),
                    $price = '300.00'
                ),
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 14:59:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '400.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 15:30:00'), $price),
                ]
            ],
            /**
             * Test that xxxxzzzz turns into xxxxzzzz as now x and z have the same price. (price is not decimal)
             *           xxxx
             */
            'Interval is updated price and can be merged #2' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 15:00:00'),
                    $newTo = new DateTime('2019-09-19 15:30:00'),
                    $price = '300'
                ),
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 14:59:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '400.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 14:30:00'), new DateTime('2019-09-19 15:30:00'), '300.00'),
                ]
            ],
            /**
             * Test that xxxx        turns into xxxx
             *                 xxxx
             */
            'Interval is updated to another date that does not have any intervals' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-19 15:00:00'),
                    $to = new DateTime('2019-09-19 15:30:00'),
                    $newFrom = new DateTime('2019-09-19 16:00:00'),
                    $newTo = new DateTime('2019-09-19 16:30:00'),
                    $price = '300'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:30:00'), '400.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 16:00:00'), new DateTime('2019-09-19 16:30:00'), '300.00'),
                ]
            ],
            /**
             * Test that xxxx   yyyy  turns into xxxxyy
             *                xxxx
             */
            'Interval is updated to another date and collides with another interval' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-20 16:00:00'),
                    $to = new DateTime('2019-09-20 17:00:00'),
                    $newFrom = new DateTime('2019-09-19 16:00:00'),
                    $newTo = new DateTime('2019-09-19 17:00:00'),
                    $price = '300'
                ),
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 17:00:00'), '400.00'),
                    new Interval(new DateTime('2019-09-20 16:00:00'), new DateTime('2019-09-20 17:00:00'), '300.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 15:00:00'), new DateTime('2019-09-19 15:59:59'), '400.00'),
                    new Interval(new DateTime('2019-09-19 16:00:00'), new DateTime('2019-09-19 17:00:00'), '300.00'),
                ]
            ],
            /**
             * Test that xxxxxxxxxx  yyyy  turns into xxxxyyyyxxx (i.e. x is split in two).
             *              yyyy
             */
            'Interval is updated to another date and collides with another interval in the middle' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-20 16:00:00'),
                    $to = new DateTime('2019-09-20 17:00:00'),
                    $newFrom = new DateTime('2019-09-19 16:00:00'),
                    $newTo = new DateTime('2019-09-19 17:00:00'),
                    $price = '300'
                ),
                [
                    new Interval(new DateTime('2019-09-19 10:00:00'), new DateTime('2019-09-19 20:00:00'), '400.00'),
                    new Interval(new DateTime('2019-09-20 16:00:00'), new DateTime('2019-09-20 17:00:00'), '300.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 10:00:00'), new DateTime('2019-09-19 15:59:59'), '400.00'),
                    new Interval(new DateTime('2019-09-19 16:00:00'), new DateTime('2019-09-19 17:00:00'), '300.00'),
                    new Interval(new DateTime('2019-09-19 17:00:01'), new DateTime('2019-09-19 20:00:00'), '400.00'),
                ]
            ],
            /**
             * Test that xxxxxxxxx  yyyy  turns into xxxxyyyy (i.e. x is split in two).
             *                yyyy
             */
            'Interval is updated to another date and collides with another interval in the edge' => [
                new IntervalUpdateRequest(
                    $from = new DateTime('2019-09-20 16:00:00'),
                    $to = new DateTime('2019-09-20 17:00:00'),
                    $newFrom = new DateTime('2019-09-19 16:00:00'),
                    $newTo = new DateTime('2019-09-19 20:00:00'),
                    $price = '300'
                ),
                [
                    new Interval(new DateTime('2019-09-19 10:00:00'), new DateTime('2019-09-19 20:00:00'), '400.00'),
                    new Interval(new DateTime('2019-09-20 16:00:00'), new DateTime('2019-09-20 17:00:00'), '300.00'),
                ],
                [
                    new Interval(new DateTime('2019-09-19 10:00:00'), new DateTime('2019-09-19 15:59:59'), '400.00'),
                    new Interval(new DateTime('2019-09-19 16:00:00'), new DateTime('2019-09-19 20:00:00'), '300.00'),
                ]
            ]
        ];
    }
}
