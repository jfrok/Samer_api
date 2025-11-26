<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            // Return friendly error messages without translation
            $message = match($status) {
                Password::INVALID_USER => 'We could not find a user with that email address.',
                Password::RESET_THROTTLED => 'Please wait before retrying.',
                default => 'Unable to send password reset link. Please try again later.'
            };

            throw ValidationException::withMessages([
                'email' => [$message],
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'We have emailed your password reset link!'
        ]);
    }
}
