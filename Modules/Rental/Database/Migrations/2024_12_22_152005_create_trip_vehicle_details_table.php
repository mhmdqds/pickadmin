<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripVehicleDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_vehicle_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('trip_details_id')->nullable();
            $table->foreignId('vehicle_identity_id')->nullable();
            $table->foreignId('vehicle_driver_id')->nullable();
            $table->dateTime('estimated_trip_end_time')->nullable();
            $table->boolean('is_completed')->default(0);
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
        Schema::dropIfExists('trip_vehicle_details');
    }
}
