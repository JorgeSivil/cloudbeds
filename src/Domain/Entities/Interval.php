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
            'from' => $this->from,
            'to' => $this->to,
            'price' => $this->price
        ];
    }

    /**
     * @return DateTime
     */
    public function getFrom(): DateTime
    {
        return $this->from;
    }

    /**
     * @param DateTime $from
     */
    public function setFrom(DateTime $from): void
    {
        $this->from = $from;
    }

    /**
     * @return DateTime
     */
    public function getTo(): DateTime
    {
        return $this->to;
    }

    /**
     * @param DateTime $to
     */
    public function setTo(DateTime $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getPrice(): string
    {
        return $this->price;
    }

    /**
     * @param string $price
     */
    public function setPrice(string $price): void
    {
        $this->price = $price;
    }
}
