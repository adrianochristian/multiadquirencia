<?php

namespace App\Models;

use App\Domain\Payments\PixStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PixTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'subacquirer_id',
        'external_id',
        'transaction_id',
        'amount',
        'status',
        'payer_name',
        'payer_document',
        'qr_code',
        'qr_code_url',
        'paid_at',
        'raw_request',
        'raw_response',
        'webhook_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'raw_request' => 'array',
        'raw_response' => 'array',
        'webhook_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subacquirer(): BelongsTo
    {
        return $this->belongsTo(Subacquirer::class);
    }

    public function statusEnum(): PixStatus
    {
        return PixStatus::tryFrom($this->status) ?? PixStatus::PENDING;
    }

    public function isPending(): bool
    {
        return in_array($this->statusEnum(), [
            PixStatus::PENDING,
            PixStatus::PROCESSING,
        ], true);
    }

    public function isPaid(): bool
    {
        return in_array($this->statusEnum(), [
            PixStatus::CONFIRMED,
            PixStatus::PAID,
        ], true);
    }
}
