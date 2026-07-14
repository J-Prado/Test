<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// --- Flow A: Auth --------------------------------------------------------
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1'); // basic brute-force protection

// --- Flow B: Stripe webhook (no auth; verified by signature) -------------
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// --- Authenticated (Sanctum token) --------------------------------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);          // protected resource
    Route::post('/logout', [AuthController::class, 'logout']);

    // Flow B: pay
    Route::post('/pay', [PaymentController::class, 'pay']);

    // Flow C: scheduling
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);
});
