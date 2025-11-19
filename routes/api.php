<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:60,1');

Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/pix', [PixController::class, 'create']);

    Route::post('/withdraw', [WithdrawalController::class, 'create']);
});
