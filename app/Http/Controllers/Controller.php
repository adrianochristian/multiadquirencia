<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    protected function apiSuccess(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    protected function apiError(
        string $errorCode,
        string $errorMessage,
        mixed $details,
        int $status
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $errorMessage,
                'details' => $details,
            ],
        ], $status);
    }
}
