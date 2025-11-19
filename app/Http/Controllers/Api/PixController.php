<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePixRequest;
use App\Application\Payments\CreatePixAction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PixController extends Controller
{
    /**
     * Create a PIX transaction
     */
    public function __construct(
        public CreatePixAction $createPix
    ) {
    }

    public function create(CreatePixRequest $request): JsonResponse
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

            $result = $this->createPix->handle($user, $request->toData());

            if (!$result['success']) {
                return $this->apiError(
                    'SUBACQUIRER_PIX_ERROR',
                    'Failed to create PIX transaction',
                    $result['error'] ?? 'Unknown error',
                    502
                );
            }

            $pixTransaction = $result['transaction'];

            return $this->apiSuccess([
                'transaction_id' => $pixTransaction->transaction_id,
                'external_id' => $pixTransaction->external_id,
                'amount' => $pixTransaction->amount,
                'status' => $pixTransaction->status,
                'qr_code' => $pixTransaction->qr_code,
                'qr_code_url' => $pixTransaction->qr_code_url,
                'created_at' => $pixTransaction->created_at,
            ], 201);
        } catch (\Exception $e) {
            Log::error("Error creating PIX transaction", [
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
