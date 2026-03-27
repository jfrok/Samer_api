<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index()
    {
        $permissions = Permission::with('roles')->get();

        return response()->json([
            'permissions' => $permissions
        ], 200);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Permission created successfully',
            'permission' => $permission
        ], 201);
    }

    /**
     * Display the specified permission.
     */
    public function show($id)
    {
        $permission = Permission::with('roles')->findOrFail($id);

        return response()->json([
            'permission' => $permission
        ], 200);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name,' . $id . '|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $permission->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Permission updated successfully',
            'permission' => $permission
        ], 200);
    }

    /**
     * Remove the specified permission.
     */
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return response()->json([
            'message' => 'Permission deleted successfully'
        ]);
    }

    /**
     * Get permissions grouped by category.
     */
    public function grouped()
    {
        $permissions = Permission::all();

        $grouped = [];
        foreach ($permissions as $permission) {
            // Extract category from permission name (e.g., "view products" -> "products")
            $parts = explode(' ', $permission->name);
            $category = count($parts) > 1 ? $parts[1] : 'general';

            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }

            $grouped[$category][] = $permission;
        }

        return response()->json([
            'grouped_permissions' => $grouped
        ]);
    }
}
