<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DiscountController;
use App\Http\Controllers\API\AddressController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Auth protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    Route::apiResource('addresses', AddressController::class)->only(['index', 'store']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::get('/cart', [CartController::class, 'index']);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
});

Route::post('/discounts/validate', [DiscountController::class, 'validate']);  // Public for cart preview
