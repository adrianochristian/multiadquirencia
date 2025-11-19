<?php

namespace App\Application\Payments;

use App\Application\Payments\Dto\CreateWithdrawalData;
use App\Domain\Payments\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Subacquirers\SubacquirerFactory;
use App\Services\WebhookSimulator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateWithdrawalAction
{
    public function __construct(
        public WebhookSimulator $webhookSimulator
    ) {
    }

    /**
     * @return array{success: bool, withdrawal: Withdrawal, error?: string}
     */
    public function handle(User $user, CreateWithdrawalData $data): array
    {
        $withdrawal = Withdrawal::create([
            'user_id' => $user->id,
            'subacquirer_id' => $user->subacquirer_id,
            'withdrawal_id' => 'WD-' . Str::uuid(),
            'amount' => $data->amount->toFloat(),
            'status' => WithdrawalStatus::PENDING->value,
            'bank_code' => $data->bankCode,
            'agency' => $data->agency,
            'account' => $data->account,
            'account_type' => $data->accountType ?? 'checking',
            'document' => $data->holderDocument->raw(),
            'requested_at' => now(),
            'raw_request' => [
                'amount' => $data->amount->toFloat(),
                'bank_code' => $data->bankCode,
                'agency' => $data->agency,
                'account' => $data->account,
                'account_type' => $data->accountType,
                'holder_name' => $data->holderName,
                'holder_document' => $data->holderDocument->raw(),
            ],
        ]);

        $subacquirerService = SubacquirerFactory::make($user->subacquirer);

        $response = $subacquirerService->createWithdrawal([
            'amount' => $data->amount->toFloat(),
            'bank_code' => $data->bankCode,
            'agency' => $data->agency,
            'account' => $data->account,
            'account_type' => $data->accountType ?? 'checking',
            'holder_name' => $data->holderName,
            'holder_document' => $data->holderDocument->raw(),
        ]);

        if (!$response['success']) {
            $withdrawal->update([
                'status' => WithdrawalStatus::FAILED->value,
                'raw_response' => $response,
            ]);

            Log::warning('Failed to create Withdrawal on subacquirer', [
                'user_id' => $user->id,
                'subacquirer' => $user->subacquirer->code,
                'error' => $response['error'] ?? null,
            ]);

            return [
                'success' => false,
                'error' => $response['error'] ?? 'Unknown error',
                'withdrawal' => $withdrawal,
            ];
        }

        $withdrawal->update([
            'external_id' => $response['external_id'],
            'status' => $response['status'],
            'raw_response' => $response['raw_response'],
        ]);

        $this->webhookSimulator->simulateWithdrawal($withdrawal);

        Log::info('Withdrawal created', [
            'withdrawal_id' => $withdrawal->withdrawal_id,
            'user_id' => $user->id,
            'subacquirer' => $user->subacquirer->code,
        ]);

        return [
            'success' => true,
            'withdrawal' => $withdrawal,
        ];
    }
}

