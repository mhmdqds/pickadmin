<?php

namespace App\Jobs;

use App\Models\AccountDeletionLog;
use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DeleteAccountJob
 * =============================================================================
 * Queueable worker that runs the full AccountDeletionService::execute() pipeline
 * OUT of the HTTP request. The controller only validates and dispatches.
 *
 * Properties:
 *   - Idempotent: handled inside the service (terminal state guard).
 *   - Retry-safe:  serialised payload carries only the request ID, no PII.
 *   - Failure-safe: exceptions update the request to 'failed' and re-throw so
 *                    the queue worker can apply its retry policy.
 */
class DeleteAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Queue name — separate queue lets ops scale deletion independently. */
    public string $queue = 'account-deletion';

    /** Retry up to 3 times with exponential backoff before final failure. */
    public int $tries = 3;

    /** Each attempt may take up to 5 minutes (large data sets). */
    public int $timeout = 300;

    public function __construct(public int $deletionRequestId)
    {
    }

    /**
     * Backoff in seconds between retries.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function handle(AccountDeletionService $service): void
    {
        $request = AccountDeletionRequest::find($this->deletionRequestId);
        if (!$request) {
            Log::warning('DeleteAccountJob: request not found', [
                'request_id' => $this->deletionRequestId,
            ]);
            return;
        }

        try {
            $service->execute($request);
        } catch (\Throwable $e) {
            // Log to audit trail first, then bubble up for queue retry.
            try {
                AccountDeletionLog::create([
                    'deletion_request_id' => $request->id,
                    'action'              => 'job_failed',
                    'actor_type'          => 'system',
                    'actor_id'            => null,
                    'status'              => $request->status,
                    'target_table'        => null,
                    'affected_rows'       => null,
                    'message'             => $e->getMessage(),
                    'metadata'            => ['attempt' => $this->attempts()],
                    'created_at'          => now(),
                ]);
            } catch (\Throwable $auditError) {
                // never let audit logging mask the real failure
            }

            throw $e;
        }
    }

    /**
     * Final failure handler — invoked once all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $request = AccountDeletionRequest::find($this->deletionRequestId);
        if (!$request) {
            return;
        }
        $request->status         = AccountDeletionRequest::STATUS_FAILED;
        $request->failed_at      = now();
        $request->failure_reason = 'Job failed after retries: ' . \Illuminate\Support\Str::limit($exception->getMessage(), 800, '...');
        $request->save();

        try {
            AccountDeletionLog::create([
                'deletion_request_id' => $request->id,
                'action'              => 'job_retries_exhausted',
                'actor_type'          => 'system',
                'actor_id'            => null,
                'status'              => $request->status,
                'target_table'        => null,
                'affected_rows'       => null,
                'message'             => 'All queue retries exhausted.',
                'metadata'            => null,
                'created_at'          => now(),
            ]);
        } catch (\Throwable $t) {
            // ignore
        }
    }
}
