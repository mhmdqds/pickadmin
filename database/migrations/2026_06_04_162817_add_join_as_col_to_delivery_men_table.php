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
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->boolean('is_delivery')->default(1);
            $table->boolean('is_ride')->default(0);
            $table->boolean('earning')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_men', function (Blueprint $table) {
            $table->dropColumn('is_delivery');
            $table->dropColumn('is_ride');
            $table->boolean('earning')->default(1)->change();
        });
    }
};
