<?php

namespace App\Domain\Payments\ValueObjects;

class Money
{
    public function __construct(
        public float $amount
    ) {
    }

    public static function fromFloat(float $amount): self
    {
        return new self(round($amount, 2));
    }

    public function toFloat(): float
    {
        return $this->amount;
    }
}

