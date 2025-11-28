<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Fallback login path used only to satisfy redirects from auth middleware; returns JSON for API clients.
Route::get('/login', function () {
    // If the request prefers JSON, send an unauthenticated message; otherwise simple text.
    if (request()->expectsJson()) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    return 'Unauthenticated - please login via the frontend application.';
})->name('login');
