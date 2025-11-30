<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DiscountController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\PackageDealController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::post('/auth/oauth/callback', [AuthController::class, 'handleOAuthCallback']);

// Password reset routes
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

// Product routes with rate limiting for search (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
});
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories/clear-cache', [CategoryController::class, 'clearCache']); // For development

Route::post('/discounts/validate', [DiscountController::class, 'validateCode']);  // Public for cart preview

// Public review routes
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);

// Package deals routes
Route::get('/packages', [PackageDealController::class, 'index']);
Route::get('/packages/featured', [PackageDealController::class, 'featured']);
Route::get('/packages/{slug}', [PackageDealController::class, 'show']);

// Public order tracking by reference (used by email tracking links)
Route::get('/orders/ref/{reference}', [OrderController::class, 'publicShowByReference']);

// Cart routes - allow guest usage
Route::post('/cart/add', [CartController::class, 'add']);
Route::get('/cart', [CartController::class, 'index']);

// Admin routes (protected by authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class)->except(['show']);
        Route::get('/dashboard/stats', [ProductController::class, 'dashboardStats']);

        // Orders admin routes
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{order}', [OrderController::class, 'adminShow']);
        Route::patch('/orders/{order}', [OrderController::class, 'adminUpdate']);
        Route::delete('/orders/{order}', [OrderController::class, 'adminSoftDelete']);

        // Package deals admin routes
        Route::apiResource('packages', PackageDealController::class)->except(['index', 'show']);

        // Settings admin routes
        Route::apiResource('settings', SettingsController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('/settings/bulk-update', [SettingsController::class, 'bulkUpdate']);
    });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile routes with rate limiting
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/profile/activity', [ProfileController::class, 'activitySummary']);
    Route::middleware('throttle:5,1')->group(function () {
        Route::put('/profile', [ProfileController::class, 'update']);
    });
    Route::middleware('throttle:3,60')->group(function () {
        Route::delete('/profile', [ProfileController::class, 'destroy']);
    });

    Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'store']);
    // Secure endpoint to fetch order by reference number (safer than exposing DB id)
    Route::get('/orders/ref/{reference}', [OrderController::class, 'showByReference']);
    Route::apiResource('addresses', AddressController::class);

    // Cart clear must come before cart/{id} to avoid route conflict
    Route::delete('/cart/clear', [CartController::class, 'clear']);
    Route::delete('/cart/{id}', [CartController::class, 'remove']);
    Route::patch('/cart/{id}', [CartController::class, 'update']);

    // Review routes with rate limiting
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);
        Route::get('/products/{productId}/reviews/can-review', [ReviewController::class, 'canReview']);
    });

    Route::middleware('throttle:20,1')->group(function () {
        Route::put('/reviews/{id}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);
    });
});
