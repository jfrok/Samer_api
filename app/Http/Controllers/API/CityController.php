<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Public endpoint for active cities (for checkout/shipping)
     */
    public function publicIndex()
    {
        $cities = City::where('is_active', true)
            ->orderBy('name')
            ->select(['id', 'name', 'label', 'shipping_price'])
            ->get();

        return response()->json(['data' => $cities]);
    }

    public function index(Request $request)
    {
        $query = City::query()->orderBy('name');

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'label' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:20',
            'country' => 'required|string|max:3',
            'shipping_price' => 'required|numeric|min:0|max:1000000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $city = City::create($data);
        return response()->json(['message' => 'City created', 'data' => $city], 201);
    }

    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'label' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:3',
            'shipping_price' => 'nullable|numeric|min:0|max:1000000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $city->update($validator->validated());
        return response()->json(['message' => 'City updated', 'data' => $city]);
    }

    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['message' => 'City deleted']);
    }

    /**
     * Get shipping price for a specific city
     */
    public function getShippingPrice($cityId)
    {
        $city = City::where('is_active', true)->findOrFail($cityId);

        return response()->json([
            'city_id' => $city->id,
            'city_name' => $city->name,
            'city_label' => $city->label,
            'shipping_price' => (float) $city->shipping_price,
            'formatted_price' => number_format($city->shipping_price, 2) . ' IQD'
        ]);
    }

    /**
     * Calculate shipping price for multiple cities or cart
     */
    public function calculateShipping(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|exists:cities,id',
            'cart_total' => 'nullable|numeric|min:0',
            'apply_free_shipping_threshold' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $city = City::where('is_active', true)->findOrFail($request->city_id);
        $shippingPrice = (float) $city->shipping_price;
        $cartTotal = $request->cart_total ?? 0;

        // Optional: Apply free shipping threshold (can be configured)
        $freeShippingThreshold = config('app.free_shipping_threshold', 100000); // 100k IQD
        $isFreeShipping = false;

        if ($request->boolean('apply_free_shipping_threshold') && $cartTotal >= $freeShippingThreshold) {
            $shippingPrice = 0;
            $isFreeShipping = true;
        }

        return response()->json([
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
                'label' => $city->label,
            ],
            'base_shipping_price' => (float) $city->shipping_price,
            'final_shipping_price' => $shippingPrice,
            'is_free_shipping' => $isFreeShipping,
            'cart_total' => $cartTotal,
            'order_total' => $cartTotal + $shippingPrice,
        ]);
    }

    /**
     * Bulk update shipping prices
     */
    public function bulkUpdateShippingPrices(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'updates' => 'required|array',
            'updates.*.city_id' => 'required|exists:cities,id',
            'updates.*.shipping_price' => 'required|numeric|min:0|max:1000000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updated = [];
        foreach ($request->updates as $update) {
            $city = City::find($update['city_id']);
            if ($city) {
                $city->update(['shipping_price' => $update['shipping_price']]);
                $updated[] = [
                    'id' => $city->id,
                    'name' => $city->name,
                    'shipping_price' => (float) $city->shipping_price,
                ];
            }
        }

        return response()->json([
            'message' => 'Shipping prices updated successfully',
            'updated_cities' => $updated,
            'count' => count($updated)
        ]);
    }

    /**
     * Get shipping statistics
     */
    public function shippingStats()
    {
        $cities = City::where('is_active', true)->get();

        return response()->json([
            'total_cities' => $cities->count(),
            'average_shipping_price' => round($cities->avg('shipping_price'), 2),
            'min_shipping_price' => (float) $cities->min('shipping_price'),
            'max_shipping_price' => (float) $cities->max('shipping_price'),
            'cities_with_free_shipping' => $cities->where('shipping_price', 0)->count(),
        ]);
    }
}
