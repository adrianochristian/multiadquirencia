<?php

namespace App\Domain\Payments;

enum PixStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case CONFIRMED = 'CONFIRMED';
    case PAID = 'PAID';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';
}

