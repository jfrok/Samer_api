<?php

use Illuminate\Support\Facades\Route;

Route::get('/test-cart', function() {
    try {
        $request = request();
        $request->merge(['product_id' => '1', 'quantity' => 1]);

        $controller = new App\Http\Controllers\API\CartController();
        $response = $controller->add($request);

        return response()->json([
            'status' => 'success',
            'response' => $response->getData()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});
