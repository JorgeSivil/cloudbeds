<?php

namespace CloudBeds\Application\Services\IntervalsManager;

use DateTime;

class IntervalCreateRequest
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
     * @var string
     */
    protected $price;

    public function __construct(DateTime $from, DateTime $to, string $price)
    {
        $this->from = $from;
        $this->to = $to;
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
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }
}
