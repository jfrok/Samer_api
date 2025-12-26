<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Make user_id nullable to support guest orders
            $table->unsignedBigInteger('user_id')->nullable()->change();

            // Add customer details for guest orders
            $table->string('customer_first_name')->nullable()->after('user_id');
            $table->string('customer_last_name')->nullable()->after('customer_first_name');
            $table->string('customer_email')->nullable()->after('customer_last_name');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert user_id to not nullable (be careful with this in production)
            $table->unsignedBigInteger('user_id')->nullable(false)->change();

            // Remove guest customer fields
            $table->dropColumn(['customer_first_name', 'customer_last_name', 'customer_email']);
        });
    }
};
