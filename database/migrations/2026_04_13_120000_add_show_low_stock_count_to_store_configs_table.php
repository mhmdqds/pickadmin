<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(Schema::hasTable('store_configs') ? 'store_configs' : 'storeConfigs', function (Blueprint $table) {
            $table->boolean('show_low_stock_count')->default(1)->after('minimum_stock_for_warning');
        });
    }

    public function down(): void
    {
        Schema::table(Schema::hasTable('store_configs') ? 'store_configs' : 'storeConfigs', function (Blueprint $table) {
            $table->dropColumn('show_low_stock_count');
        });
    }
};
