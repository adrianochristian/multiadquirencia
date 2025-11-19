<?php

use App\Http\Controllers\Api\PixController;
use App\Http\Controllers\Api\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/pix', [PixController::class, 'create']);

Route::post('/withdraw', [WithdrawalController::class, 'create']);
