<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('provider_id');
            $table->foreignId('zone_id');
            $table->foreignId('module_id');
            $table->foreignId('pickup_zone_id')->nullable();
            $table->foreignId('cash_back_id')->nullable();
            $table->double('trip_amount',23, 8)->default(0);
            $table->double('discount_on_trip',23, 8)->default(0);
            $table->enum('discount_on_trip_by',['admin','vendor','none'])->default('none');
            $table->double('coupon_discount_amount',23, 8)->default(0);
            $table->enum('coupon_discount_by',['admin','vendor','none'])->default('none');
            $table->string('coupon_code',100)->nullable();
            $table->enum('trip_status',['pending','confirmed','ongoing','completed','canceled','payment_failed','processing','waiting'])->default('pending');
            $table->enum('payment_status',['paid','unpaid','partially_paid'])->default('unpaid');
            $table->string('payment_method',100)->nullable();
            $table->string('transaction_reference',100)->nullable();
            $table->double('tax_amount',23, 8)->default(0);
            $table->enum('tax_status',['included','excluded'])->default('excluded');
            $table->double('tax_percentage',23, 8)->default(0);
            $table->enum('trip_type',['hourly','distance_wise'])->default('hourly');
            $table->double('additional_charge',23, 8)->default(0);
            $table->double('partially_paid_amount',23, 8)->default(0);
            $table->double('distance',23, 8)->default(0);
            $table->double('estimated_hours',23, 8)->default(0);
            $table->double('ref_bonus_amount',23, 8)->default(0);
            $table->enum('canceled_by',['admin','vendor','user','none'])->default('none');
            $table->string('attachment')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->text('pickup_location')->nullable();
            $table->text('destination_location')->nullable();
            $table->text('user_info')->nullable();
            $table->text('trip_note')->nullable();
            $table->string('callback')->nullable();
            $table->string('otp',100)->nullable();
            $table->boolean('is_guest')->default(0);
            $table->boolean('edited')->default(0);
            $table->boolean('checked')->default(0);
            $table->boolean('scheduled')->default(0);
            $table->dateTime('schedule_at')->nullable();
            $table->dateTime('pending')->nullable();
            $table->dateTime('confirmed')->nullable();
            $table->dateTime('ongoing')->nullable();
            $table->dateTime('completed')->nullable();
            $table->dateTime('canceled')->nullable();
            $table->dateTime('payment_failed')->nullable();
            $table->smallInteger('quantity')->default(1);
            $table->dateTime('estimated_trip_end_time')->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE trips AUTO_INCREMENT = 100000;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
}
