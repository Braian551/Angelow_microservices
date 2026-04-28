<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/audits/orders', [AuditController::class, 'orders']);
Route::get('/audits/users', [AuditController::class, 'users']);
Route::get('/audits/products', [AuditController::class, 'products']);
