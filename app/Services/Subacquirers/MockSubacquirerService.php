<?php

namespace App\Services\Subacquirers;

use App\Domain\Payments\PixStatus;
use App\Domain\Payments\WithdrawalStatus;
use App\Services\Subacquirers\Contracts\SubacquirerInterface;
use Illuminate\Support\Str;

class MockSubacquirerService implements SubacquirerInterface
{
    protected string $subacquirerCode;

    public function __construct(string $subacquirerCode)
    {
        $this->subacquirerCode = $subacquirerCode;
    }

    /**
     * Create a PIX transaction - Mock Response
     */
    public function createPix(array $data): array
    {
        \Log::info('Mock Subacquirer - Creating PIX', [
            'subacquirer' => $this->subacquirerCode,
            'data' => $data,
        ]);

        $pixId = 'PIX_' . strtoupper(Str::random(16));
        $transactionId = 'TXN_' . strtoupper(Str::random(12));
        $qrCode = $this->generateMockQRCode();

        $response = [
            'pix_id' => $pixId,
            'transaction_id' => $transactionId,
            'amount' => $data['amount'],
            'status' => 'PENDING',
            'qr_code' => $qrCode,
            'qr_code_url' => "https://mock-qrcode.example.com/{$pixId}",
            'expires_at' => now()->addMinutes(30)->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        \Log::info('Mock Subacquirer - PIX Created', [
            'subacquirer' => $this->subacquirerCode,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'external_id' => $response['pix_id'],
            'transaction_id' => $response['transaction_id'],
            'qr_code' => $response['qr_code'],
            'qr_code_url' => $response['qr_code_url'],
            'status' => $this->normalizeStatus($response['status']),
            'raw_response' => $response,
        ];
    }

    /**
     * Create a withdrawal - Mock Response
     */
    public function createWithdrawal(array $data): array
    {
        \Log::info('Mock Subacquirer - Creating Withdrawal', [
            'subacquirer' => $this->subacquirerCode,
            'data' => $data,
        ]);

        $withdrawId = 'WD_' . strtoupper(Str::random(16));
        $transactionId = 'TXN_' . strtoupper(Str::random(12));

        $response = [
            'withdraw_id' => $withdrawId,
            'transaction_id' => $transactionId,
            'amount' => $data['amount'],
            'status' => 'PENDING',
            'bank_code' => $data['bank_code'],
            'agency' => $data['agency'],
            'account' => $data['account'],
            'estimated_completion' => now()->addHours(2)->toIso8601String(),
            'created_at' => now()->toIso8601String(),
        ];

        \Log::info('Mock Subacquirer - Withdrawal Created', [
            'subacquirer' => $this->subacquirerCode,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'external_id' => $response['withdraw_id'],
            'transaction_id' => $response['transaction_id'],
            'status' => $this->normalizeStatus($response['status']),
            'raw_response' => $response,
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
            'payer_document' => $payload['payer_cpf'] ?? $payload['payer_document'] ?? null,
            'paid_at' => $payload['payment_date'] ?? $payload['paid_at'] ?? null,
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
     * Normalize status to internal status
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

    /**
     * Generate a mock QR Code string
     */
    protected function generateMockQRCode(): string
    {
        $pixKey = 'mockpix@example.com';
        $merchantName = 'MOCK MERCHANT';
        $merchantCity = 'SAO PAULO';
        $txid = strtoupper(Str::random(25));

        return "00020126580014br.gov.bcb.pix0136{$pixKey}52040000530398654040.005802BR5913{$merchantName}6009{$merchantCity}62070503***63041D3D";
    }
}
