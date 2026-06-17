<?php

/**
 * Rutas de la API del servicio de descuentos.
 * Organizadas por recurso: health, descuentos públicos, descuentos admin.
 * Las rutas admin están protegidas por el middleware EnsureAdmin.
 */

use App\Http\Controllers\Admin\AdminDiscountController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// Health check para el orquestador de contenedores (Docker/k8s).
Route::get('/health', HealthController::class);

// Endpoints públicos de descuentos.
Route::get('/discounts/codes', [DiscountController::class, 'listCodes']);                   // Listar códigos vigentes
Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);             // Validar un cupón
Route::post('/discounts/bulk/validate', [DiscountController::class, 'validateBulkDiscount']); // Validar descuento por cantidad

// ── Admin (protegido con middleware EnsureAdmin para verificar token y rol) ──────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    // CRUD de códigos de descuento.
    Route::get('/discount-codes', [AdminDiscountController::class, 'codes']);
    Route::post('/discount-codes', [AdminDiscountController::class, 'storeCode']);
    Route::put('/discount-codes/{id}', [AdminDiscountController::class, 'updateCode']);
    Route::delete('/discount-codes/{id}', [AdminDiscountController::class, 'destroyCode']);

    // Campañas de descuento: masivas (todos los clientes) o específicas (por IDs).
    Route::get('/discount-codes/campaign/customers', [AdminDiscountController::class, 'campaignCustomers']);
    Route::post('/discount-codes/campaign/mass', [AdminDiscountController::class, 'sendMassCampaign']);
    Route::post('/discount-codes/campaign/specific', [AdminDiscountController::class, 'sendSpecificCampaign']);

    // CRUD de reglas de descuento por cantidad.
    Route::get('/bulk-discounts', [AdminDiscountController::class, 'bulkDiscounts']);
    Route::post('/bulk-discounts', [AdminDiscountController::class, 'storeBulkDiscount']);
    Route::put('/bulk-discounts/{id}', [AdminDiscountController::class, 'updateBulkDiscount']);
    Route::delete('/bulk-discounts/{id}', [AdminDiscountController::class, 'destroyBulkDiscount']);
});
