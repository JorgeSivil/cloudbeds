<?php

namespace CloudBeds\Application\Services\IntervalsManager;

use DateTime;

class IntervalUpdateRequest
{
    /**
     * @var DateTime
     */
    protected $from;

    /**
     * @var DateTime
     */
    protected $to;

    /**
     * @var DateTime
     */
    protected $newFrom;

    /**
     * @var DateTime
     */
    protected $newTo;

    /**
     * @var string
     */
    protected $price;

    public function __construct(DateTime $from, DateTime $to, DateTime $newFrom, DateTime $newTo, string $price)
    {
        $this->from = $from;
        $this->to = $to;
        $this->newFrom = $newFrom;
        $this->newTo = $newTo;
        $this->price = $price;
    }

    /**
     * @return DateTime
     */
    public function getFrom(): DateTime
    {
        return $this->from;
    }

    /**
     * @return DateTime
     */
    public function getTo(): DateTime
    {
        return $this->to;
    }

    /**
     * @return DateTime
     */
    public function getNewFrom(): DateTime
    {
        return $this->newFrom;
    }

    /**
     * @return DateTime
     */
    public function getNewTo(): DateTime
    {
        return $this->newTo;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }
}
