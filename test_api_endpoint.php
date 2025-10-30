<?php

// Test the exact API endpoint that's failing
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Create a test request to /api/products
$request = \Illuminate\Http\Request::create('/api/products', 'GET');

try {
    echo "Testing /api/products endpoint...\n\n";

    $response = $kernel->handle($request);

    echo "Status Code: " . $response->getStatusCode() . "\n";

    if ($response->getStatusCode() === 200) {
        $content = $response->getContent();
        $data = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            echo "Response is valid JSON\n";
            echo "Number of products: " . count($data['data'] ?? $data) . "\n";

            if (isset($data['data'][0])) {
                $firstProduct = $data['data'][0];
                echo "First product: " . ($firstProduct['name'] ?? 'Name missing') . "\n";
                echo "First product variants: " . count($firstProduct['variants'] ?? []) . "\n";
            }
        } else {
            echo "Invalid JSON response\n";
            echo "First 500 chars: " . substr($content, 0, 500) . "\n";
        }
    } else {
        echo "Error response:\n";
        echo $response->getContent() . "\n";
    }

} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

$kernel->terminate($request, $response ?? null);
