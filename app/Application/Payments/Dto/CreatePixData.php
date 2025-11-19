<?php

namespace App\Application\Payments\Dto;

use App\Domain\Payments\ValueObjects\Document;
use App\Domain\Payments\ValueObjects\Money;

class CreatePixData
{
    public function __construct(
        public Money $amount,
        public ?string $description,
        public ?string $customerName,
        public ?Document $customerDocument,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Money::fromFloat((float) $data['amount']),
            $data['description'] ?? null,
            $data['customer_name'] ?? null,
            isset($data['customer_document']) ? Document::fromString((string) $data['customer_document']) : null,
        );
    }
}

