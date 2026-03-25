<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth API Routes
|--------------------------------------------------------------------------
|
| Public routes for authentication (no token required).
|
*/

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);

    // Protected routes (require Sanctum token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/me', [LoginController::class, 'me']);
    });
});

Route::get('/health', HealthController::class);
