<?php

use App\Http\Controllers\Admin\AdminShippingController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ShippingController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/shipping/methods', [ShippingController::class, 'methods']);
Route::get('/shipping/rules', [ShippingController::class, 'rules']);
Route::post('/shipping/estimate', [ShippingController::class, 'estimate']);
Route::get('/shipping/addresses', [ShippingController::class, 'userAddresses']);
Route::post('/shipping/addresses', [ShippingController::class, 'createUserAddress']);
Route::put('/shipping/addresses/{addressId}', [ShippingController::class, 'updateUserAddress']);
Route::delete('/shipping/addresses/{addressId}', [ShippingController::class, 'deleteUserAddress']);
Route::patch('/shipping/addresses/{addressId}/default', [ShippingController::class, 'setDefaultUserAddress']);

// ── Admin ───────────────────────────────────────────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/shipping-methods', [AdminShippingController::class, 'methods']);
    Route::post('/shipping-methods', [AdminShippingController::class, 'storeMethod']);
    Route::put('/shipping-methods/{id}', [AdminShippingController::class, 'updateMethod']);
    Route::delete('/shipping-methods/{id}', [AdminShippingController::class, 'destroyMethod']);

    Route::get('/shipping-rules', [AdminShippingController::class, 'rules']);
    Route::post('/shipping-rules', [AdminShippingController::class, 'storeRule']);
    Route::put('/shipping-rules/{id}', [AdminShippingController::class, 'updateRule']);
    Route::delete('/shipping-rules/{id}', [AdminShippingController::class, 'destroyRule']);
});
