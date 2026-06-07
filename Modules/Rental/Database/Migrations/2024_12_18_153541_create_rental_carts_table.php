<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRentalCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id');
            $table->foreignId('user_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('module_id');
            $table->integer('quantity')->default(1);
            $table->boolean('is_guest')->default(0);
            $table->double('price',23, 8)->default(0);
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
        Schema::dropIfExists('rental_carts');
    }
}
