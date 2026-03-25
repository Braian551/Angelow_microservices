<?php

use App\Http\Controllers\DiscountController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/discounts/codes', [DiscountController::class, 'listCodes']);
Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);
