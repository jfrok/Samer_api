<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API / SPA backend usage, avoid referencing a non-existent named route.
        // If the client expects JSON, return null (standard 401). Otherwise return a plain path.
        if ($request->expectsJson()) {
            return null;
        }
        // Return a fixed path rather than a named route to prevent RouteNotFoundException.
        return '/login';
    }
}
