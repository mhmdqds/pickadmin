<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable();
            $table->foreignId('vendor_id')->nullable();
            $table->foreignId('trip_id')->nullable();
            $table->foreignId('zone_id')->nullable();
            $table->foreignId('module_id')->nullable();
            $table->double('trip_amount',23, 8)->default(0);
            $table->double('store_amount',23, 8)->default(0);
            $table->double('admin_commission',23, 8)->default(0);
            $table->double('tax',23, 8)->default(0);
            $table->string('received_by',50)->nullable();
            $table->double('admin_expense',23, 8)->default(0);
            $table->double('store_expense',23, 8)->default(0);
            $table->string('status',50)->nullable();
            $table->double('discount_amount_by_store',23, 8)->default(0);
            $table->double('additional_charge',23, 8)->default(0);
            $table->double('ref_bonus_amount',23, 8)->default(0);
            $table->boolean('is_subscribed')->default(0);
            $table->double('commission_percentage',23, 8)->default(0);
            $table->double('admin_net_income',23, 8)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_transactions');
    }
}
