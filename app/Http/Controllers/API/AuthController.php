<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    /**
     * Admin login endpoint - only allows users with role 'admin'
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        // Ensure user has admin role
        if (!isset($user->role) || strtolower($user->role) !== 'admin') {
            // Log out the attempted session token
            auth()->logout();
            return response()->json(['message' => 'Unauthorized - admin access required'], 403);
        }

        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    /**
     * Handle OAuth callback and authenticate user
     */
    public function handleOAuthCallback(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook',
            'access_token' => 'required|string',
        ]);

        try {
            $provider = $request->provider;

            // Get user info from provider using the access token
            $providerUser = Socialite::driver($provider)->userFromToken($request->access_token);

            // Find or create user
            $user = User::where('email', $providerUser->getEmail())->first();

            if (!$user) {
                // Create new user from OAuth provider
                $user = User::create([
                    'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'User',
                    'email' => $providerUser->getEmail(),
                    'password' => Hash::make(Str::random(32)), // Random password for OAuth users
                    'email_verified_at' => now(), // OAuth emails are pre-verified
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                ]);
            } else {
                // Update provider info if user exists
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                ]);
            }

            // Create token for the user
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
                'message' => 'Successfully authenticated with ' . ucfirst($provider)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to authenticate with OAuth provider',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
