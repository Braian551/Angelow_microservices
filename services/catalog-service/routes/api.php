<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog Service API Routes
|--------------------------------------------------------------------------
|
| Endpoints for product browsing, categories, collections, wishlist,
| and site-wide content (settings, sliders, announcements).
| Base URL: http://localhost:8002/api
|
*/

// ── Home (aggregated data) ──────────────────────────────────────────────
Route::get('/home', [SiteController::class, 'home']);

// ── Site Content ────────────────────────────────────────────────────────
Route::get('/settings', [SiteController::class, 'settings']);
Route::get('/sliders', [SiteController::class, 'sliders']);

// ── Products ────────────────────────────────────────────────────────────
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// ── Categories & Collections ────────────────────────────────────────────
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/collections', [CategoryController::class, 'collections']);

// ── Wishlist / Favorites ────────────────────────────────────────────────
Route::get('/wishlist', [WishlistController::class, 'index']);
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle']);
