<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    // Facebook OAuth: redirect to provider
    public function facebookRedirect()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
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

    // Facebook OAuth: handle callback
    public function facebookCallback(Request $request)
    {
        try {
            $facebookUser = Socialite::driver('facebook')->stateless()->user();

            $user = User::where('email', $facebookUser->getEmail())->first();
            if (!$user) {
                $user = User::create([
                    'name' => $facebookUser->getName() ?? $facebookUser->getNickname() ?? 'Facebook User',
                    'email' => $facebookUser->getEmail(),
                    'password' => Hash::make(Str::random(32)),
                    'facebook_id' => $facebookUser->getId(),
                    'email_verified_at' => now(),
                ]);
            } else {
                $user->update([
                    'facebook_id' => $facebookUser->getId(),
                    'name' => $facebookUser->getName() ?? $user->name,
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
            return response()->json(['message' => 'Facebook OAuth failed', 'error' => $e->getMessage()], 400);
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

    // Generic OAuth token exchange (updated to handle Google OAuth properly)
    public function handleOAuthCallback(Request $request)
    {
        $request->validate([
            'provider' => 'required|in:google,facebook',
            'access_token' => 'required|string',
        ]);

        Log::info('OAuth callback received', [
            'provider' => $request->provider,
            'has_token' => !empty($request->access_token),
            'token_length' => strlen($request->access_token),
        ]);

        try {
            $provider = $request->provider;
            $accessToken = $request->access_token;

            if ($provider === 'google') {
                Log::info('Fetching Google user info', ['token_prefix' => substr($accessToken, 0, 20) . '...']);

                // Manually fetch Google user info using access token
                $userResponse = Http::timeout(10)->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                ])->get('https://www.googleapis.com/oauth2/v3/userinfo');

                Log::info('Google API response', [
                    'status' => $userResponse->status(),
                    'successful' => $userResponse->successful(),
                    'response_time' => now()->toISOString(),
                ]);

                if (!$userResponse->successful()) {
                    Log::error('Google user info fetch failed', [
                        'status' => $userResponse->status(),
                        'response' => $userResponse->json()
                    ]);
                    throw new \Exception('Failed to fetch Google user information');
                }

                $googleUser = $userResponse->json();

                // Find or create user
                $user = User::where('google_id', $googleUser['sub'])
                    ->orWhere('email', $googleUser['email'])
                    ->first();

                if (!$user) {
                    // Generate unique username from Google data
                    $baseName = $googleUser['name'] ?? explode('@', $googleUser['email'])[0] ?? 'user';
                    $base = Str::slug($baseName, '-');
                    $username = $this->generateUniqueUsername($base);

                    $user = User::create([
                        'name' => $googleUser['name'] ?? $username,
                        'email' => $googleUser['email'],
                        'password' => Hash::make(Str::random(32)),
                        'google_id' => $googleUser['sub'],
                        'email_verified_at' => now(),
                        'phone' => null,
                    ]);
                } else {
                    // Update existing user with Google data
                    $user->update([
                        'google_id' => $googleUser['sub'],
                        'name' => $googleUser['name'] ?? $user->name,
                        'email_verified_at' => $user->email_verified_at ?? now(),
                    ]);
                }
            } else {
                // Handle other providers (Facebook, etc.) with Socialite
                $providerUser = Socialite::driver($provider)->userFromToken($accessToken);

                $user = User::where('email', $providerUser->getEmail())->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $providerUser->getName() ?? $providerUser->getNickname() ?? 'User',
                        'email' => $providerUser->getEmail(),
                        'password' => Hash::make(Str::random(32)),
                        'email_verified_at' => now(),
                        'facebook_id' => $providerUser->getId(),
                    ]);
                } else {
                    $user->update([
                        'facebook_id' => $providerUser->getId(),
                        'name' => $providerUser->getName() ?? $user->name,
                    ]);
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'message' => 'Successfully authenticated with ' . ucfirst($provider)
            ], 200);
        } catch (\Exception $e) {
            Log::error('OAuth Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Authentication failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a unique username from a base string
     */
    public function generateUniqueUsername($base, $counter = 0)
    {
        $username = $counter === 0 ? $base : $base . $counter;

        // Check if username already exists
        if (User::where('name', $username)->exists()) {
            return $this->generateUniqueUsername($base, $counter + 1);
        }

        return $username;
    }
}
