<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'enable_checkout_quiz'],
            [
                'value' => 'true',
                'description' => 'Enable simple quiz verification at checkout',
                'type' => 'boolean',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('app_settings')->where('key', 'enable_checkout_quiz')->delete();
    }
};
