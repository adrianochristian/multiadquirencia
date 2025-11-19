<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePixRequest;
use App\Jobs\ProcessPixWebhook;
use App\Models\PixTransaction;
use App\Models\User;
use App\Services\Subacquirers\SubacquirerFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PixController extends Controller
{
    /**
     * Create a PIX transaction
     */
    public function create(CreatePixRequest $request): JsonResponse
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

            return DB::transaction(function () use ($request, $user, $validated) {
                $pixTransaction = PixTransaction::create([
                    'user_id' => $user->id,
                    'subacquirer_id' => $user->subacquirer_id,
                    'transaction_id' => 'PIX-' . Str::uuid(),
                    'amount' => $validated['amount'],
                    'status' => PixTransaction::STATUS_PENDING,
                    'raw_request' => $validated,
                ]);

                $subacquirerService = SubacquirerFactory::make($user->subacquirer);

                $response = $subacquirerService->createPix([
                    'amount' => $validated['amount'],
                    'description' => $validated['description'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_document' => $validated['customer_document'] ?? null,
                ]);

                if (!$response['success']) {
                    $pixTransaction->update([
                        'status' => PixTransaction::STATUS_FAILED,
                        'raw_response' => $response,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to create PIX transaction',
                        'error' => $response['error'] ?? 'Unknown error',
                    ], 500);
                }

                $pixTransaction->update([
                    'external_id' => $response['external_id'],
                    'status' => $response['status'],
                    'qr_code' => $response['qr_code'],
                    'qr_code_url' => $response['qr_code_url'],
                    'raw_response' => $response['raw_response'],
                ]);

                $this->simulateWebhook($pixTransaction);

                Log::info("PIX transaction created", [
                    'transaction_id' => $pixTransaction->transaction_id,
                    'user_id' => $user->id,
                    'subacquirer' => $user->subacquirer->code,
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'transaction_id' => $pixTransaction->transaction_id,
                        'external_id' => $pixTransaction->external_id,
                        'amount' => $pixTransaction->amount,
                        'status' => $pixTransaction->status,
                        'qr_code' => $pixTransaction->qr_code,
                        'qr_code_url' => $pixTransaction->qr_code_url,
                        'created_at' => $pixTransaction->created_at,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            Log::error("Error creating PIX transaction", [
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
     * Simulate webhook for PIX transaction
     */
    private function simulateWebhook(PixTransaction $pixTransaction): void
    {
        if ($pixTransaction->subacquirer->code === 'subadq_a') {
            $webhookPayload = [
                'event' => 'pix_payment_confirmed',
                'transaction_id' => $pixTransaction->external_id,
                'pix_id' => $pixTransaction->external_id,
                'status' => 'CONFIRMED',
                'amount' => (float) $pixTransaction->amount,
                'payer_name' => 'João da Silva',
                'payer_cpf' => '12345678900',
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
                        'document' => '98765432100',
                    ],
                    'confirmed_at' => now()->toIso8601String(),
                ],
                'signature' => 'd1c4b6f98eaa',
            ];
        }

        ProcessPixWebhook::dispatch($pixTransaction, $webhookPayload)->delay(now()->addSeconds(2));
    }
}
