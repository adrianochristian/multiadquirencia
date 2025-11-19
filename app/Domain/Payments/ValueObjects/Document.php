<?php

namespace App\Domain\Payments\ValueObjects;

class Document
{
    public function __construct(
        public string $value
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self(preg_replace('/\D+/', '', $value) ?? '');
    }

    public function masked(): string
    {
        $digits = $this->value;
        $last = substr($digits, -4);

        return '***' . $last;
    }

    public function raw(): string
    {
        return $this->value;
    }
}

