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
}
