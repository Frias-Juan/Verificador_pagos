<?php

use App\Http\Controllers\Api\PaymentNotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/api/payment-notifications', [PaymentNotificationController::class, 'store'])->middleware('auth:sanctum');