<?php

use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/banks', [PaymentController::class, 'banks']);
Route::get('/payments', [PaymentController::class, 'index']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::patch('/payments/{id}/verify', [PaymentController::class, 'verify']);

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/payments', [AdminPaymentController::class, 'index']);
    Route::patch('/payments/{id}', [AdminPaymentController::class, 'verify']);
});
