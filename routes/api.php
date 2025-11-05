<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DiscountController;
use App\Http\Controllers\API\AddressController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/oauth/callback', [AuthController::class, 'handleOAuthCallback']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories/clear-cache', [CategoryController::class, 'clearCache']); // For development

Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);  // Public for cart preview

// Cart routes - allow guest usage
Route::post('/cart/add', [CartController::class, 'add']);
Route::get('/cart', [CartController::class, 'index']);
// Admin routes (for now without authentication - add auth middleware later)
Route::prefix('admin')->group(function () {
    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class)->except(['show']);
    Route::get('/dashboard/stats', [ProductController::class, 'dashboardStats']);
});
// Auth protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::apiResource('addresses', AddressController::class);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
    Route::patch('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/clear', [CartController::class, 'clear']);
});
