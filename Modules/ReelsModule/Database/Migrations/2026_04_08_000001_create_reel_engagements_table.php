<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reel_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_id')->constrained('reels')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_id')->nullable();
            $table->string('type', 20);
            $table->timestamps();

            $table->index(['reel_id', 'user_id', 'guest_id', 'type'], 'reel_engagement_lookup_index');
            $table->unique(['reel_id', 'user_id', 'type'], 'reel_user_type_unique');
            $table->unique(['reel_id', 'guest_id', 'type'], 'reel_guest_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reel_engagements');
    }
};
