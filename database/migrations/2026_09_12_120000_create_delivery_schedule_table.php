<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * Independent table that mirrors the structure of `store_schedule`
     * (Daily time schedule) so that the existing schedule logic is not
     * touched. Used to define the hours during which the store accepts
     * Delivery orders (separate from store opening hours).
     */
    public function up(): void
    {
        if (Schema::hasTable('delivery_schedule')) {
            return;
        }

        Schema::create('delivery_schedule', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedTinyInteger('day'); // 0 (Sun) .. 6 (Sat)
            $table->time('opening_time');
            $table->time('closing_time');
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index(['store_id', 'day']);

            $table->foreign('store_id')
                ->references('id')->on('stores')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_schedule');
    }
};
