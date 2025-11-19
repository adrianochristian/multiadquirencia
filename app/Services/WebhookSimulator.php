<?php

namespace App\Services;

use App\Jobs\ProcessPixWebhook;
use App\Jobs\ProcessWithdrawalWebhook;
use App\Models\PixTransaction;
use App\Models\Withdrawal;

class WebhookSimulator
{
    public function simulatePix(PixTransaction $pixTransaction): void
    {
        if ($pixTransaction->subacquirer->code === 'subadq_a') {
            $webhookPayload = [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => $pixTransaction->external_id,
                'pix_id' => $pixTransaction->external_id,
                'status' => 'CONFIRMED',
                'amount' => (float) $pixTransaction->amount,
                'payer_name' => 'João da Silva',
                'payer_cpf' => '***' . substr((string) $pixTransaction->payer_document, -4),
                'payment_date' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'environment' => 'sandbox',
                ],
            ];
        } else {
            $webhookPayload = [
                'type' => 'pix.status_update',
                'data' => [
                    'id' => $pixTransaction->external_id,
                    'status' => 'PAID',
                    'value' => (float) $pixTransaction->amount,
                    'payer' => [
                        'name' => 'Maria Oliveira',
                        'document' => '***' . substr((string) $pixTransaction->payer_document, -4),
                    ],
                    'confirmed_at' => now()->toIso8601String(),
                ],
                'signature' => 'd1c4b6f98eaa',
            ];
        }

        ProcessPixWebhook::dispatch($pixTransaction, $webhookPayload)->delay(now()->addSeconds(2));
    }

    public function simulateWithdrawal(Withdrawal $withdrawal): void
    {
        if ($withdrawal->subacquirer->code === 'subadq_a') {
            $webhookPayload = [
                'event' => 'withdraw_completed',
                'withdraw_id' => $withdrawal->external_id,
                'transaction_id' => $withdrawal->external_id,
                'status' => 'SUCCESS',
                'amount' => (float) $withdrawal->amount,
                'requested_at' => $withdrawal->requested_at->toIso8601String(),
                'completed_at' => now()->toIso8601String(),
                'metadata' => [
                    'source' => 'SubadqA',
                    'destination_bank' => $withdrawal->bank_code,
                ],
            ];
        } else {
            $webhookPayload = [
                'type' => 'withdraw.status_update',
                'data' => [
                    'id' => $withdrawal->external_id,
                    'status' => 'DONE',
                    'amount' => (float) $withdrawal->amount,
                    'bank_account' => [
                        'bank' => $withdrawal->bank_code,
                        'agency' => $withdrawal->agency,
                        'account' => $withdrawal->account,
                    ],
                    'processed_at' => now()->toIso8601String(),
                ],
                'signature' => 'aabbccddeeff112233',
            ];
        }

        ProcessWithdrawalWebhook::dispatch($withdrawal, $webhookPayload)->delay(now()->addSeconds(3));
    }
}

