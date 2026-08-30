<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountDeletionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * account_deletion_logs
     * --------------------------------------------------------------------------
     * Audit trail for every action taken inside the deletion pipeline.
     * Used by the Admin Panel "Account Deletion" -> "Details" view.
     *
     * SECURITY: never log password, OTP, access tokens, payment secrets or any
     * raw personal data. Only store counts, status transitions and metadata.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deletion_request_id')
                ->constrained('account_deletion_requests')
                ->cascadeOnDelete();
            $table->string('action', 64); // e.g. 'request_created','verification_passed','anonymized','deleted','retained','job_failed','retry'
            $table->string('actor_type', 32)->nullable(); // 'user' | 'admin' | 'system'
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('status', 32)->nullable(); // mirror of request status at log time
            $table->string('target_table', 64)->nullable(); // which table was touched
            $table->unsignedBigInteger('affected_rows')->nullable(); // # rows affected
            $table->text('message')->nullable(); // human-readable
            $table->json('metadata')->nullable(); // extra context (no PII)
            $table->timestamp('created_at')->useCurrent();

            $table->index(['deletion_request_id', 'created_at'], 'idx_adl_request_created');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_deletion_logs');
    }
}
