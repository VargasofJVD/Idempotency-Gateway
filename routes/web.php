<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return response()->json(['message' => 'Idempotency Gateway API is running']);
});

Route::post('/process-payment', [PaymentController::class, 'processPayment'])->middleware('idempotent');

