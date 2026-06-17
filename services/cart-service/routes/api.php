<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas API del cart-service
|--------------------------------------------------------------------------
|
| Define los endpoints REST para la gestión del carrito de compras.
| URL base del servicio: http://localhost:8003/api
|
| Los endpoints públicos (cart/) permiten operaciones CRUD identificando
| al usuario por user_id (autenticado) o session_id (visitante).
| El endpoint admin/ está protegido por token interno (X-Internal-Token).
|
| @see CartController
| @see HealthController
|
*/

// ── Carrito de compras ──────────────────────────────────────────────────
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/{itemId}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{itemId}', [CartController::class, 'destroy'])->name('cart.destroy');
Route::get('/cart/items', [CartController::class, 'productIds'])->name('cart.product-ids');

// ── Administración interna ──────────────────────────────────────────────
Route::post('/admin/cart/abandoned/reminders/dispatch', [CartController::class, 'dispatchAbandonedReminders']);

// ── Salud del servicio ──────────────────────────────────────────────────
Route::get('/health', HealthController::class);
