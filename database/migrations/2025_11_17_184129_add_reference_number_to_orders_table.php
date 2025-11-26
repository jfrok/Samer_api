<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('id');
            $table->string('order_number')->nullable()->after('reference_number');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending')->after('payment_method');
        });

        // Generate reference and order numbers for existing orders
        $orders = DB::table('orders')->whereNull('reference_number')->get();
        foreach ($orders as $order) {
            $date = date('Ymd', strtotime($order->created_at));
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $referenceNumber = "REF-{$date}-{$random}";
            $orderNumber = 'ORD-' . strtoupper(uniqid());

            DB::table('orders')
                ->where('id', $order->id)
                ->update([
                    'reference_number' => $referenceNumber,
                    'order_number' => $orderNumber
                ]);
        }

        // Now make them unique
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference_number')->unique()->change();
            $table->string('order_number')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'order_number', 'payment_status']);
        });
    }
};
