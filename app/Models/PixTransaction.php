<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PixTransaction extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';
    public const STATUS_FAILED = 'FAILED';

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

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING,
        ], true);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [
            self::STATUS_CONFIRMED,
            self::STATUS_PAID,
        ], true);
    }
}
