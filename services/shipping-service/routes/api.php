<?php

use App\Http\Controllers\Admin\AdminShippingController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ShippingController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API de shipping-service
|--------------------------------------------------------------------------
|
| Endpoints públicos y administrativos del microservicio de envíos.
| Los endpoints públicos son consumidos por el frontend SPA (checkout,
| dashboard de usuario) y los endpoints admin por el panel de administración.
|
| La mayoría de endpoints públicos aceptan user_id o user_email para resolver
| la identidad del usuario durante la migración desde Angelow legacy.
|
*/

// ── Salud ────────────────────────────────────────────────
/** Endpoint de health check para Docker y balanceadores */
Route::get('/health', HealthController::class);

// ── Envíos (público) ─────────────────────────────────────
/** Obtiene métodos de envío activos con costos resueltos según subtotal */
Route::get('/shipping/methods', [ShippingController::class, 'methods']);

/** Obtiene reglas de precio por rango activas */
Route::get('/shipping/rules', [ShippingController::class, 'rules']);

/** Calcula costo estimado de envío para un subtotal */
Route::post('/shipping/estimate', [ShippingController::class, 'estimate']);

// ── Direcciones (público) ────────────────────────────────
/** Obtiene las direcciones activas del usuario */
Route::get('/shipping/addresses', [ShippingController::class, 'userAddresses']);

/** Crea una nueva dirección de envío */
Route::post('/shipping/addresses', [ShippingController::class, 'createUserAddress']);

/** Actualiza una dirección existente */
Route::put('/shipping/addresses/{addressId}', [ShippingController::class, 'updateUserAddress']);

/** Elimina una dirección del usuario */
Route::delete('/shipping/addresses/{addressId}', [ShippingController::class, 'deleteUserAddress']);

/** Establece una dirección como principal */
Route::patch('/shipping/addresses/{addressId}/default', [ShippingController::class, 'setDefaultUserAddress']);

// ── Admin (protegido con middleware EnsureAdmin) ─────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    // Gestión de métodos de envío
    Route::get('/shipping-methods', [AdminShippingController::class, 'methods']);
    Route::post('/shipping-methods', [AdminShippingController::class, 'storeMethod']);
    Route::put('/shipping-methods/{id}', [AdminShippingController::class, 'updateMethod']);
    Route::delete('/shipping-methods/{id}', [AdminShippingController::class, 'destroyMethod']);

    // Gestión de reglas de precio
    Route::get('/shipping-rules', [AdminShippingController::class, 'rules']);
    Route::post('/shipping-rules', [AdminShippingController::class, 'storeRule']);
    Route::put('/shipping-rules/{id}', [AdminShippingController::class, 'updateRule']);
    Route::delete('/shipping-rules/{id}', [AdminShippingController::class, 'destroyRule']);
});
