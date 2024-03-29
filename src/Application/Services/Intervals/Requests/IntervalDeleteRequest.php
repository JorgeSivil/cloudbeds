<?php

namespace CloudBeds\Application\Services\Intervals\Requests;

use DateTime;

class IntervalDeleteRequest
{
    /** @var DateTime */
    protected $from;

    /** @var DateTime */
    protected $to;

    public function __construct(DateTime $from, DateTime $to)
    {
        $this->from = $from;
        $this->to = $to;
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
}
