<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    /**
     * Display a listing of discounts (Admin)
     */
    public function index(Request $request)
    {
        $query = Discount::query();

        // Filter by status
        if ($request->has('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by code
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Filter active/expired
        if ($request->boolean('active_only')) {
            $query->active();
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $discounts = $query->paginate($perPage);

        return response()->json($discounts);
    }

    /**
     * Store a newly created discount (Admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50|unique:discounts,code',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate percentage value
        if ($request->type === 'percentage' && $request->value > 100) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100%',
                'errors' => ['value' => ['Maximum percentage is 100']]
            ], 422);
        }

        $data = $validator->validated();
        $data['code'] = strtoupper($data['code']); // Store codes in uppercase
        $data['uses_count'] = 0;

        $discount = Discount::create($data);

        return response()->json([
            'message' => 'Discount created successfully',
            'discount' => $discount
        ], 201);
    }

    /**
     * Display the specified discount (Admin)
     */
    public function show($id)
    {
        $discount = Discount::findOrFail($id);

        return response()->json([
            'discount' => $discount,
            'remaining_uses' => $discount->max_uses ? max(0, $discount->max_uses - $discount->uses_count) : null,
            'is_expired' => !$discount->is_active || ($discount->end_date && $discount->end_date->isPast()),
        ]);
    }

    /**
     * Update the specified discount (Admin)
     */
    public function update(Request $request, $id)
    {
        $discount = Discount::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|max:50|unique:discounts,code,' . $id,
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate percentage value
        if ($request->has('type') && $request->type === 'percentage' && $request->value > 100) {
            return response()->json([
                'message' => 'Percentage discount cannot exceed 100%',
                'errors' => ['value' => ['Maximum percentage is 100']]
            ], 422);
        }

        $data = $validator->validated();
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $discount->update($data);

        return response()->json([
            'message' => 'Discount updated successfully',
            'discount' => $discount->fresh()
        ]);
    }

    /**
     * Remove the specified discount (Admin)
     */
    public function destroy($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->delete();

        return response()->json([
            'message' => 'Discount deleted successfully'
        ]);
    }

    /**
     * Toggle discount active status (Admin)
     */
    public function toggleStatus($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['is_active' => !$discount->is_active]);

        return response()->json([
            'message' => 'Discount status updated',
            'discount' => $discount,
            'is_active' => $discount->is_active
        ]);
    }

    /**
     * Reset uses count (Admin)
     */
    public function resetUses($id)
    {
        $discount = Discount::findOrFail($id);
        $discount->update(['uses_count' => 0]);

        return response()->json([
            'message' => 'Discount uses count reset successfully',
            'discount' => $discount
        ]);
    }

    /**
     * Duplicate a discount with new code (Admin)
     */
    public function duplicate(Request $request, $id)
    {
        $original = Discount::findOrFail($id);

        // If new_code is provided, validate it
        if ($request->has('new_code') && $request->new_code) {
            $validator = Validator::make($request->all(), [
                'new_code' => 'required|string|max:50|unique:discounts,code',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $newCode = strtoupper($request->new_code);
        } else {
            // Auto-generate a unique code with COPY- prefix
            $baseCode = $original->code;
            $counter = 1;

            do {
                $newCode = "COPY{$counter}-" . $baseCode;
                $counter++;
            } while (Discount::where('code', $newCode)->exists());
        }

        $newDiscount = $original->replicate();
        $newDiscount->code = $newCode;
        $newDiscount->uses_count = 0;
        $newDiscount->save();

        return response()->json([
            'message' => 'Discount duplicated successfully',
            'discount' => $newDiscount
        ], 201);
    }

    /**
     * Get discount statistics (Admin)
     */
    public function stats()
    {
        $total = Discount::count();
        $active = Discount::active()->count();
        $inactive = Discount::where('is_active', false)->count();
        $expired = Discount::where('end_date', '<', now())->count();

        $mostUsed = Discount::orderBy('uses_count', 'desc')
            ->limit(5)
            ->get(['code', 'uses_count', 'type', 'value']);

        $totalDiscountValue = Discount::where('type', 'fixed')->sum('value');

        return response()->json([
            'total_discounts' => $total,
            'active_discounts' => $active,
            'inactive_discounts' => $inactive,
            'expired_discounts' => $expired,
            'most_used' => $mostUsed,
            'total_fixed_discount_value' => round($totalDiscountValue, 2),
        ]);
    }

    /**
     * Generate a random discount code (Admin helper)
     */
    public function generateCode(Request $request)
    {
        $prefix = $request->get('prefix', '');
        $length = min(max((int) $request->get('length', 8), 4), 20);

        do {
            $code = $prefix . strtoupper(Str::random($length));
        } while (Discount::where('code', $code)->exists());

        return response()->json([
            'code' => $code
        ]);
    }

    /**
     * Validate discount code (Public)
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'nullable|numeric',
            'product_id' => 'nullable|integer'
        ]);

        $discount = Discount::active()->where('code', strtoupper($request->code))->first();

        if (!$discount) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired promotion code'
            ], 400);
        }

        // If order amount is provided, check full applicability
        if ($request->has('order_amount')) {
            // Cast to float to avoid string/decimal comparison issues
            $orderAmount = (float) $request->order_amount;
            if (!$discount->isApplicable($orderAmount)) {
                return response()->json([
                    'valid' => false,
                    'message' => $orderAmount < (float) $discount->min_order_amount
                        ? "Minimum order amount of {$discount->min_order_amount} required"
                        : 'Discount limit reached'
                ], 400);
            }

            return response()->json([
                'valid' => true,
                'discount_amount' => $discount->calculateDiscount($orderAmount),
                'type' => $discount->type,
                'value' => $discount->value,
                'code' => $discount->code,
                'message' => 'Promotion code applied successfully'
            ]);
        }

        // For product page validation (without order amount)
        return response()->json([
            'valid' => true,
            'type' => $discount->type,
            'value' => $discount->value,
            'code' => $discount->code,
            'min_order_amount' => $discount->min_order_amount,
            'message' => $discount->type === 'percentage'
                ? "{$discount->value}% discount will be applied"
                : "IQD {$discount->value} discount will be applied"
        ]);
    }
}
