<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $userModel = config('auth.providers.users.model');

        /** @var \Illuminate\Foundation\Auth\User|null $user */
        $user = $userModel::where('email', $credentials['email'])->first();

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return $this->apiError(
                'INVALID_CREDENTIALS',
                'The provided credentials are incorrect.',
                null,
                401
            );
        }

        $token = $user->createToken('api')->plainTextToken;

        return $this->apiSuccess([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}

