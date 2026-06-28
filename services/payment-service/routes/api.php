<?php

/**
 * Rutas de la API del servicio de pagos.
 * Organizadas por recurso: health, bancos, cuenta de pago, pagos públicos y admin.
 * Las rutas admin están protegidas por el middleware EnsureAdmin.
 */

use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

// Health check para el orquestador de contenedores (Docker/k8s).
Route::get('/health', HealthController::class);

// Endpoints públicos de pagos.
Route::get('/banks', [PaymentController::class, 'banks']);                    // Listar bancos colombianos
Route::get('/payment-account', [PaymentController::class, 'paymentAccount']);  // Cuenta de pago activa
Route::get('/payments', [PaymentController::class, 'index']);                 // Listar pagos
Route::post('/payments', [PaymentController::class, 'store']);                // Crear transacción
Route::patch('/payments/{id}/verify', [PaymentController::class, 'verify']);  // Verificar transacción

// ── Admin (protegido con middleware EnsureAdmin para verificar token y rol) ──────────────
Route::prefix('admin')->middleware(EnsureAdmin::class)->group(function () {
    Route::get('/payments', [AdminPaymentController::class, 'index']);               // Listar pagos con filtros
    Route::patch('/payments/{id}', [AdminPaymentController::class, 'verify']);       // Verificar pago manual
    Route::get('/payment-account', [AdminPaymentController::class, 'accountSettings']); // Config de cuenta y bancos
    Route::put('/payment-account', [AdminPaymentController::class, 'saveAccountSettings']); // Guardar cuenta
});
