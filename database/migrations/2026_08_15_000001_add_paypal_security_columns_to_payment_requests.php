<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hardens `payment_requests` for the PayPal flow.
 *
 *  - Adds paypal_order_id (UNIQUE) so a PayPal Order can be tied to
 *    exactly one local row.
 *  - Adds paypal_capture_id (UNIQUE) so a PayPal Capture can be tied
 *    to exactly one local row. This is the real PSP-side identifier
 *    we need for refund + reconciliation.
 *  - Adds captured_amount and captured_currency so we can compare
 *    what PayPal actually captured against what we expected to be
 *    paid (FIX-03 / FIX-04 / FIX-08).
 *  - Adds paypal_webhook_event_id (UNIQUE) for webhook idempotency
 *    (FIX-17).
 *  - Adds paypal_webhook_id (nullable) — the Webhook ID issued by
 *    PayPal when the webhook is registered; verified as part of
 *    the signature check (FIX-16).
 *  - Adds paypal_processed_at — a timestamp of when the local row
 *    was last locked+updated. Helps operators distinguish "captured
 *    at PayPal" from "settled at our side".
 *
 *  Mirrors the Stripe migration pattern (try/catch around unique
 *  index creation so legacy duplicates cannot block the migration).
 */
return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasTable('payment_requests')) {
            return;
        }

        Schema::table('payment_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_requests', 'paypal_order_id')) {
                $table->string('paypal_order_id', 64)->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('payment_requests', 'paypal_capture_id')) {
                $table->string('paypal_capture_id', 64)->nullable()->after('paypal_order_id');
            }
            if (!Schema::hasColumn('payment_requests', 'captured_amount')) {
                $table->decimal('captured_amount', 24, 2)->nullable()->after('paypal_capture_id');
            }
            if (!Schema::hasColumn('payment_requests', 'captured_currency')) {
                $table->string('captured_currency', 20)->nullable()->after('captured_amount');
            }
            if (!Schema::hasColumn('payment_requests', 'paypal_webhook_event_id')) {
                $table->string('paypal_webhook_event_id', 64)->nullable()->after('captured_currency');
            }
            if (!Schema::hasColumn('payment_requests', 'paypal_webhook_id')) {
                $table->string('paypal_webhook_id', 64)->nullable()->after('paypal_webhook_event_id');
            }
            if (!Schema::hasColumn('payment_requests', 'paypal_processed_at')) {
                $table->timestamp('paypal_processed_at')->nullable()->after('paypal_webhook_id');
            }
        });

        // Defensive unique indexes. Pre-existing duplicate rows (rare but
        // possible on legacy data) will not block the migration; new writes
        // are bound via the controller so future duplicates are impossible.
        $this->safeAddUnique('payment_requests', 'paypal_order_id', 'uniq_payment_requests_paypal_order_id');
        $this->safeAddUnique('payment_requests', 'paypal_capture_id', 'uniq_payment_requests_paypal_capture_id');
        $this->safeAddUnique('payment_requests', 'paypal_webhook_event_id', 'uniq_payment_requests_paypal_webhook_event_id');
    }

    public function down(): void
    {
        if (!Schema::hasTable('payment_requests')) {
            return;
        }

        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_paypal_order_id');
        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_paypal_capture_id');
        $this->safeDropUnique('payment_requests', 'uniq_payment_requests_paypal_webhook_event_id');

        Schema::table('payment_requests', function (Blueprint $table) {
            foreach (
                [
                    'paypal_order_id',
                    'paypal_capture_id',
                    'captured_amount',
                    'captured_currency',
                    'paypal_webhook_event_id',
                    'paypal_webhook_id',
                    'paypal_processed_at',
                ] as $col
            ) {
                if (Schema::hasColumn('payment_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function safeAddUnique(string $table, string $column, string $indexName): void
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            if (!empty($indexes)) {
                return;
            }
            DB::statement("ALTER TABLE `{$table}` ADD UNIQUE `{$indexName}` (`{$column}`)");
        } catch (\Throwable $e) {
            // Most likely: pre-existing duplicates. Logged via the framework
            // and not blocking — the controller now writes these values
            // atomically, so future inserts are clean.
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
