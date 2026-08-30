<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountDeletionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * account_deletion_requests
     * --------------------------------------------------------------------------
     * Stores the lifecycle of every account-deletion request initiated from
     * either the Flutter app (API) or the public Web /delete-account page.
     *
     * Design notes:
     *   - `request_uuid` is the only public-facing identifier. The numeric
     *     `user_id` is intentionally nullable: a public-web visitor may not be
     *     logged in yet (they authenticate INSIDE the deletion flow), and we
     *     must not leak which user_id matches which email/phone.
     *   - No password, OTP, or token is ever persisted on this row.
     *   - `retention_reason` is set ONLY if some data has to be retained for
     *     legal/financial reasons (orders/payments). Free-text but bounded.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->uuid('request_uuid')->unique();
            $table->string('verification_method', 16); // 'email_password' | 'phone_otp'
            $table->string('channel', 16)->default('api'); // 'api' (Flutter) | 'web' (browser)
            $table->string('status', 32)->default('pending'); // pending|verified|processing|completed|partially_retained|failed|cancelled
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->text('retention_reason')->nullable();
            $table->json('deletion_summary')->nullable(); // counts per category (anonymized, deleted, retained)
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['status', 'requested_at'], 'idx_adr_status_requested');
            $table->index(['user_id', 'status'], 'idx_adr_user_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_deletion_requests');
    }
}
