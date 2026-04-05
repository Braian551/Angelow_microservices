<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
});

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'recentOrders']);
    Route::get('/reports/sales', [AdminOrderController::class, 'reportSales']);
});
