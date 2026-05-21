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
        Schema::table('d_m_vehicles', function (Blueprint $table) {
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_delivery')->default(1);
            $table->boolean('is_ride')->default(0);
            $table->double('starting_coverage_area',16,2)->default(0)->change();
            $table->double('maximum_coverage_area',16,2)->default(0)->change();
            $table->double('extra_charges',16,2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('d_m_vehicles', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->dropColumn('description');
            $table->dropColumn('image');
            $table->dropColumn('is_delivery');
            $table->dropColumn('is_ride');
            $table->double('starting_coverage_area',16,2)->change();
            $table->double('maximum_coverage_area',16,2)->change();
            $table->double('extra_charges',16,2)->change();
        });
    }
};
