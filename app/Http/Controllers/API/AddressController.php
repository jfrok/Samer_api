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
            'type' => 'required|in:shipping,billing',
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'zip_code' => 'required|string|max:20',
        ]);

        $address = Auth::user()->addresses()->create($request->all());

        return response()->json($address, 201);
    }

    // Add update/delete as needed
}
