<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordRecoveryController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\RegistrationVerificationController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Internal\UserProfileController;
use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de API del servicio de autenticación (auth-service)
|--------------------------------------------------------------------------
|
| El prefijo /api se agrega automáticamente desde bootstrap/app.php.
|
| Grupos de rutas:
|   /api/auth/*          — Endpoints públicos y protegidos de autenticación
|   /api/auth/password-recovery/* — Flujo de recuperación de contraseña
|   /api/admin/*          — Endpoints administrativos (requieren auth + admin)
|   /api/internal/*       — Endpoints de servicio a servicio (token interno)
|   /api/health           — Health check del servicio
|
*/

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    // Registro e inicio de sesión (públicos)
    Route::post('/register', RegisterController::class);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/google', [LoginController::class, 'google']);

    // Recuperación de contraseña (público, 4 pasos)
    Route::prefix('password-recovery')->group(function () {
        Route::post('/request-code', [PasswordRecoveryController::class, 'requestCode']);
        Route::post('/resend-code', [PasswordRecoveryController::class, 'resendCode']);
        Route::post('/verify-code', [PasswordRecoveryController::class, 'verifyCode']);
        Route::post('/reset-password', [PasswordRecoveryController::class, 'resetPassword']);
    });

    Route::prefix('registration-verification')->group(function () {
        Route::post('/request-code', [RegistrationVerificationController::class, 'requestCode']);
        Route::post('/resend-code', [RegistrationVerificationController::class, 'resendCode']);
        Route::post('/verify-code', [RegistrationVerificationController::class, 'verifyCode']);
    });

    Route::prefix('registration-verification')->group(function () {
        Route::post('/request-code', [RegistrationVerificationController::class, 'requestCode']);
        Route::post('/resend-code', [RegistrationVerificationController::class, 'resendCode']);
        Route::post('/verify-code', [RegistrationVerificationController::class, 'verifyCode']);
    });

    Route::prefix('registration-verification')->group(function () {
        Route::post('/request-code', [RegistrationVerificationController::class, 'requestCode']);
        Route::post('/resend-code', [RegistrationVerificationController::class, 'resendCode']);
        Route::post('/verify-code', [RegistrationVerificationController::class, 'verifyCode']);
    });

    // Rutas protegidas (requieren token Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/me', [LoginController::class, 'me']);
        Route::post('/profile', [ProfileController::class, 'updateProfile']);
        Route::post('/password', [ProfileController::class, 'updatePassword']);
    });
});

// Rutas administrativas (requieren autenticación + rol admin)
Route::prefix('admin')->middleware(['auth:sanctum', EnsureAdmin::class])->group(function () {
    Route::get('/customers', [AdminUserController::class, 'customers']);
    Route::patch('/customers/{id}/block', [AdminUserController::class, 'toggleBlock']);
    Route::get('/administrators', [AdminUserController::class, 'administrators']);
    Route::post('/administrators', [AdminUserController::class, 'storeAdmin']);
    Route::put('/administrators/{id}', [AdminUserController::class, 'updateAdmin']);
    Route::delete('/administrators/{id}', [AdminUserController::class, 'destroyAdmin']);
    Route::get('/reports/customers', [AdminUserController::class, 'reportCustomers']);
});

// Rutas internas (comunicación entre microservicios)
Route::prefix('internal')->group(function () {
    Route::get('/users/profiles', [UserProfileController::class, 'index']);
});

// Health check para Docker y monitoreo
Route::get('/health', HealthController::class);
