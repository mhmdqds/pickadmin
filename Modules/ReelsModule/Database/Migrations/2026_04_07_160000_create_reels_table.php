<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('module_id')->index();
            $table->string('module_type', 50)->index();
            $table->text('description');
            $table->string('thumbnail')->nullable();
            $table->string('video')->nullable();
            $table->boolean('is_always_visible')->default(false);
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedBigInteger('total_likes')->default(0);
            $table->unsignedBigInteger('total_store_visits')->default(0);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->string('created_by_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reels');
    }
};
