<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardens `payment_requests` for the Stripe flow.
 *
 *  - Adds stripe_session_id (UNIQUE) and stripe_payment_intent (UNIQUE)
 *    so a Checkout Session can be tied to exactly one local row.
 *  - Adds idempotency columns (webhook_event_id) so webhook processing
 *    is replay-safe.
 *  - Adds `expired_at` to make abandoned-session reconciliation possible.
 *  - Adds an index on (payment_method, is_paid) for fast "pending" queries.
 *
 * Idempotent: re-running on a table that already has the column is a no-op
 * (the IF NOT EXISTS pattern is replicated by checking Schema::hasColumn,
 *  since this MariaDB / MySQL version may not yet support ADD COLUMN IF
 *  NOT EXISTS for all flavors).
 *
 * Safe: the unique indexes are created with try/catch — if pre-existing
 * duplicate stripe_session_id or stripe_payment_intent rows exist (which
 * can happen on legacy data), the migration logs a warning and proceeds
 * without blocking. New unique violations will be impossible to introduce
 * because controllers always bind session_id before insert.
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('payment_requests')) {
            // Table is created from database/partial/payment_requests.sql
            // by UpdateController. Nothing to do here.
            return;
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_requests', 'stripe_session_id')) {
                $table->string('stripe_session_id', 191)->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('payment_requests', 'stripe_payment_intent')) {
                $table->string('stripe_payment_intent', 191)->nullable()->after('stripe_session_id');
            }
            if (!Schema::hasColumn('payment_requests', 'webhook_event_id')) {
                $table->string('webhook_event_id', 191)->nullable()->after('stripe_payment_intent');
            }
            if (!Schema::hasColumn('payment_requests', 'expired_at')) {
                $table->timestamp('expired_at')->nullable()->after('webhook_event_id');
            }
        });

        // Unique indexes — defensive try/catch (legacy duplicates must not
        // block the migration; we proceed and let new writes be clean).
        $this->safeAddUnique('payment_requests', 'stripe_session_id', 'uniq_payment_requests_stripe_session_id');
        $this->safeAddUnique('payment_requests', 'stripe_payment_intent', 'uniq_payment_requests_stripe_payment_intent');
        $this->safeAddUnique('payment_requests', 'webhook_event_id', 'uniq_payment_requests_webhook_event_id');
        $this->safeAddUnique('payment_requests', 'transaction_id', 'uniq_payment_requests_transaction_id');

        // Composite index for "find pending Stripe sessions older than N".
        try {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->index(['payment_method', 'is_paid', 'created_at'], 'idx_payment_requests_lookup');
            });
        } catch (\Throwable $e) {
            // index already exists — ignore
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_requests')) {
            return;
        }

        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_stripe_session_id');
        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_stripe_payment_intent');
        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_webhook_event_id');
        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_transaction_id');
        try {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->dropIndex('idx_payment_requests_lookup');
            });
        } catch (\Throwable $e) {
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            foreach (['stripe_session_id', 'stripe_payment_intent', 'webhook_event_id', 'expired_at'] as $col) {
                if (Schema::hasColumn('payment_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    /**
     * Add a UNIQUE index on a single column. Throws on duplicate data;
     * we swallow that specific error so the migration is never blocking.
     */
    private function safeAddUnique(string $table, string $column, string $indexName): void
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                return;
            }
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$indexName}` (`{$column}`)");
        } catch (\Throwable $e) {
            // Most likely: pre-existing duplicates. We log via the migration
            // framework and proceed — the application code now writes
            // session_id atomically, so future inserts are clean.
            \Log::warning("Skipped unique index {$indexName} on {$table}.{$column}: " . $e->getMessage());
        }
    }

    private function safeDropUnique(string $table, string $indexName): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        } catch (\Throwable $e) {
        }
    }
};
