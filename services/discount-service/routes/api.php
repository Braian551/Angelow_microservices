<?php

// Comentario de mantenimiento: Estas rutas conectan contratos HTTP con controladores del servicio.

use App\Http\Controllers\Admin\AdminDiscountController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.

Route::get('/health', HealthController::class);
// Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
Route::get('/discounts/codes', [DiscountController::class, 'listCodes']);
// Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);
// Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
Route::post('/discounts/bulk/validate', [DiscountController::class, 'validateBulkDiscount']);

// Sección: administración.
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/discount-codes', [AdminDiscountController::class, 'codes']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/discount-codes', [AdminDiscountController::class, 'storeCode']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/discount-codes/{id}', [AdminDiscountController::class, 'updateCode']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/discount-codes/{id}', [AdminDiscountController::class, 'destroyCode']);
    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.
    Route::get('/discount-codes/campaign/customers', [AdminDiscountController::class, 'campaignCustomers']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/discount-codes/campaign/mass', [AdminDiscountController::class, 'sendMassCampaign']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/discount-codes/campaign/specific', [AdminDiscountController::class, 'sendSpecificCampaign']);

    // Expone un endpoint GET del servicio y delega la operación al controlador correspondiente.

    Route::get('/bulk-discounts', [AdminDiscountController::class, 'bulkDiscounts']);
    // Expone un endpoint POST del servicio y delega la operación al controlador correspondiente.
    Route::post('/bulk-discounts', [AdminDiscountController::class, 'storeBulkDiscount']);
    // Expone un endpoint PUT del servicio y delega la operación al controlador correspondiente.
    Route::put('/bulk-discounts/{id}', [AdminDiscountController::class, 'updateBulkDiscount']);
    // Expone un endpoint DELETE del servicio y delega la operación al controlador correspondiente.
    Route::delete('/bulk-discounts/{id}', [AdminDiscountController::class, 'destroyBulkDiscount']);
});
