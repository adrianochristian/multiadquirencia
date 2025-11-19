<?php

namespace App\Services\Subacquirers;

use App\Domain\Payments\PixStatus;
use App\Domain\Payments\WithdrawalStatus;
use App\Services\Subacquirers\Contracts\SubacquirerInterface;

class SubadqBService extends BaseSubacquirerService implements SubacquirerInterface
{
    /**
     * Create a PIX transaction
     */
    public function createPix(array $data): array
    {
        $payload = [
            'value' => $data['amount'],
            'description' => $data['description'] ?? 'Pagamento via PIX',
            'payer' => [
                'name' => $data['customer_name'] ?? 'Cliente',
                'document' => $data['customer_document'] ?? '',
            ],
        ];

        $headers = [
            'x-mock-response-name' => '[SUCESSO_PIX] pix_create',
            'Content-Type' => 'application/json',
        ];

        $response = $this->makeRequest('post', '/pix/create', $payload, $headers);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data']['data'] ?? $response['data'];

        return [
            'success' => true,
            'external_id' => $data['id'] ?? null,
            'transaction_id' => $data['id'] ?? null,
            'qr_code' => $data['qr_code'] ?? null,
            'qr_code_url' => $data['qr_code_url'] ?? null,
            'status' => $this->normalizeStatus($data['status'] ?? 'PENDING'),
            'raw_response' => $response['data'],
        ];
    }

    /**
     * Create a withdrawal
     */
    public function createWithdrawal(array $data): array
    {
        $payload = [
            'amount' => $data['amount'],
            'bank_account' => [
                'bank' => $data['bank_code'],
                'agency' => $data['agency'],
                'account' => $data['account'],
                'account_type' => $data['account_type'] ?? 'checking',
                'holder_name' => $data['holder_name'],
                'holder_document' => $data['holder_document'],
            ],
        ];

        $headers = [
            'x-mock-response-name' => '[SUCESSO_WD] withdraw',
            'Content-Type' => 'application/json',
        ];

        $response = $this->makeRequest('post', '/withdraw', $payload, $headers);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data']['data'] ?? $response['data'];

        return [
            'success' => true,
            'external_id' => $data['id'] ?? null,
            'transaction_id' => $data['id'] ?? null,
            'status' => $this->normalizeStatus($data['status'] ?? 'PENDING'),
            'raw_response' => $response['data'],
        ];
    }

    /**
     * Parse webhook payload for PIX
     */
    public function parsePixWebhook(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        return [
            'external_id' => $data['id'] ?? null,
            'transaction_id' => $data['id'] ?? null,
            'status' => $this->normalizeStatus($data['status'] ?? 'PENDING'),
            'amount' => $data['value'] ?? null,
            'payer_name' => $data['payer']['name'] ?? null,
            'payer_document' => $data['payer']['document'] ?? null,
            'paid_at' => $data['confirmed_at'] ?? null,
        ];
    }

    /**
     * Parse webhook payload for withdrawal
     */
    public function parseWithdrawalWebhook(array $payload): array
    {
        $data = $payload['data'] ?? $payload;

        return [
            'external_id' => $data['id'] ?? null,
            'transaction_id' => $data['id'] ?? null,
            'status' => $this->normalizeStatus($data['status'] ?? 'PENDING'),
            'amount' => $data['amount'] ?? null,
            'completed_at' => $data['processed_at'] ?? null,
        ];
    }

    /**
     * Normalize status from SubadqB to internal status
     */
    protected function normalizeStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PENDING' => PixStatus::PENDING->value,
            'PROCESSING' => PixStatus::PROCESSING->value,
            'CONFIRMED' => PixStatus::CONFIRMED->value,
            'PAID' => PixStatus::PAID->value,
            'SUCCESS' => WithdrawalStatus::SUCCESS->value,
            'DONE' => WithdrawalStatus::DONE->value,
            'CANCELLED' => WithdrawalStatus::CANCELLED->value,
            'FAILED' => WithdrawalStatus::FAILED->value,
            default => PixStatus::PENDING->value,
        };
    }
}
