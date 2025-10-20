<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate(['code' => 'required|string', 'order_amount' => 'required|numeric']);

        $discount = Discount::active()->where('code', $request->code)->first();

        if (!$discount || !$discount->isApplicable($request->order_amount)) {
            return response()->json(['valid' => false], 400);
        }

        return response()->json([
            'valid' => true,
            'discount_amount' => $discount->calculateDiscount($request->order_amount),
            'type' => $discount->type,
        ]);
    }
}
