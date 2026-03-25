<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cart Service API Routes
|--------------------------------------------------------------------------
|
| Endpoints for shopping cart operations.
| Base URL: http://localhost:8003/api
|
*/

// ── Cart ────────────────────────────────────────────────────────────────
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart/add', [CartController::class, 'add']);
Route::put('/cart/{itemId}', [CartController::class, 'update']);
Route::delete('/cart/{itemId}', [CartController::class, 'destroy']);
Route::get('/cart/items', [CartController::class, 'productIds']);

Route::get('/health', HealthController::class);
