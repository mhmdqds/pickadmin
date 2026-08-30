<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteAccountJob;
use App\Models\AccountDeletionLog;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin: Account Deletion monitoring & retry.
 *
 * Routes:
 *   GET    /admin/account-deletion                  — list with filter
 *   GET    /admin/account-deletion/{id}             — details + audit log
 *   POST   /admin/account-deletion/{id}/retry       — reset to verified + re-dispatch job
 *   POST   /admin/account-deletion/{id}/cancel      — cancel a non-terminal request
 */
class AccountDeletionRequestController extends Controller
{
    public function __construct()
    {
        // Routed inside the existing admin middleware group in routes/admin/routes.php
        // (admin + current-module + actch:admin_panel + admin.module:account_deletion).
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $query = AccountDeletionRequest::query()->latest('id');

        if ($status && in_array($status, [
            AccountDeletionRequest::STATUS_PENDING,
            AccountDeletionRequest::STATUS_VERIFIED,
            AccountDeletionRequest::STATUS_PROCESSING,
            AccountDeletionRequest::STATUS_COMPLETED,
            AccountDeletionRequest::STATUS_PARTIALLY_RETAINED,
            AccountDeletionRequest::STATUS_FAILED,
            AccountDeletionRequest::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(config('default_pagination', 25));

        $counts = [
            'all'                 => AccountDeletionRequest::count(),
            'pending'             => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_PENDING)->count(),
            'verified'            => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_VERIFIED)->count(),
            'processing'          => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_PROCESSING)->count(),
            'completed'           => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_COMPLETED)->count(),
            'partially_retained'  => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_PARTIALLY_RETAINED)->count(),
            'failed'              => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_FAILED)->count(),
            'cancelled'           => AccountDeletionRequest::where('status', AccountDeletionRequest::STATUS_CANCELLED)->count(),
        ];

        return view('admin-views.account-deletion.index', compact('requests', 'counts', 'status'));
    }

    public function show(AccountDeletionRequest $deletion)
    {
        $logs = AccountDeletionLog::where('deletion_request_id', $deletion->id)
            ->orderBy('id')
            ->get();

        return view('admin-views.account-deletion.show', compact('deletion', 'logs'));
    }

    /**
     * Retry a failed request: reset to 'verified' + re-dispatch the job.
     * Idempotent: only acts on failed/partially_retained requests.
     */
    public function retry(AccountDeletionRequest $deletion)
    {
        if (!in_array($deletion->status, [
            AccountDeletionRequest::STATUS_FAILED,
            AccountDeletionRequest::STATUS_PARTIALLY_RETAINED,
        ], true)) {
            abort(403, 'Only failed or partially-retained requests can be retried.');
        }

        $admin = Auth::guard('admin')->user();

        $deletion->status          = AccountDeletionRequest::STATUS_VERIFIED;
        $deletion->verified_at      = now();
        $deletion->processing_started_at = null;
        $deletion->completed_at     = null;
        $deletion->failed_at        = null;
        $deletion->failure_reason   = null;
        $deletion->save();

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'admin_retry',
            'actor_type'          => 'admin',
            'actor_id'            => $admin?->id,
            'status'              => $deletion->status,
            'message'             => 'Admin requested retry. Job re-dispatched.',
            'metadata'            => ['admin_id' => $admin?->id],
            'created_at'          => now(),
        ]);

        DeleteAccountJob::dispatch($deletion->id);

        return back()->with('success', translate('messages.retry_dispatched') ?? 'Retry has been dispatched.');
    }

    public function cancel(AccountDeletionRequest $deletion)
    {
        if ($deletion->isTerminal() || $deletion->isInFlight()) {
            abort(403, 'Only pending requests can be cancelled.');
        }

        $admin = Auth::guard('admin')->user();

        $deletion->status = AccountDeletionRequest::STATUS_CANCELLED;
        $deletion->save();

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'admin_cancel',
            'actor_type'          => 'admin',
            'actor_id'            => $admin?->id,
            'status'              => $deletion->status,
            'message'             => 'Admin cancelled the deletion request.',
            'metadata'            => ['admin_id' => $admin?->id],
            'created_at'          => now(),
        ]);

        return back()->with('success', translate('messages.cancelled') ?? 'Request cancelled.');
    }
}
