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
        // Check if 'checked' column doesn't exist, then add it
        if (!Schema::hasColumn('orders', 'checked')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->boolean('checked')->default(1)->after('order_status');
            });
        }
        
        // Ensure cancelled orders have checked = 0 for notification system
        DB::table('orders')
            ->where('order_status', 'canceled')
            ->whereNull('checked')
            ->update(['checked' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('orders', 'checked')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('checked');
            });
        }
    }
};