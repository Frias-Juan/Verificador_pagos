<?php

use App\Http\Controllers\Api\BdvPaymentController;
use App\Http\Controllers\Api\PaymentNotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SMSNotificationController;
use Symfony\Component\HttpFoundation\Request;

Route::post('/payments/notifications/sms', [PaymentNotificationController::class, 'receiveSms']);



/*Route::post('/tokens/create', function (Illuminate\Http\Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required|string',
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !\Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas'
        ], 401);
    }


    if (!$user->tenant_id) {
        return response()->json([
            'success' => false,
            'message' => 'Usuario no autorizado para API'
        ], 403);
    }

    $token = $user->createToken($request->device_name);

    return response()->json([
        'success' => true,
        'token' => $token->plainTextToken,
        'user' => $user->only(['id', 'name', 'email', 'tenant_id'])
    ]);
});


Route::get('/test', function () {
    return response()->json([
        'status' => 'API funcionando',
        'timestamp' => now()->toDateTimeString(),
        'framework' => 'Laravel 11'
    ]);
});*/