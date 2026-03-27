<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $addresses = Auth::user()->addresses;

        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'type' => 'required|in:shipping,billing',
            'street' => 'required|string|max:255',
            'closest_point' => 'nullable|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'is_default' => 'boolean',
        ]);

        $user = Auth::user();

        // Check if user already has 3 addresses
        $currentAddressCount = $user->addresses()->count();
        if ($currentAddressCount >= 3) {
            return response()->json([
                'message' => 'لا يمكن إضافة أكثر من 3 عناوين. يرجى حذف عنوان قديم قبل إضافة عنوان جديد.',
                'error' => 'Address limit exceeded'
            ], 422);
        }

        // If setting as default, unset other defaults
        if ($request->is_default) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create($request->all());

        return response()->json($address, 201);
    }

    public function show(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        return response()->json($address, 200);
    }

    public function update(Request $request, Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'sometimes|nullable|string|max:100',
            'type' => 'sometimes|in:shipping,billing',
            'street' => 'sometimes|string|max:255',
            'closest_point' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'country' => 'sometimes|string|max:100',
            'is_default' => 'boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            Auth::user()->addresses()->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json($address);
    }

    public function destroy(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }

    public function canDelete(Address $address)
    {
        if ($address->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if address is used in any orders
        $isUsedInOrders = \DB::table('orders')
            ->where('shipping_address_id', $address->id)
            ->exists();

        return response()->json([
            'can_delete' => !$isUsedInOrders,
            'message' => $isUsedInOrders ?
                'لا يمكن حذف هذا العنوان لأنه مستخدم في طلبات سابقة' :
                null
        ]);
    }
}
