<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\PreventDuplicateOrderSubmission;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store'])->middleware(PreventDuplicateOrderSubmission::class);
    Route::post('/{id}/send-confirmation', [OrderController::class, 'sendCheckoutConfirmation']);
    Route::patch('/{id}/cancel', [OrderController::class, 'cancel']);
    Route::get('/{id}', [OrderController::class, 'show']);
    Route::patch('/{id}', [OrderController::class, 'update']);
    Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
    Route::patch('/{id}/deactivate', [OrderController::class, 'deactivate']);
});

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'recentOrders']);
    Route::get('/invoices', [AdminInvoiceController::class, 'index']);
    Route::get('/invoices/{id}/download', [AdminInvoiceController::class, 'download']);
    Route::post('/invoices/{id}/resend', [AdminInvoiceController::class, 'resend']);
    Route::get('/reports/sales', [AdminOrderController::class, 'reportSales']);
    Route::get('/reports/products', [AdminOrderController::class, 'reportProducts']);
    Route::get('/reports/customers', [AdminOrderController::class, 'reportCustomers']);
});
