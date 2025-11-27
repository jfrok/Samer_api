<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'nullable|numeric',
            'product_id' => 'nullable|integer'
        ]);

        $discount = Discount::active()->where('code', $request->code)->first();

        if (!$discount) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired promotion code'
            ], 400);
        }

        // If order amount is provided, check full applicability
        if ($request->has('order_amount')) {
            if (!$discount->isApplicable($request->order_amount)) {
                return response()->json([
                    'valid' => false,
                    'message' => $request->order_amount < $discount->min_order_amount
                        ? "Minimum order amount of {$discount->min_order_amount} required"
                        : 'Discount limit reached'
                ], 400);
            }

            return response()->json([
                'valid' => true,
                'discount_amount' => $discount->calculateDiscount($request->order_amount),
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
                : "SAR {$discount->value} discount will be applied"
        ]);
    }
}
