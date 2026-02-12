<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions']);

        // Filter by role
        if ($request->has('role')) {
            $query->role($request->role);
        }

        // Filter by status (active/deleted)
        if ($request->has('status')) {
            if ($request->status === 'deleted') {
                $query->onlyTrashed();
            } elseif ($request->status === 'all') {
                $query->withTrashed();
            }
        }

        // Search by name, email, or phone
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'email_verified_at' => now(),
        ]);

        // Assign roles if provided
        if ($request->has('roles')) {
            $user->assignRole($request->roles);
        }

        // Assign permissions if provided
        if ($request->has('permissions')) {
            $user->givePermissionTo($request->permissions);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles', 'permissions')
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::withTrashed()
            ->with(['roles.permissions', 'permissions', 'orders', 'addresses', 'reviews'])
            ->findOrFail($id);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'deleted_at' => $user->deleted_at,
                'roles' => $user->roles,
                'direct_permissions' => $user->permissions,
                'all_permissions' => $user->getAllPermissions(),
            ],
            'statistics' => [
                'total_orders' => $user->orders()->count(),
                'total_addresses' => $user->addresses()->count(),
                'total_reviews' => $user->reviews()->count(),
            ]
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->name;
        }

        if ($request->has('email')) {
            $data['email'] = $request->email;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        if ($request->has('phone')) {
            $data['phone'] = $request->phone;
        }

        $user->update($data);

        // Update roles if provided
        if ($request->has('roles')) {
            $user->syncRoles($request->roles);
        }

        // Update permissions if provided
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh()->load('roles', 'permissions')
        ]);
    }

    /**
     * Soft delete the specified user.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deletion of super-admin users
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'message' => 'Cannot delete super admin users'
            ], 403);
        }

        // Revoke all user's tokens before deletion
        $user->tokens()->delete();

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully (soft delete)'
        ]);
    }

    /**
     * Restore a soft deleted user.
     */
    public function restore($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        return response()->json([
            'message' => 'User restored successfully',
            'user' => $user->load('roles', 'permissions')
        ]);
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        // Prevent permanent deletion of super-admin users
        if ($user->hasRole('super-admin')) {
            return response()->json([
                'message' => 'Cannot permanently delete super admin users'
            ], 403);
        }

        // Delete all related data
        $user->tokens()->delete();
        $user->orders()->delete();
        $user->addresses()->delete();
        $user->reviews()->delete();

        $user->forceDelete();

        return response()->json([
            'message' => 'User permanently deleted'
        ]);
    }

    /**
     * Get users statistics.
     */
    public function stats()
    {
        $total = User::count();
        $active = User::whereNotNull('email_verified_at')->count();
        $deleted = User::onlyTrashed()->count();

        $byRole = [];
        foreach (\Spatie\Permission\Models\Role::all() as $role) {
            $byRole[$role->name] = $role->users()->count();
        }

        return response()->json([
            'total_users' => $total,
            'active_users' => $active,
            'deleted_users' => $deleted,
            'new_this_month' => User::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'users_by_role' => $byRole,
        ]);
    }

    /**
     * Toggle user's email verification status.
     */
    public function toggleEmailVerification($id)
    {
        $user = User::findOrFail($id);

        $user->email_verified_at = $user->email_verified_at ? null : now();
        $user->save();

        return response()->json([
            'message' => 'Email verification status updated',
            'user' => $user,
            'is_verified' => $user->email_verified_at !== null
        ]);
    }

    /**
     * Revoke all user's tokens.
     */
    public function revokeTokens($id)
    {
        $user = User::findOrFail($id);
        $tokensCount = $user->tokens()->count();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'All user tokens revoked successfully',
            'tokens_revoked' => $tokensCount
        ]);
    }
}
