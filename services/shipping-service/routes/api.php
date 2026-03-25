<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ShippingController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::get('/shipping/methods', [ShippingController::class, 'methods']);
Route::get('/shipping/rules', [ShippingController::class, 'rules']);
Route::post('/shipping/estimate', [ShippingController::class, 'estimate']);
