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
        Schema::table('automated_messages', function (Blueprint $table) {
            $table->string('question_for')->default(CUSTOMER)->after('id');
            $table->string('question')->after('question_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('automated_messages', function (Blueprint $table) {
            $table->dropColumn('question_for');
            $table->dropColumn('question');
        });
    }
};
