<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_DONE = 'DONE';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FAILED = 'FAILED';

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

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
        ], true);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUCCESS,
            self::STATUS_DONE,
        ], true);
    }
}
