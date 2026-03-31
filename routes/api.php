<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProductGalleryController;
use App\Http\Controllers\API\MediaLibraryController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\DiscountController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\ReviewController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\PackageDealController;
use App\Http\Controllers\API\SettingsController;
use App\Http\Controllers\API\LikedProductController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\API\MailController;
use App\Http\Controllers\API\CityController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\UserRoleController;
use App\Http\Controllers\API\UserController;

// Create test user route (for development only)
Route::post('/create-test-user', function () {
    try {
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'jfroosama10@gmail.com'],
            [
                'name' => 'Test User',
                'email' => 'jfroosama10@gmail.com',
                'password' => bcrypt('password123'),
                'phone' => '+1234567890',
                'email_verified_at' => now(),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Test user created/updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to create test user: ' . $e->getMessage()
        ], 500);
    }
});

// Test password reset route
Route::get('/test-password-reset/{email}', function ($email) {
    try {
        $user = \App\Models\User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found. Please register first.'
            ], 404);
        }

        // Generate a test token
        $token = \Illuminate\Support\Str::random(60);
        $resetUrl = config('app.frontend_url', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

        $userData = [
            'name' => $user->name,
            'email' => $user->email
        ];

        Mail::to($email)->send(new \App\Mail\PasswordResetMail($userData, $resetUrl, $token));

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset email sent successfully to ' . $email,
            'reset_url' => $resetUrl,
            'token' => substr($token, 0, 10) . '...',
            'user' => $user->name
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send password reset email: ' . $e->getMessage()
        ], 500);
    }
});

// Test email route
Route::get('/test-email/{email}', function ($email) {
    try {
        Mail::to($email)->send(new \App\Mail\TestMail(['message' => 'Mailgun integration test successful!']));
        return response()->json([
            'status' => 'success',
            'message' => 'Test email sent successfully to ' . $email,
            'mailer' => config('mail.default'),
            'from' => config('mail.from.address')
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to send test email: ' . $e->getMessage(),
            'mailer' => config('mail.default')
        ], 500);
    }
});

// Mail API routes
Route::prefix('mail')->group(function () {
    Route::post('/test', [MailController::class, 'sendTestEmail']);
    Route::get('/config', [MailController::class, 'getMailConfig']);
    Route::post('/welcome', [MailController::class, 'sendWelcomeEmail']);
    Route::post('/password-reset', [MailController::class, 'sendPasswordResetEmail']);
    Route::post('/notification', [MailController::class, 'sendNotification']);
    Route::get('/health', [MailController::class, 'getSystemHealth']);

    // Language testing routes
    Route::post('/test-languages', function(Request $request) {
        $results = [];
        $email = $request->input('email', 'test@example.com');
        $name = $request->input('name', 'Test User');

        foreach (['en', 'ar'] as $language) {
            $originalLocale = app()->getLocale();
            app()->setLocale($language);

            $results[$language] = [
                'language' => $language,
                'direction' => $language === 'ar' ? 'rtl' : 'ltr',
                'app_name' => __('emails.app_name'),
                'welcome_subject' => __('emails.welcome.subject', ['app_name' => __('emails.app_name')]),
                'greeting' => __('emails.welcome.greeting', ['name' => $name]),
                'test_sent' => false
            ];

            app()->setLocale($originalLocale);
        }

        return response()->json([
            'status' => 'success',
            'languages_tested' => $results,
            'note' => 'Use POST /mail/test, /mail/welcome, or /mail/password-reset with "language" parameter to send actual emails'
        ]);
    });
});

// Public routes with rate limiting for authentication
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/admin/login', [AuthController::class, 'adminLogin']);
    Route::post('/auth/oauth/callback', [AuthController::class, 'handleOAuthCallback']);
});

Route::match(['get', 'post'], '/checkToken/{token}', [AuthController::class, 'checkTokenByParam']);

// Password reset routes
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

// Product routes with rate limiting for search (60 requests per minute)
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/latest', [ProductController::class, 'latest']);
    // Google OAuth
    Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('/auth/google/callback', [AuthController::class, 'googleCallback']);
    // Facebook OAuth
    Route::get('/auth/facebook/redirect', [AuthController::class, 'facebookRedirect']);
    Route::get('/auth/facebook/callback', [AuthController::class, 'facebookCallback']);
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

// Public cities routes for shipping calculation
Route::get('/cities', [CityController::class, 'publicIndex']);

// Page content routes
Route::get('/page-content/{pageKey}', [App\Http\Controllers\API\PageContentController::class, 'show']);
Route::get('/packages/featured', [PackageDealController::class, 'featured']);
Route::get('/packages/{slug}', [PackageDealController::class, 'show']);

// Public order tracking by reference (used by email tracking links)
Route::get('/orders/ref/{reference}', [OrderController::class, 'publicShowByReference']);

// Cart routes - allow guest usage
Route::post('/cart/add', [CartController::class, 'add']);
Route::get('/cart', [CartController::class, 'index']);

// Order creation - allow guest checkout
Route::post('/orders', [OrderController::class, 'store']);

// Admin routes (protected by authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::apiResource('products', ProductController::class);

        // Product Gallery Management Routes
        Route::delete('/products/{product}/gallery/{mediaId}', [ProductGalleryController::class, 'deleteGalleryImage']);
        Route::post('/products/{product}/gallery/reorder', [ProductGalleryController::class, 'reorderGalleryImages']);
        Route::patch('/products/{product}/gallery/{mediaId}', [ProductGalleryController::class, 'updateImageMetadata']);
        Route::post('/products/{product}/gallery/attach', [ProductGalleryController::class, 'attachExistingMedia']);

        // Media Library Management Routes (Album System)
        Route::get('/media-library', [MediaLibraryController::class, 'index']);
        Route::get('/media-library/{mediaId}/usage', [MediaLibraryController::class, 'usage']);
        Route::get('/media-library/orphaned', [MediaLibraryController::class, 'orphaned']);
        Route::get('/media-library/duplicates', [MediaLibraryController::class, 'duplicates']);
        Route::delete('/media-library/{mediaId}', [MediaLibraryController::class, 'destroy']);
        Route::post('/media-library/cleanup-orphaned', [MediaLibraryController::class, 'cleanupOrphaned']);

        Route::apiResource('categories', CategoryController::class);
        Route::get('/categories/icons/available', [CategoryController::class, 'getAvailableIcons']);
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

        // Cities admin routes
        Route::get('/cities', [CityController::class, 'index']);
        Route::post('/cities', [CityController::class, 'store']);
        Route::get('/cities/shipping-stats', [CityController::class, 'shippingStats']);
        Route::post('/cities/bulk-update-shipping', [CityController::class, 'bulkUpdateShippingPrices']);
        Route::put('/cities/{city}', [CityController::class, 'update']);
        Route::patch('/cities/{city}', [CityController::class, 'update']);
        Route::delete('/cities/{city}', [CityController::class, 'destroy']);

        // Clients admin routes
        Route::get('/clients', [ClientController::class, 'index']);
        Route::get('/clients/stats', [ClientController::class, 'stats']);
        Route::post('/clients', [ClientController::class, 'store']);
        Route::get('/clients/{client}', [ClientController::class, 'show']);
        Route::put('/clients/{client}', [ClientController::class, 'update']);
        Route::patch('/clients/{client}', [ClientController::class, 'update']);
        Route::delete('/clients/{client}', [ClientController::class, 'destroy']);
        Route::post('/clients/{client}/restore', [ClientController::class, 'restore']);

        // Discounts admin routes
        Route::get('/discounts', [DiscountController::class, 'index']);
        Route::get('/discounts/stats', [DiscountController::class, 'stats']);
        Route::get('/discounts/generate-code', [DiscountController::class, 'generateCode']);
        Route::post('/discounts', [DiscountController::class, 'store']);
        Route::get('/discounts/{discount}', [DiscountController::class, 'show']);
        Route::put('/discounts/{discount}', [DiscountController::class, 'update']);
        Route::patch('/discounts/{discount}', [DiscountController::class, 'update']);
        Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy']);
        Route::post('/discounts/{discount}/toggle-status', [DiscountController::class, 'toggleStatus']);
        Route::post('/discounts/{discount}/reset-uses', [DiscountController::class, 'resetUses']);
        Route::post('/discounts/{discount}/duplicate', [DiscountController::class, 'duplicate']);

        // Roles admin routes (super-admin and admin only)
        Route::middleware(['role:super-admin|admin'])->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::post('/roles', [RoleController::class, 'store']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
            Route::put('/roles/{role}', [RoleController::class, 'update']);
            Route::patch('/roles/{role}', [RoleController::class, 'update']);
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
            Route::post('/roles/{role}/assign-permissions', [RoleController::class, 'assignPermissions']);
            Route::get('/roles/{role}/users', [RoleController::class, 'users']);

            // Permissions admin routes
            Route::get('/permissions', [PermissionController::class, 'index']);
            Route::get('/permissions/grouped', [PermissionController::class, 'grouped']);
            Route::post('/permissions', [PermissionController::class, 'store']);
            Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
            Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
            Route::patch('/permissions/{permission}', [PermissionController::class, 'update']);
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);

            // User roles and permissions management
            Route::post('/users/{user}/assign-roles', [UserRoleController::class, 'assignRoles']);
            Route::post('/users/{user}/assign-permissions', [UserRoleController::class, 'assignPermissions']);
            Route::get('/users/{user}/roles-permissions', [UserRoleController::class, 'getUserRolesAndPermissions']);
            Route::delete('/users/{user}/remove-role', [UserRoleController::class, 'removeRole']);
            Route::get('/users/{user}/check-permission/{permission}', [UserRoleController::class, 'checkPermission']);
            Route::get('/users/{user}/check-role/{role}', [UserRoleController::class, 'checkRole']);

            // User management (CRUD)
            Route::get('/users/stats', [UserController::class, 'stats']);
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{user}', [UserController::class, 'show']);
            Route::put('/users/{user}', [UserController::class, 'update']);
            Route::patch('/users/{user}', [UserController::class, 'update']);
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
            Route::post('/users/{user}/restore', [UserController::class, 'restore']);
            Route::delete('/users/{user}/force-delete', [UserController::class, 'forceDelete']);
            Route::post('/users/{user}/toggle-verification', [UserController::class, 'toggleEmailVerification']);
            Route::post('/users/{user}/revoke-tokens', [UserController::class, 'revokeTokens']);
        });
    });
});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/checkToken', [AuthController::class, 'checkToken']);

    // Profile routes with rate limiting
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::get('/profile/activity', [ProfileController::class, 'activitySummary']);
    Route::get('/profile/roles-permissions', [ProfileController::class, 'rolesAndPermissions']);
    Route::middleware('throttle:5,1')->group(function () {
        Route::put('/profile', [ProfileController::class, 'update']);
    });
    Route::middleware('throttle:3,60')->group(function () {
        Route::delete('/profile', [ProfileController::class, 'destroy']);
    });

    // Page content management for admin users
    Route::put('/page-content/{pageKey}', [App\Http\Controllers\API\PageContentController::class, 'update']);

    Route::apiResource('orders', OrderController::class)->only(['index', 'show']);
    // Secure endpoint to fetch order by reference number (safer than exposing DB id)
    Route::get('/orders/ref/{reference}', [OrderController::class, 'showByReference']);
    Route::apiResource('addresses', AddressController::class);
    Route::get('/addresses/{address}/can-delete', [AddressController::class, 'canDelete']);

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

    // Liked products routes
    Route::get('/liked-products', [LikedProductController::class, 'index']);
    Route::post('/products/{productId}/like', [LikedProductController::class, 'store']);
    Route::delete('/products/{productId}/like', [LikedProductController::class, 'destroy']);
    Route::get('/products/{productId}/is-liked', [LikedProductController::class, 'check']);
});
