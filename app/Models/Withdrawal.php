<?php

namespace App\Models;

use App\Domain\Payments\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'subacquirer_id',
        'external_id',
        'withdrawal_id',
        'amount',
        'status',
        'bank_code',
        'bank_name',
        'agency',
        'account',
        'account_type',
        'document',
        'requested_at',
        'completed_at',
        'raw_request',
        'raw_response',
        'webhook_payload',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function statusEnum(): WithdrawalStatus
    {
        return WithdrawalStatus::tryFrom($this->status) ?? WithdrawalStatus::PENDING;
    }

    public function isPending(): bool
    {
        return in_array($this->statusEnum(), [
            WithdrawalStatus::PENDING,
            WithdrawalStatus::PROCESSING,
        ], true);
    }

    public function isCompleted(): bool
    {
        return in_array($this->statusEnum(), [
            WithdrawalStatus::SUCCESS,
            WithdrawalStatus::DONE,
        ], true);
    }
}
