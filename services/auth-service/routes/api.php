<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordRecoveryController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Internal\UserProfileController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/google', [LoginController::class, 'google']);

    Route::prefix('password-recovery')->group(function () {
        Route::post('/request-code', [PasswordRecoveryController::class, 'requestCode']);
        Route::post('/resend-code', [PasswordRecoveryController::class, 'resendCode']);
        Route::post('/verify-code', [PasswordRecoveryController::class, 'verifyCode']);
        Route::post('/reset-password', [PasswordRecoveryController::class, 'resetPassword']);
    });

    // Protected routes (require Sanctum token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/me', [LoginController::class, 'me']);
        Route::post('/profile', [ProfileController::class, 'updateProfile']);
        Route::post('/password', [ProfileController::class, 'updatePassword']);
    });
});

// Admin routes
Route::prefix('admin')->middleware(['auth:sanctum', EnsureAdmin::class])->group(function () {
    Route::get('/customers', [AdminUserController::class, 'customers']);
    Route::patch('/customers/{id}/block', [AdminUserController::class, 'toggleBlock']);
    Route::get('/administrators', [AdminUserController::class, 'administrators']);
    Route::post('/administrators', [AdminUserController::class, 'storeAdmin']);
    Route::put('/administrators/{id}', [AdminUserController::class, 'updateAdmin']);
    Route::delete('/administrators/{id}', [AdminUserController::class, 'destroyAdmin']);
    Route::get('/reports/customers', [AdminUserController::class, 'reportCustomers']);
});

Route::prefix('internal')->group(function () {
    Route::get('/users/profiles', [UserProfileController::class, 'index']);
});

Route::get('/health', HealthController::class);
