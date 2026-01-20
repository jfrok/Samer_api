<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('name')->after('user_id')->nullable(); // Address name like "Home", "Work"
            $table->text('closest_point')->after('street')->nullable(); // Closest landmark/point for clarification
            $table->dropColumn('zip_code'); // Remove postal code
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn(['name', 'closest_point']);
            $table->string('zip_code')->after('country');
        });
    }
};
