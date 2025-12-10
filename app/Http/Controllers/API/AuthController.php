<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    // Google OAuth: redirect to provider
    public function googleRedirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Google OAuth: handle callback
    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();
            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Google User',
                    'email' => $googleUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'name' => $googleUser->getName() ?? $user->name,
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Set HTTP-only cookie for session token (API domain)
            $cookieName = 'auth_token';
            $minutes = 60 * 24 * 7; // 7 days
            $cookie = cookie(
                $cookieName,
                $token,
                $minutes,
                null,
                null,
                false, // secure: set true on HTTPS
                true,  // httpOnly
                false, // raw
                'Lax'  // sameSite
            );

            $frontendUrl = config('services.frontend.url', env('FRONTEND_URL', 'http://localhost:3000'));
            $redirectUrl = $frontendUrl . '/auth/callback';
            return redirect($redirectUrl)->withCookie($cookie);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Google OAuth failed', 'error' => $e->getMessage()], 400);
        }
    }

    // Email/password register
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

    // Email/password login
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

    // Admin login
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
        if (!isset($user->role) || strtolower($user->role) !== 'admin') {
            auth()->logout();
            return response()->json(['message' => 'Unauthorized - admin access required'], 403);
        }

        $token = $user->createToken('admin_auth_token')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user]);
    }

    // Logout current token
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }
        // Clear cookie
        $clearCookie = cookie('auth_token', '', -60);
        return response()->json(['message' => 'Logged out'])->withCookie($clearCookie);
    }

    // Generic OAuth token exchange (optional)
    public function handleOAuthCallback(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook',
            'access_token' => 'required|string',
        ]);

        try {
            $provider = $request->provider;
            $providerUser = Socialite::driver($provider)->userFromToken($request->access_token);

            $user = User::where('email', $providerUser->getEmail())->first();
            if (!$user) {
                $user = User::create([
                    'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'User',
                    'email' => $providerUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                ]);
            } else {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                ]);
            }

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
