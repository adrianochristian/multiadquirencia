<?php

namespace App\Services\Subacquirers;

use App\Services\Subacquirers\Contracts\SubacquirerInterface;

class SubadqAService extends BaseSubacquirerService implements SubacquirerInterface
{
    /**
     * Create a PIX transaction
     */
    public function createPix(array $data): array
    {
        $payload = [
            'amount' => $data['amount'],
            'description' => $data['description'] ?? 'Pagamento via PIX',
            'customer' => [
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

        return [
            'success' => true,
            'external_id' => $response['data']['pix_id'] ?? null,
            'transaction_id' => $response['data']['transaction_id'] ?? null,
            'qr_code' => $response['data']['qr_code'] ?? null,
            'qr_code_url' => $response['data']['qr_code_url'] ?? null,
            'status' => $this->normalizeStatus($response['data']['status'] ?? 'PENDING'),
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
                'bank_code' => $data['bank_code'],
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

        return [
            'success' => true,
            'external_id' => $response['data']['withdraw_id'] ?? null,
            'transaction_id' => $response['data']['transaction_id'] ?? null,
            'status' => $this->normalizeStatus($response['data']['status'] ?? 'PENDING'),
            'raw_response' => $response['data'],
        ];
    }

    /**
     * Parse webhook payload for PIX
     */
    public function parsePixWebhook(array $payload): array
    {
        return [
            'external_id' => $payload['pix_id'] ?? null,
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status' => $this->normalizeStatus($payload['status'] ?? 'PENDING'),
            'amount' => $payload['amount'] ?? null,
            'payer_name' => $payload['payer_name'] ?? null,
            'payer_document' => $payload['payer_cpf'] ?? null,
            'paid_at' => $payload['payment_date'] ?? null,
        ];
    }

    /**
     * Parse webhook payload for withdrawal
     */
    public function parseWithdrawalWebhook(array $payload): array
    {
        return [
            'external_id' => $payload['withdraw_id'] ?? null,
            'transaction_id' => $payload['transaction_id'] ?? null,
            'status' => $this->normalizeStatus($payload['status'] ?? 'PENDING'),
            'amount' => $payload['amount'] ?? null,
            'completed_at' => $payload['completed_at'] ?? null,
        ];
    }

    /**
     * Normalize status from SubadqA to internal status
     */
    protected function normalizeStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'PENDING' => 'PENDING',
            'PROCESSING' => 'PROCESSING',
            'CONFIRMED' => 'CONFIRMED',
            'PAID' => 'PAID',
            'SUCCESS' => 'SUCCESS',
            'DONE' => 'DONE',
            'CANCELLED' => 'CANCELLED',
            'FAILED' => 'FAILED',
            default => 'PENDING',
        };
    }
}
