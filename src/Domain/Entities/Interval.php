<?php

namespace CloudBeds\Domain\Entities;

use CloudBeds\Domain\Interfaces\Arrayable;
use DateTime;

class Interval implements Arrayable
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

    /**
     * Interval constructor.
     * @param DateTime $from
     * @param DateTime $to
     * @param string $price
     */
    public function __construct(DateTime $from, DateTime $to, string $price)
    {
        $this->from = $from;
        $this->to = $to;
        $this->price = $price;
    }

    public function toArray(): array
    {
        return [
            'date_from' => $this->from,
            'date_to' => $this->to,
            'price' => $this->price
        ];
    }
}
