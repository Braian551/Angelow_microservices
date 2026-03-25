<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/banks', [PaymentController::class, 'banks']);
Route::get('/payments', [PaymentController::class, 'index']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::patch('/payments/{id}/verify', [PaymentController::class, 'verify']);
