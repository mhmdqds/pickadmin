<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('video', 191)->nullable()->after('image');
            $table->text('video_link')->nullable()->after('video');
        });

        Schema::table('temp_products', function (Blueprint $table) {
            $table->string('video', 191)->nullable()->after('image');
            $table->text('video_link')->nullable()->after('video');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['video', 'video_link']);
        });

        Schema::table('temp_products', function (Blueprint $table) {
            $table->dropColumn(['video', 'video_link']);
        });
    }
};
