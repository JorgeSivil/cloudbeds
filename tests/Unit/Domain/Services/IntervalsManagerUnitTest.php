<?php

namespace Tests\Unit\Domain\Services;

use CloudBeds\Application\Services\Intervals\Requests\IntervalCreateRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalDeleteRequest;
use CloudBeds\Application\Services\Intervals\Requests\IntervalUpdateRequest;
use CloudBeds\Domain\Repositories\IntervalsRepository;
use CloudBeds\Domain\Services\IntervalsManager\IntervalsManager;
use DateTime;
use Mockery;
use ReflectionException;
use Tests\UnitTestCase;

class IntervalsManagerUnitTest extends UnitTestCase
{

    /**
     * Tests the case where there are two intervals that are continuous in time the new interval. All with same prices.
     * e.g:   XXX   ZZZ
     *           YYY
     * Should combine into XXXXXXXXX. YYY should not be created. ZZZ should be deleted.
     * @throws ReflectionException
     */
    public function testIntervalsAreMergedWhenNewIntervalIsContinuous()
    {
        $mockedRepository = Mockery::mock(IntervalsRepository::class);
        $intervalManager = new IntervalsManager($mockedRepository);
        $updateInterval = new IntervalUpdateRequest(
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 16:30'),
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 15:59'),
            '15.50'
        );
        $updateInterval2 = new IntervalUpdateRequest(
            new DateTime('2019-02-20 16:45'),
            new DateTime('2019-02-20 18:00'),
            new DateTime('2019-02-20 17:01'),
            new DateTime('2019-02-20 18:00'),
            '15.50'
        );
        $createInterval = new IntervalCreateRequest(
            new DateTime('2019-02-20 16:00'),
            new DateTime('2019-02-20 17:00'),
            '15.50'
        );
        $method = $this->getProtectedMethod(IntervalsManager::class, 'mergeContinuousIntervals');
        $mergedIntervals = $method->invokeArgs(
            $intervalManager,
            [
                [$updateInterval, $updateInterval2],
                [$createInterval]
            ]
        );
        $newUpdateRequests = $mergedIntervals['updateRequests'];
        $newCreateRequests = $mergedIntervals['createRequests'];
        $newDeleteRequests = $mergedIntervals['deleteRequests'];
        $this->assertEquals(1, sizeof($newUpdateRequests));
        $this->assertEquals(0, sizeof($newCreateRequests));
        $this->assertEquals(1, sizeof($newDeleteRequests));

        /** @var IntervalUpdateRequest $newInterval */
        $newInterval = $newUpdateRequests[0];
        /** @var IntervalDeleteRequest $newIntervalDelete */
        $newIntervalDelete = $newDeleteRequests[0];

        // Check that PK remains intact.
        $this->assertSame('2019-02-20 15:00:00', $newInterval->getFrom()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame('2019-02-20 16:30:00', $newInterval->getTo()->format(IntervalsRepository::DATETIME_FORMAT));

        // Check that first interval now ends at last interval's time.
        $this->assertSame('2019-02-20 15:00:00', $newInterval->getNewFrom()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame('2019-02-20 18:00:00', $newInterval->getNewTo()->format(IntervalsRepository::DATETIME_FORMAT));

        // Check that last update interval gets deleted because it was absorbed by the first.
        $this->assertSame(
            $updateInterval2->getFrom()->format(IntervalsRepository::DATETIME_FORMAT),
            $newIntervalDelete->getFrom()->format(IntervalsRepository::DATETIME_FORMAT)
        );
        $this->assertSame(
            $updateInterval2->getTo()->format(IntervalsRepository::DATETIME_FORMAT),
            $newIntervalDelete->getTo()->format(IntervalsRepository::DATETIME_FORMAT)
        );
    }

    /**
     * Tests the case where there are two intervals that are continuous in time the new interval,
     * but prices don't overlap.
     * e.g:   XXX   ZZZ
     *           YYY
     * Should not combine
     * @throws ReflectionException
     */
    public function testIntervalsAreNotMergedWhenNewIntervalIsContinuousWithDifferentPrice()
    {
        $mockedRepository = Mockery::mock(IntervalsRepository::class);
        $intervalManager = new IntervalsManager($mockedRepository);
        $updateInterval = new IntervalUpdateRequest(
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 16:30'),
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 15:59'),
            '15'
        );
        $updateInterval2 = new IntervalUpdateRequest(
            new DateTime('2019-02-20 16:45'),
            new DateTime('2019-02-20 18:00'),
            new DateTime('2019-02-20 17:01'),
            new DateTime('2019-02-20 18:00'),
            '15'
        );
        $createInterval = new IntervalCreateRequest(
            new DateTime('2019-02-20 16:00'),
            new DateTime('2019-02-20 17:00'),
            '17'
        );
        $method = $this->getProtectedMethod(IntervalsManager::class, 'mergeContinuousIntervals');
        $mergedIntervals = $method->invokeArgs(
            $intervalManager,
            [
                [$updateInterval, $updateInterval2],
                [$createInterval]
            ]
        );
        $newUpdateRequests = $mergedIntervals['updateRequests'];
        $newCreateRequests = $mergedIntervals['createRequests'];
        $newDeleteRequests = $mergedIntervals['deleteRequests'];
        $this->assertEquals(2, sizeof($newUpdateRequests));
        $this->assertEquals(1, sizeof($newCreateRequests));
        $this->assertEquals(0, sizeof($newDeleteRequests));
        $this->assertSame($updateInterval, $newUpdateRequests[0]);
        $this->assertSame($updateInterval2, $newUpdateRequests[1]);
        $this->assertSame($createInterval, $newCreateRequests[0]);
    }

    /**
     * Tests the case where there are two intervals that are continuous, prices for X and Y are the same.
     * e.g:   XXX   ZZZ
     *           YYY
     * Should combine into XXXXXXZZZ
     * @throws ReflectionException
     */
    public function testIntervalsAreMergedWhenNewIntervalIsContinuousWithFirst()
    {
        $mockedRepository = Mockery::mock(IntervalsRepository::class);
        $intervalManager = new IntervalsManager($mockedRepository);
        $updateInterval = new IntervalUpdateRequest(
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 16:30'),
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 15:59'),
            '15'
        );
        $updateInterval2 = new IntervalUpdateRequest(
            new DateTime('2019-02-20 16:45'),
            new DateTime('2019-02-20 18:00'),
            new DateTime('2019-02-20 17:01'),
            new DateTime('2019-02-20 18:00'),
            '16'
        );
        $createInterval = new IntervalCreateRequest(
            new DateTime('2019-02-20 16:00'),
            new DateTime('2019-02-20 17:00'),
            '15'
        );
        $method = $this->getProtectedMethod(IntervalsManager::class, 'mergeContinuousIntervals');
        $mergedIntervals = $method->invokeArgs(
            $intervalManager,
            [
                [$updateInterval, $updateInterval2],
                [$createInterval]
            ]
        );
        $newUpdateRequests = $mergedIntervals['updateRequests'];
        $newCreateRequests = $mergedIntervals['createRequests'];
        $newDeleteRequests = $mergedIntervals['deleteRequests'];
        /** @var IntervalUpdateRequest $newUpdateInterval */
        $newUpdateInterval = $newUpdateRequests[0];
        $this->assertEquals(2, sizeof($newUpdateRequests));
        $this->assertEquals(0, sizeof($newCreateRequests));
        $this->assertEquals(0, sizeof($newDeleteRequests));

        // Check that PK remains intact.
        $this->assertSame(
            $updateInterval->getFrom()->format(IntervalsRepository::DATETIME_FORMAT),
            $newUpdateInterval->getFrom()->format(IntervalsRepository::DATETIME_FORMAT)
        );
        $this->assertSame(
            $updateInterval->getTo()->format(IntervalsRepository::DATETIME_FORMAT),
            $newUpdateInterval->getTo()->format(IntervalsRepository::DATETIME_FORMAT)
        );

        $this->assertSame('2019-02-20 15:00:00', $newUpdateInterval->getNewFrom()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame('2019-02-20 17:00:00', $newUpdateInterval->getNewTo()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame($updateInterval2, $newUpdateRequests[1]);
    }

    /**
     * This tests te case where there are two intervals that are continuous in time, prices for Y and Z are the same.
     * e.g:   XXX   ZZZ
     *           YYY
     * Should combine into XXXZZZZZZ
     * @throws ReflectionException
     */
    public function testIntervalsAreMergedWhenNewIntervalIsContinuousWithLast()
    {
        $mockedRepository = Mockery::mock(IntervalsRepository::class);
        $intervalManager = new IntervalsManager($mockedRepository);
        $updateInterval = new IntervalUpdateRequest(
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 16:30'),
            new DateTime('2019-02-20 15:00'),
            new DateTime('2019-02-20 15:59'),
            '15'
        );
        $updateInterval2 = new IntervalUpdateRequest(
            new DateTime('2019-02-20 16:45'),
            new DateTime('2019-02-20 18:00'),
            new DateTime('2019-02-20 17:01'),
            new DateTime('2019-02-20 18:00'),
            '17'
        );
        $createInterval = new IntervalCreateRequest(
            new DateTime('2019-02-20 16:00'),
            new DateTime('2019-02-20 17:00'),
            '17'
        );
        $method = $this->getProtectedMethod(IntervalsManager::class, 'mergeContinuousIntervals');
        $mergedIntervals = $method->invokeArgs(
            $intervalManager,
            [
                [$updateInterval, $updateInterval2],
                [$createInterval]
            ]
        );
        $newUpdateRequests = $mergedIntervals['updateRequests'];
        $newCreateRequests = $mergedIntervals['createRequests'];
        $newDeleteRequests = $mergedIntervals['deleteRequests'];
        /** @var IntervalUpdateRequest $newUpdateInterval */
        $newUpdateInterval = $newUpdateRequests[1];
        $this->assertEquals(2, sizeof($newUpdateRequests));
        $this->assertEquals(0, sizeof($newCreateRequests));
        $this->assertEquals(0, sizeof($newDeleteRequests));

        // Check that PK remains intact.
        $this->assertSame(
            $updateInterval2->getFrom()->format(IntervalsRepository::DATETIME_FORMAT),
            $newUpdateInterval->getFrom()->format(IntervalsRepository::DATETIME_FORMAT)
        );
        $this->assertSame(
            $updateInterval2->getTo()->format(IntervalsRepository::DATETIME_FORMAT),
            $newUpdateInterval->getTo()->format(IntervalsRepository::DATETIME_FORMAT)
        );

        $this->assertSame('2019-02-20 16:00:00', $newUpdateInterval->getNewFrom()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame('2019-02-20 18:00:00', $newUpdateInterval->getNewTo()->format(IntervalsRepository::DATETIME_FORMAT));
        $this->assertSame($updateInterval, $newUpdateRequests[0]);
    }
}
