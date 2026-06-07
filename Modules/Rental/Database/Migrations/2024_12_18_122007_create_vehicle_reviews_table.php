<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVehicleReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vehicle_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id');
            $table->foreignId('module_id');
            $table->foreignId('user_id');
            $table->foreignId('trip_id');
            $table->foreignId('vehicle_id');
            $table->foreignId('vehicle_identity_id')->nullable();
            $table->integer('rating')->nullable();
            $table->mediumText('comment')->nullable();
            $table->text('attachment')->nullable();
            $table->boolean('status')->default(true);
            $table->mediumText('reply')->nullable();
            $table->string('review_id',100)->nullable();
            $table->dateTime('replied_at')->nullable();
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
        Schema::dropIfExists('vehicle_reviews');
    }
}
