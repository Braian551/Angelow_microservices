<?php

use App\Http\Controllers\Admin\AdminDiscountController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/discounts/codes', [DiscountController::class, 'listCodes']);
Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);
Route::post('/discounts/bulk/validate', [DiscountController::class, 'validateBulkDiscount']);

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/discount-codes', [AdminDiscountController::class, 'codes']);
    Route::post('/discount-codes', [AdminDiscountController::class, 'storeCode']);
    Route::put('/discount-codes/{id}', [AdminDiscountController::class, 'updateCode']);
    Route::delete('/discount-codes/{id}', [AdminDiscountController::class, 'destroyCode']);
    Route::get('/discount-codes/campaign/customers', [AdminDiscountController::class, 'campaignCustomers']);
    Route::post('/discount-codes/campaign/mass', [AdminDiscountController::class, 'sendMassCampaign']);
    Route::post('/discount-codes/campaign/specific', [AdminDiscountController::class, 'sendSpecificCampaign']);

    Route::get('/bulk-discounts', [AdminDiscountController::class, 'bulkDiscounts']);
    Route::post('/bulk-discounts', [AdminDiscountController::class, 'storeBulkDiscount']);
    Route::put('/bulk-discounts/{id}', [AdminDiscountController::class, 'updateBulkDiscount']);
    Route::delete('/bulk-discounts/{id}', [AdminDiscountController::class, 'destroyBulkDiscount']);
});
