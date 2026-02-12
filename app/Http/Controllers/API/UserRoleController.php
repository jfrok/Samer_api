<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserRoleController extends Controller
{
    /**
     * Assign roles to a user.
     */
    public function assignRoles(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->syncRoles($request->roles);

        return response()->json([
            'message' => 'Roles assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Assign permissions directly to a user.
     */
    public function assignPermissions(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->syncPermissions($request->permissions);

        return response()->json([
            'message' => 'Permissions assigned successfully',
            'user' => $user->load('permissions')
        ]);
    }

    /**
     * Get user's roles and permissions.
     */
    public function getUserRolesAndPermissions($userId)
    {
        $user = User::with('roles.permissions', 'permissions')->findOrFail($userId);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->roles,
            'direct_permissions' => $user->permissions,
            'all_permissions' => $user->getAllPermissions(),
        ]);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'role' => 'required|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->removeRole($request->role);

        return response()->json([
            'message' => 'Role removed successfully',
            'user' => $user->load('roles')
        ]);
    }

    /**
     * Check if user has permission.
     */
    public function checkPermission($userId, $permission)
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'user_id' => $userId,
            'permission' => $permission,
            'has_permission' => $user->hasPermissionTo($permission)
        ]);
    }

    /**
     * Check if user has role.
     */
    public function checkRole($userId, $role)
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'user_id' => $userId,
            'role' => $role,
            'has_role' => $user->hasRole($role)
        ]);
    }
}
