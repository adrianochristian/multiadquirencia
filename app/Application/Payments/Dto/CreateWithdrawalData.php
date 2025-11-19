<?php

namespace App\Application\Payments\Dto;

use App\Domain\Payments\ValueObjects\Document;
use App\Domain\Payments\ValueObjects\Money;

class CreateWithdrawalData
{
    public function __construct(
        public Money $amount,
        public string $bankCode,
        public string $agency,
        public string $account,
        public ?string $accountType,
        public string $holderName,
        public Document $holderDocument,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            Money::fromFloat((float) $data['amount']),
            $data['bank_code'],
            $data['agency'],
            $data['account'],
            $data['account_type'] ?? null,
            $data['holder_name'],
            Document::fromString((string) $data['holder_document']),
        );
    }
}

