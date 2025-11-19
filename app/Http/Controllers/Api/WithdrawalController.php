<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWithdrawalWebhook;
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
    public function create(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'amount' => 'required|numeric|min:0.01',
                'bank_code' => 'required|string',
                'agency' => 'required|string',
                'account' => 'required|string',
                'account_type' => 'nullable|string|in:checking,savings',
                'holder_name' => 'required|string',
                'holder_document' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::with('subacquirer')->findOrFail($request->user_id);

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

            return DB::transaction(function () use ($request, $user) {
                $withdrawal = Withdrawal::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                    'withdrawal_id' => 'WD-' . Str::uuid(),
                    'amount' => $request->amount,
                    'status' => 'PENDING',
                    'bank_code' => $request->bank_code,
                    'agency' => $request->agency,
                    'account' => $request->account,
                    'account_type' => $request->account_type ?? 'checking',
                    'document' => $request->holder_document,
                    'requested_at' => now(),
                    'raw_request' => $request->all(),
                ]);

                $subacquirerService = SubacquirerFactory::make($user->subacquirer);

                $response = $subacquirerService->createWithdrawal([
                    'amount' => $request->amount,
                    'bank_code' => $request->bank_code,
                    'agency' => $request->agency,
                    'account' => $request->account,
                    'account_type' => $request->account_type ?? 'checking',
                    'holder_name' => $request->holder_name,
                    'holder_document' => $request->holder_document,
                ]);

                if (!$response['success']) {
                    $withdrawal->update([
                        'status' => 'FAILED',
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
