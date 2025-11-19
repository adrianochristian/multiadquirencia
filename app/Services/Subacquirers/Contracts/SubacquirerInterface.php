<?php

namespace App\Services\Subacquirers\Contracts;

interface SubacquirerInterface
{
    /**
     * Create a PIX transaction
     *
     * @param array $data
     * @return array
     */
    public function createPix(array $data): array;

    /**
     * Create a withdrawal
     *
     * @param array $data
     * @return array
     */
    public function createWithdrawal(array $data): array;

    /**
     * Parse webhook payload for PIX
     *
     * @param array $payload
     * @return array
     */
    public function parsePixWebhook(array $payload): array;

    /**
     * Parse webhook payload for withdrawal
     *
     * @param array $payload
     * @return array
     */
    public function parseWithdrawalWebhook(array $payload): array;
}
