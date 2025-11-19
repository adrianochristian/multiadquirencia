<?php

namespace App\Enums;

enum WithdrawalStatus: string
{
    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case DONE = 'DONE';
    case CANCELLED = 'CANCELLED';
    case FAILED = 'FAILED';
}

