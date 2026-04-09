<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Jobs\SendOwnerOrderNotificationJob;

$order = Order::latest()->first();

if ($order) {
    $ownerEmail = config('mail.owner_email') ?? env('OWNER_EMAIL');

    echo "Testing owner notification email\n";
    echo "Order ID: {$order->id}\n";
    echo "Order Number: {$order->order_number}\n";
    echo "Owner Email: {$ownerEmail}\n";
    echo "\nDispatching job...\n";

    // Dispatch job with 5 second delay to test immediately
    SendOwnerOrderNotificationJob::dispatch($order, $ownerEmail)->delay(now()->addSeconds(5));

    echo "✅ Job dispatched successfully!\n";
    echo "Check the queue worker output in 5 seconds...\n";
    echo "\nTo check queue status:\n";
    echo "  php artisan queue:failed\n";
    echo "  php artisan tinker --execute=\"echo 'Pending: ' . DB::table('jobs')->count() . PHP_EOL;\"\n";
} else {
    echo "❌ No orders found in database.\n";
    echo "Create an order via API first.\n";
}
