<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMessageCentralColumnsToPhoneVerificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the columns needed to glue phone_verifications to Message Central
     * VerifyNow-style verification:
     *
     *   - verification_id : opaque id returned by Message Central on send
     *   - transaction_id  : MC transactionId (audit only)
     *   - reference_id    : MC referenceId (audit only)
     *   - flow_type       : MC flowType (SMS / WHATSAPP / ...)
     *   - verified_at     : timestamp of the successful provider verify
     *   - is_verified     : boolean flag mirroring verified_at for fast lookup
     *
     * Note: the phone itself is already stored in the existing `phone` column;
     * we do NOT add a duplicate `mobile_number` column.
     *
     * All new columns are NULLable so the legacy Message-Now flow keeps
     * working without any data migration.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('phone_verifications', function (Blueprint $table) {
            $table->string('verification_id', 128)->nullable()->after('token');
            $table->string('transaction_id', 128)->nullable()->after('verification_id');
            $table->string('reference_id', 128)->nullable()->after('transaction_id');
            $table->string('flow_type', 16)->nullable()->after('reference_id');
            $table->timestamp('verified_at')->nullable()->after('flow_type');
            $table->boolean('is_verified')->default(0)->after('verified_at');

            // Composite index for "find latest pending verification by phone".
            $table->index(['phone', 'verified_at'], 'idx_phone_verifications_phone_verified');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('phone_verifications', function (Blueprint $table) {
            $table->dropIndex('idx_phone_verifications_phone_verified');
            $table->dropColumn([
                'verification_id',
                'transaction_id',
                'reference_id',
                'flow_type',
                'verified_at',
                'is_verified',
            ]);
        });
    }
}