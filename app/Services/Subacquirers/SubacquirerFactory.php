<?php

namespace App\Services\Subacquirers;

use App\Models\Subacquirer;
use App\Services\Subacquirers\Contracts\SubacquirerInterface;

class SubacquirerFactory
{
    /**
     * Create a subacquirer service instance
     *
     * @param Subacquirer $subacquirer
     * @return SubacquirerInterface
     * @throws \Exception
     */
    public static function make(Subacquirer $subacquirer): SubacquirerInterface
    {
        $mode = env('SUBACQUIRER_MODE', 'mock');

        if ($mode === 'mock' || $subacquirer->base_url === 'mock' || empty($subacquirer->base_url)) {
            return new MockSubacquirerService($subacquirer->code);
        }

        return match ($subacquirer->code) {
            'subadq_a' => new SubadqAService($subacquirer),
            'subadq_b' => new SubadqBService($subacquirer),
            default => throw new \Exception("Subacquirer {$subacquirer->code} not supported"),
        };
    }
}
