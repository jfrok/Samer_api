<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PageContentController extends Controller
{
    // Public endpoint to get page content
    public function show($pageKey)
    {
        $pageContent = PageContent::where('page_key', $pageKey)->first();

        if (!$pageContent) {
            return response()->json(['error' => 'Page not found'], 404);
        }

        return response()->json([
            'data' => $pageContent->content
        ]);
    }

    // Admin endpoint to update page content
    public function update(Request $request, $pageKey)
    {
        // Check if user is admin
        $user = Auth::user();
        if (!$user || !$this->isAdmin($user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $pageContent = PageContent::updateOrCreate(
            ['page_key' => $pageKey],
            [
                'content' => $request->input('content'),
                'updated_by' => $user->id,
            ]
        );

        return response()->json([
            'message' => 'Page content updated successfully',
            'data' => $pageContent->content
        ]);
    }

    private function isAdmin(User $user): bool
    {
        // Check if user email contains 'admin' or matches specific admin emails
        // You can modify this logic based on your admin identification system
        return str_contains($user->email, 'admin') ||
               in_array($user->email, [
                   'jfroosama10@gmail.com', // Add specific admin emails here
                   'admin@samsmy.com'
               ]);
    }
}
