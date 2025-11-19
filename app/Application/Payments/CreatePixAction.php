<?php

namespace App\Application\Payments;

use App\Application\Payments\Dto\CreatePixData;
use App\Domain\Payments\PixStatus;
use App\Models\PixTransaction;
use App\Models\User;
use App\Services\Subacquirers\SubacquirerFactory;
use App\Services\WebhookSimulator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreatePixAction
{
    public function __construct(
        public WebhookSimulator $webhookSimulator
    ) {
    }

    /**
     * @return array{success: bool, transaction: PixTransaction, error?: string}
     */
    public function handle(User $user, CreatePixData $data): array
    {
        $pixTransaction = PixTransaction::create([
            'user_id' => $user->id,
            'subacquirer_id' => $user->subacquirer_id,
            'transaction_id' => 'PIX-' . Str::uuid(),
            'amount' => $data->amount->toFloat(),
            'status' => PixStatus::PENDING->value,
            'raw_request' => [
                'amount' => $data->amount->toFloat(),
                'description' => $data->description,
                'customer_name' => $data->customerName,
                'customer_document' => $data->customerDocument?->raw(),
            ],
        ]);

        $subacquirerService = SubacquirerFactory::make($user->subacquirer);

        $response = $subacquirerService->createPix([
            'amount' => $data->amount->toFloat(),
            'description' => $data->description,
            'customer_name' => $data->customerName,
            'customer_document' => $data->customerDocument?->raw(),
        ]);

        if (!$response['success']) {
            $pixTransaction->update([
                'status' => PixStatus::FAILED->value,
                'raw_response' => $response,
            ]);

            Log::warning('Failed to create PIX transaction on subacquirer', [
                'user_id' => $user->id,
                'subacquirer' => $user->subacquirer->code,
                'error' => $response['error'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Unknown error',
                'transaction' => $pixTransaction,
            ];
        }

        $pixTransaction->update([
            'external_id' => $response['external_id'],
            'status' => $response['status'],
            'qr_code' => $response['qr_code'],
            'qr_code_url' => $response['qr_code_url'],
            'raw_response' => $response['raw_response'],
        ]);

        $this->webhookSimulator->simulatePix($pixTransaction);

        Log::info('PIX transaction created', [
            'transaction_id' => $pixTransaction->transaction_id,
            'user_id' => $user->id,
            'subacquirer' => $user->subacquirer->code,
        ]);

        return [
            'success' => true,
            'transaction' => $pixTransaction,
        ];
    }
}

