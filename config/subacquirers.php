<?php

return [
    'http_timeout' => env('SUBACQUIRER_HTTP_TIMEOUT', 5),

    'webhook_jobs' => [
        'tries' => env('WEBHOOK_JOB_TRIES', 3),
        'backoff' => [
            60,
            300,
            900,
        ],
    ],

    'drivers' => [
        'subadq_a' => \App\Services\Subacquirers\SubadqAService::class,
        'subadq_b' => \App\Services\Subacquirers\SubadqBService::class,
    ],
];
