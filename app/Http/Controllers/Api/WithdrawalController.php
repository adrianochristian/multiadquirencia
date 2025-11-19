<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateWithdrawalRequest;
use App\Application\Payments\CreateWithdrawalAction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WithdrawalController extends Controller
{
    /**
     * Create a withdrawal
     */
    public function __construct(
        public CreateWithdrawalAction $createWithdrawal
    ) {
    }

    public function create(CreateWithdrawalRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = $request->user();

            if (!$user instanceof User) {
                return $this->apiError(
                    'UNAUTHENTICATED',
                    'User is not authenticated',
                    null,
                    401
                );
            }

            $user->load('subacquirer');

            if (!$user->subacquirer) {
                return $this->apiError(
                    'SUBACQUIRER_NOT_CONFIGURED',
                    'User does not have a subacquirer configured',
                    null,
                    400
                );
            }

            if (!$user->subacquirer->is_active) {
                return $this->apiError(
                    'SUBACQUIRER_INACTIVE',
                    'Subacquirer is not active',
                    null,
                    400
                );
            }

            $result = $this->createWithdrawal->handle($user, $request->toData());

            if (!$result['success']) {
                return $this->apiError(
                    'SUBACQUIRER_WITHDRAWAL_ERROR',
                    'Failed to create withdrawal',
                    $result['error'] ?? 'Unknown error',
                    502
                );
            }

            $withdrawal = $result['withdrawal'];

            return $this->apiSuccess([
                'withdrawal_id' => $withdrawal->withdrawal_id,
                'external_id' => $withdrawal->external_id,
                'amount' => $withdrawal->amount,
                'status' => $withdrawal->status,
                'requested_at' => $withdrawal->requested_at,
                'created_at' => $withdrawal->created_at,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error creating withdrawal", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->apiError(
                'INTERNAL_SERVER_ERROR',
                'Internal server error',
                $e->getMessage(),
                500
            );
        }
    }
}
