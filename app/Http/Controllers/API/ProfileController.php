<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Get authenticated user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'provider' => $user->provider,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    /**
     * Update user profile with security measures
     * Rate limiting: 5 attempts per minute per user
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Rate limiting to prevent brute force attacks
        $key = 'profile-update:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'تم تجاوز عدد المحاولات. يرجى المحاولة بعد ' . $seconds . ' ثانية.',
                'error' => 'Too many attempts. Please try again in ' . $seconds . ' seconds.'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Validate input with strict rules
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|regex:/^[\p{Arabic}\p{L}\s]+$/u',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20|regex:/^[0-9+\-\s()]+$/',
            'current_password' => 'required_with:password|string',
            'password' => 'nullable|string|min:8|confirmed|different:current_password',
        ], [
            'name.regex' => 'الاسم يجب أن يحتوي على حروف فقط',
            'phone.regex' => 'رقم الهاتف غير صحيح',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password.different' => 'كلمة المرور الجديدة يجب أن تكون مختلفة عن القديمة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        // Security: Sanitize input to prevent XSS
        $data = [];

        if ($request->has('name')) {
            $data['name'] = strip_tags(trim($request->name));
        }

        if ($request->has('email')) {
            // Check if email change requires verification
            if ($request->email !== $user->email) {
                $data['email'] = filter_var($request->email, FILTER_SANITIZE_EMAIL);
                $data['email_verified_at'] = null; // Require re-verification
            }
        }

        if ($request->has('phone')) {
            $data['phone'] = preg_replace('/[^0-9+\-\s()]/', '', $request->phone);
        }

        // Password update with current password verification
        if ($request->filled('password')) {
            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                RateLimiter::hit($key, 60); // Penalize wrong password attempts

                return response()->json([
                    'message' => 'كلمة المرور الحالية غير صحيحة',
                    'errors' => [
                        'current_password' => ['Current password is incorrect']
                    ]
                ], 422);
            }

            // Check if new password is not in common passwords list (basic check)
            $commonPasswords = ['password', '12345678', 'qwerty123', 'password123'];
            if (in_array(strtolower($request->password), $commonPasswords)) {
                return response()->json([
                    'message' => 'كلمة المرور ضعيفة جداً. اختر كلمة مرور أقوى',
                    'errors' => [
                        'password' => ['Password is too common. Choose a stronger password.']
                    ]
                ], 422);
            }

            $data['password'] = Hash::make($request->password);

            // Revoke all other tokens for security (force re-login on other devices)
            $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();
        }

        // Update user
        try {
            $user->update($data);

            // Clear rate limiter on success
            RateLimiter::clear($key);

            return response()->json([
                'message' => 'تم تحديث الملف الشخصي بنجاح',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                    'provider' => $user->provider,
                    'created_at' => $user->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل تحديث الملف الشخصي',
                'error' => 'Failed to update profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete user account (soft delete with confirmation)
     * Rate limiting: 3 attempts per hour
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Rate limiting for account deletion
        $key = 'account-delete:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'تم تجاوز عدد المحاولات',
                'error' => 'Too many deletion attempts.'
            ], 429);
        }

        RateLimiter::hit($key, 3600); // 1 hour

        // Require password confirmation
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:DELETE',
        ]);

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة',
                'errors' => [
                    'password' => ['Password is incorrect']
                ]
            ], 422);
        }

        try {
            // Revoke all tokens
            $user->tokens()->delete();

            // Delete user (consider soft delete in production)
            $user->delete();

            return response()->json([
                'message' => 'تم حذف الحساب بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل حذف الحساب',
                'error' => 'Failed to delete account.'
            ], 500);
        }
    }

    /**
     * Get user's activity summary (orders, reviews, etc.)
     */
    public function activitySummary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'summary' => [
                'total_orders' => $user->orders()->count(),
                'pending_orders' => $user->orders()->where('status', 'pending')->count(),
                'total_reviews' => $user->reviews()->count(),
                'total_addresses' => $user->addresses()->count(),
                'member_since' => $user->created_at->format('Y-m-d'),
            ]
        ]);
    }
}
