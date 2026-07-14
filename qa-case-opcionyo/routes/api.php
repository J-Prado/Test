<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SchedulingController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes  (prefixed with /api)
|--------------------------------------------------------------------------
| Flow A — auth        register / login / me
| Flow B — payments    subscribe / stripe webhook
| Flow C — scheduling  specialists / slots / appointments
| Video  — chime        meetings
*/

// Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::get('/specialists', [SchedulingController::class, 'specialists']);
Route::get('/slots', [SchedulingController::class, 'slots']);
Route::get('/subscriptions/{user}', [PaymentController::class, 'show']);

// Protected (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/payments/subscribe', [PaymentController::class, 'subscribe']);

    Route::get('/appointments', [SchedulingController::class, 'myAppointments']);
    Route::post('/appointments', [SchedulingController::class, 'book']);
    Route::delete('/appointments/{appointment}', [SchedulingController::class, 'cancel']);

    Route::post('/video/meetings', [VideoController::class, 'create']);
});
