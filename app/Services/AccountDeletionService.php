<?php

namespace App\Services;

use App\Models\AccountDeletionLog;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * AccountDeletionService — central, idempotent deletion pipeline.
 *
 * All entry points (Flutter API, public Web page, admin retry) MUST go through
 * this class. Do NOT duplicate the deletion logic anywhere else.
 */
class AccountDeletionService
{
    private array $summary = [
        'deleted'    => [],
        'anonymized' => [],
        'retained'   => [],
    ];

    public function execute(AccountDeletionRequest $request): bool
    {
        if ($request->isTerminal() && $request->status !== AccountDeletionRequest::STATUS_FAILED) {
            $this->audit($request, 'already_completed', 'system', null, 'Skipped — request already terminal.');
            return false;
        }

        $user = $this->resolveUser($request);
        if (!$user) {
            $this->markFailed($request, 'User record not found and could not be resolved from the request.');
            return false;
        }

        DB::beginTransaction();
        try {
            $user = User::where('id', $user->id)->lockForUpdate()->first();
            if (!$user) {
                throw new \RuntimeException('User vanished between resolve and lock.');
            }
            $request->status                = AccountDeletionRequest::STATUS_PROCESSING;
            $request->processing_started_at = now();
            $request->save();
            $this->audit($request, 'processing_started', 'system', null, 'Pipeline started.', ['user_id' => $user->id]);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->markFailed($request, 'Could not start processing: ' . $e->getMessage());
            return false;
        }

        // Phase 1 — HARD DELETE personal data
        try {
            $this->runPhase1($request, $user);
        } catch (\Throwable $e) {
            $this->markFailed($request, 'Phase 1 (personal data) failed: ' . $e->getMessage());
            return false;
        }

        // Phase 2 — ANONYMISE financial records
        try {
            $this->runPhase2($request, $user);
        } catch (\Throwable $e) {
            $this->markFailed($request, 'Phase 2 (anonymisation) failed: ' . $e->getMessage());
            return false;
        }

        // Phase 3 — User record
        try {
            $this->anonymizeAndDeleteUser($request, $user);
        } catch (\Throwable $e) {
            $this->markFailed($request, 'Phase 3 (user record) failed: ' . $e->getMessage());
            return false;
        }

        $hadRetained = collect($this->summary['retained'])->flatten()->isNotEmpty();

        DB::beginTransaction();
        try {
            $request->status          = $hadRetained
                ? AccountDeletionRequest::STATUS_PARTIALLY_RETAINED
                : AccountDeletionRequest::STATUS_COMPLETED;
            $request->completed_at    = now();
            $request->deletion_summary= $this->summary;
            if ($hadRetained) {
                $request->retention_reason = $this->buildRetentionReason();
            }
            $request->save();

            $this->audit(
                $request,
                'completed',
                'system',
                null,
                $hadRetained
                    ? 'Deletion completed with partial retention (see summary).'
                    : 'Deletion completed.',
                ['summary' => $this->summary]
            );
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->markFailed($request, 'Finalisation failed: ' . $e->getMessage());
            return false;
        }

        return true;
    }

    private function resolveUser(AccountDeletionRequest $request): ?User
    {
        return $request->user_id ? User::find($request->user_id) : null;
    }

    /**
     * Phase 1 — personal data removal.
     */
    private function runPhase1(AccountDeletionRequest $request, User $user): void
    {
        // OAuth tokens
        $tokenCount = DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
        DB::table('oauth_refresh_tokens')
            ->whereIn('access_token_id', function ($q) use ($user) {
                $q->select('id')->from('oauth_access_tokens')->where('user_id', $user->id);
            })->delete();
        if ($tokenCount > 0) {
            $this->recordDeleted($request, 'oauth_tokens', $tokenCount, 'OAuth tokens revoked.');
        }

        // Customer addresses
        $count = DB::table('customer_addresses')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'customer_addresses', $count, 'Customer addresses deleted.');
        }

        // user_infos
        $count = DB::table('user_infos')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'user_infos', $count, 'User info records deleted.');
        }

        // user_notifications
        $count = DB::table('user_notifications')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'user_notifications', $count, 'Notifications deleted.');
        }

        // user_files personal types
        $files = DB::table('user_files')
            ->where('user_id', $user->id)
            ->whereIn('type', ['profile', 'identity', 'other'])
            ->get();
        foreach ($files as $f) {
            $this->deleteStorageFile('order/saved_files', $f->file_name ?? null, $f->storage ?? 'public');
        }
        $count = DB::table('user_files')
            ->where('user_id', $user->id)
            ->whereIn('type', ['profile', 'identity', 'other'])
            ->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'user_files_personal', $count, 'Personal user files deleted.');
        }

        // wishlists
        $count = DB::table('wishlists')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'wishlists', $count, 'Wishlists deleted.');
        }
        if (Schema::hasTable('wishlist_items')) {
            $count = DB::table('wishlist_items')->where('user_id', $user->id)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'wishlist_items', $count, 'Wishlist items deleted.');
            }
        }

        // carts
        $count = DB::table('carts')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'carts', $count, 'Cart items deleted.');
        }

        // reviews
        $count = DB::table('reviews')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'reviews', $count, 'Reviews deleted.');
        }

        // conversations + messages
        $count = DB::table('conversations')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'conversations', $count, 'Conversations deleted.');
        }
        $count = DB::table('messages')
            ->where('sender_id', $user->id)
            ->where('sender_type', 'customer')
            ->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'messages', $count, 'Customer messages deleted.');
        }

        // password_resets
        $email = $user->getRawOriginal('email');
        if ($email) {
            $count = DB::table('password_resets')->where('email', $email)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'password_resets', $count, 'Password reset tokens deleted.');
            }
        }

        // email_verifications
        if ($email) {
            $count = DB::table('email_verifications')->where('email', $email)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'email_verifications', $count, 'Email verifications deleted.');
            }
        }

        // phone_verifications
        $phone = $user->getRawOriginal('phone');
        if ($phone) {
            $count = DB::table('phone_verifications')->where('phone', $phone)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'phone_verifications', $count, 'Phone verifications deleted.');
            }
        }

        // newsletter
        if ($email) {
            $count = DB::table('newsletters')->where('email', $email)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'newsletters', $count, 'Newsletter subscriptions removed.');
            }
        }

        // user_last_locations
        $count = DB::table('user_last_locations')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'user_last_locations', $count, 'Last locations deleted.');
        }

        // rental carts
        if (Schema::hasTable('rental_carts')) {
            $count = DB::table('rental_carts')->where('customer_id', $user->id)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'rental_carts', $count, 'Rental carts deleted.');
            }
        }

        // account_transactions
        $count = DB::table('account_transactions')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'account_transactions', $count, 'Account transactions deleted.');
        }

        // wallet_transactions
        $count = DB::table('wallet_transactions')->where('user_id', $user->id)->delete();
        if ($count > 0) {
            $this->recordDeleted($request, 'wallet_transactions', $count, 'Wallet transactions deleted.');
        }

        // social_accounts
        if (Schema::hasTable('social_accounts')) {
            $count = DB::table('social_accounts')->where('user_id', $user->id)->delete();
            if ($count > 0) {
                $this->recordDeleted($request, 'social_accounts', $count, 'Social accounts deleted.');
            }
        }
    }

    /**
     * Phase 2 — anonymise financial records (orders, payments, refunds).
     */
    private function runPhase2(AccountDeletionRequest $request, User $user): void
    {
        $orders = DB::table('orders')
            ->where('user_id', $user->id)
            ->where('is_guest', 0)
            ->get();

        if ($orders->isNotEmpty()) {
            foreach ($orders as $row) {
                if (!empty($row->delivery_address)) {
                    $decoded = json_decode($row->delivery_address, true);
                    if (is_array($decoded)) {
                        $decoded['contact_person_name']   = 'Deleted User';
                        $decoded['contact_person_number'] = '+0000000000';
                        $decoded['address']               = '[redacted]';
                        $decoded['email']                 = null;
                        $decoded['road']                  = '';
                        $decoded['house']                 = '';
                        $decoded['floor']                 = '';
                        $decoded['longitude']             = null;
                        $decoded['latitude']              = null;
                        DB::table('orders')->where('id', $row->id)
                            ->update(['delivery_address' => json_encode($decoded)]);
                    }
                }
            }

            $count = DB::table('orders')
                ->where('user_id', $user->id)
                ->where('is_guest', 0)
                ->update(['user_id' => null, 'delivery_address_id' => null, 'updated_at' => now()]);

            if ($count > 0) {
                $this->summary['anonymized']['orders'] = $count;
                $this->summary['retained']['orders']   = $count;
                $this->audit(
                    $request,
                    'anonymized',
                    'system',
                    null,
                    'Orders anonymised — PII wiped, financial figures retained.',
                    ['count' => $count],
                    'orders'
                );
            }
        }

        // order_payments
        $count = DB::table('order_payments')->where('user_id', $user->id)->count();
        if ($count > 0) {
            DB::table('order_payments')->where('user_id', $user->id)
                ->update(['user_id' => null, 'updated_at' => now()]);
            $this->summary['anonymized']['order_payments'] = $count;
            $this->summary['retained']['order_payments']   = $count;
            $this->audit(
                $request,
                'anonymized',
                'system',
                null,
                'Order payments anonymised (gateway refs retained).',
                ['count' => $count],
                'order_payments'
            );
        }

        // refunds
        $count = DB::table('refunds')->where('user_id', $user->id)
            ->update([
                'user_id'         => null,
                'image'           => null,
                'customer_reason' => '[account deleted]',
                'customer_note'   => null,
                'updated_at'      => now(),
            ]);
        if ($count > 0) {
            $this->summary['anonymized']['refunds'] = $count;
            $this->summary['retained']['refunds']   = $count;
            $this->audit($request, 'anonymized', 'system', null, 'Refunds anonymised.', ['count' => $count], 'refunds');
        }

        // order_delivery_histories
        if (Schema::hasTable('order_delivery_histories')) {
            $count = DB::table('order_delivery_histories')->where('user_id', $user->id)
                ->update(['user_id' => null, 'updated_at' => now()]);
            if ($count > 0) {
                $this->summary['anonymized']['order_delivery_histories'] = $count;
                $this->audit($request, 'anonymized', 'system', null, 'Order delivery histories anonymised.', ['count' => $count], 'order_delivery_histories');
            }
        }

        // user_files order attachments — tombstone
        $count = DB::table('user_files')
            ->where('user_id', $user->id)
            ->where('type', 'order')
            ->update(['user_id' => null, 'file_name' => '[deleted]', 'updated_at' => now()]);
        if ($count > 0) {
            $this->summary['anonymized']['user_files_order'] = $count;
            $this->audit($request, 'anonymized', 'system', null, 'Order attachments anonymised.', ['count' => $count], 'user_files');
        }
    }

    /**
     * Phase 3 — anonymise then hard-delete the User row.
     */
    private function anonymizeAndDeleteUser(AccountDeletionRequest $request, User $user): void
    {
        DB::table('storages')
            ->where('data_type', User::class)
            ->where('data_id', $user->id)
            ->delete();

        if (!empty($user->getRawOriginal('image'))) {
            $this->deleteStorageFile('profile', $user->getRawOriginal('image'), 'public');
        }

        $tombstoneEmail = 'deleted-' . Str::lower(Str::random(12)) . '@deleted.invalid';

        $user->f_name            = 'Deleted';
        $user->l_name            = 'User';
        $user->email             = $tombstoneEmail;
        $user->phone             = '+0000000000';
        $user->password          = null;
        $user->remember_token    = null;
        $user->cm_firebase_token = null;
        $user->image             = null;
        $user->login_medium      = null;
        $user->ref_code          = null;
        $user->ref_by            = null;
        $user->interest          = null;
        $user->social_id         = null;
        $user->status            = 0;
        $user->is_phone_verified = 0;
        $user->email_verified_at = null;
        $user->wallet_balance    = 0;
        $user->loyalty_point     = 0;
        $user->order_count       = 0;
        $user->save();

        $this->audit($request, 'anonymized', 'system', null, 'User row anonymised.', null, 'users');

        // Hard-delete — soft-deletes are NOT acceptable for account deletion.
        $user->delete();

        $this->summary['deleted']['user_record'] = 1;
        $this->audit($request, 'deleted', 'system', null, 'User record hard-deleted.', ['count' => 1], 'users');
    }

    /**
     * Track a deletion count and emit an audit row.
     */
    private function recordDeleted(AccountDeletionRequest $request, string $label, int $count, string $message): void
    {
        $this->summary['deleted'][$label] = $count;
        $this->audit($request, 'deleted', 'system', null, $message, ['count' => $count], $label);
    }

    /**
     * Best-effort file removal from any configured disk.
     */
    private function deleteStorageFile(string $folder, ?string $filename, string $disk): void
    {
        if (empty($filename)) {
            return;
        }
        try {
            if (Storage::disk($disk)->exists($folder . '/' . $filename)) {
                Storage::disk($disk)->delete($folder . '/' . $filename);
            }
        } catch (\Throwable $e) {
            Log::warning('account_deletion: file delete failed', [
                'folder'   => $folder,
                'filename' => $filename,
                'disk'     => $disk,
            ]);
        }
    }

    /**
     * Append an audit-log row. NEVER include raw secrets.
     */
    private function audit(
        AccountDeletionRequest $request,
        string $action,
        ?string $actorType,
        ?int $actorId,
        ?string $message = null,
        ?array $metadata = null,
        ?string $targetTable = null
    ): void {
        try {
            AccountDeletionLog::create([
                'deletion_request_id' => $request->id,
                'action'              => $action,
                'actor_type'          => $actorType,
                'actor_id'            => $actorId,
                'status'              => $request->status,
                'target_table'        => $targetTable,
                'affected_rows'       => $metadata['count'] ?? null,
                'message'             => $message,
                'metadata'            => $metadata,
                'created_at'          => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('account_deletion: audit log write failed', [
                'request_id' => $request->id,
                'action'     => $action,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark the request as failed. Used by all phase handlers.
     */
    private function markFailed(AccountDeletionRequest $request, string $reason): void
    {
        try {
            $request->status         = AccountDeletionRequest::STATUS_FAILED;
            $request->failed_at      = now();
            $request->failure_reason = Str::limit($reason, 1000, '...');
            $request->save();
            $this->audit($request, 'failed', 'system', null, $reason);
        } catch (\Throwable $e) {
            Log::error('account_deletion: markFailed itself failed', [
                'request_id' => $request->id,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build a human-readable retention summary.
     */
    private function buildRetentionReason(): string
    {
        $lines = [];
        foreach ($this->summary['retained'] as $table => $count) {
            $lines[] = $table . ': ' . $count . ' row(s) retained for legal/financial compliance.';
        }
        return implode("\n", $lines);
    }
}


