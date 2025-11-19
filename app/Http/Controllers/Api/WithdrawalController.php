<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWithdrawalRequest;
use App\Jobs\ProcessWithdrawalWebhook;
use App\Enums\WithdrawalStatus;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Subacquirers\SubacquirerFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WithdrawalController extends Controller
{
    /**
     * Create a withdrawal
     */
    public function create(CreateWithdrawalRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::with('subacquirer')->findOrFail($validated['user_id']);

            if (!$user->subacquirer) {
                return response()->json([
                    'success' => false,
                    'message' => 'User does not have a subacquirer configured',
                ], 400);
            }

            if (!$user->subacquirer->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subacquirer is not active',
                ], 400);
            }

            return DB::transaction(function () use ($user, $validated) {
                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                    'withdrawal_id' => 'WD-' . Str::uuid(),
                    'amount' => $validated['amount'],
                    'status' => WithdrawalStatus::PENDING->value,
                    'bank_code' => $validated['bank_code'],
                    'agency' => $validated['agency'],
                    'account' => $validated['account'],
                    'account_type' => $validated['account_type'] ?? 'checking',
                    'document' => $validated['holder_document'],
                    'requested_at' => now(),
                    'raw_request' => $validated,
                ]);

                $subacquirerService = SubacquirerFactory::make($user->subacquirer);

                $response = $subacquirerService->createWithdrawal([
                    'amount' => $validated['amount'],
                    'bank_code' => $validated['bank_code'],
                    'agency' => $validated['agency'],
                    'account' => $validated['account'],
                    'account_type' => $validated['account_type'] ?? 'checking',
                    'holder_name' => $validated['holder_name'],
                    'holder_document' => $validated['holder_document'],
                ]);

                if (!$response['success']) {
                    $withdrawal->update([
                        'status' => WithdrawalStatus::FAILED->value,
                        'raw_response' => $response,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create withdrawal',
                        'error' => $response['error'] ?? 'Unknown error',
                    ], 500);
                }

                $withdrawal->update([
                    'external_id' => $response['external_id'],
                    'status' => $response['status'],
                    'raw_response' => $response['raw_response'],
                ]);

                $this->simulateWebhook($withdrawal);

                Log::info("Withdrawal created", [
                    'withdrawal_id' => $withdrawal->withdrawal_id,
                    'user_id' => $user->id,
                    'subacquirer' => $user->subacquirer->code,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'withdrawal_id' => $withdrawal->withdrawal_id,
                        'external_id' => $withdrawal->external_id,
                        'amount' => $withdrawal->amount,
                        'status' => $withdrawal->status,
                        'requested_at' => $withdrawal->requested_at,
                        'created_at' => $withdrawal->created_at,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error creating withdrawal", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Simulate webhook for withdrawal
     */
    private function simulateWebhook(Withdrawal $withdrawal): void
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
