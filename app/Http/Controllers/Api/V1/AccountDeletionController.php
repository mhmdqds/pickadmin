<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteAccountJob;
use App\Models\AccountDeletionLog;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * AccountDeletionController (API for Flutter)
 *
 * Endpoints:
 *   POST  /api/v1/customer/account-deletion/request
 *   POST  /api/v1/customer/account-deletion/verify
 *   POST  /api/v1/customer/account-deletion/confirm
 *   GET   /api/v1/customer/account-deletion/status
 */
class AccountDeletionController extends Controller
{
    private function throttleKey(Request $request, string $suffix): string
    {
        $userId = Auth::id() ?? 'ip:' . $request->ip();
        return 'account-deletion:' . $userId . ':' . $suffix;
    }

    private function publicView(AccountDeletionRequest $d): array
    {
        return [
            'request_uuid'         => $d->request_uuid,
            'verification_method'  => $d->verification_method,
            'channel'              => $d->channel,
            'status'               => $d->status,
            'requested_at'         => optional($d->requested_at)->toIso8601String(),
            'verified_at'          => optional($d->verified_at)->toIso8601String(),
            'processing_started_at'=> optional($d->processing_started_at)->toIso8601String(),
            'completed_at'         => optional($d->completed_at)->toIso8601String(),
            'failed_at'            => optional($d->failed_at)->toIso8601String(),
        ];
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'errors' => [['code' => 'unauthenticated', 'message' => translate('messages.unauthenticated')]],
        ], 401);
    }

    private function rateLimited(): JsonResponse
    {
        return response()->json([
            'errors' => [['code' => 'rate_limited', 'message' => translate('messages.too_many_attempts')]],
        ], 429);
    }

    /**
     * POST /api/v1/customer/account-deletion/request
     */
    public function request(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_method' => 'required|in:email_password,phone_otp',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => [['code' => 'invalid_method', 'message' => $validator->errors()->first()]],
            ], 403);
        }

        $user = Auth::user();
        if (!$user) {
            return $this->unauthenticated();
        }

        if (RateLimiter::tooManyAttempts($this->throttleKey($request, 'request'), 5)) {
            return $this->rateLimited();
        }
        RateLimiter::hit($this->throttleKey($request, 'request'), 60);

        $method = $request->input('verification_method');

        $existing = AccountDeletionRequest::where('user_id', $user->id)
            ->whereIn('status', [
                AccountDeletionRequest::STATUS_PENDING,
                AccountDeletionRequest::STATUS_VERIFIED,
                AccountDeletionRequest::STATUS_PROCESSING,
            ])
            ->orderByDesc('id')
            ->first();

        if ($existing) {
            return response()->json([
                'response_code' => 'deletion_request_exists_200',
                'message'       => 'An existing deletion request is in progress.',
                'data'          => $this->publicView($existing),
            ], 200);
        }

        $deletion = AccountDeletionRequest::create([
            'user_id'             => $user->id,
            'request_uuid'        => (string) Str::uuid(),
            'verification_method' => $method,
            'channel'             => AccountDeletionRequest::CHANNEL_API,
            'status'              => AccountDeletionRequest::STATUS_PENDING,
            'requested_at'        => now(),
            'ip_address'          => $request->ip(),
            'user_agent'          => substr((string) $request->userAgent(), 0, 512),
        ]);

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'request_created',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'target_table'        => null,
            'affected_rows'       => null,
            'message'             => 'Deletion request created via API.',
            'metadata'            => ['method' => $method, 'channel' => 'api'],
            'created_at'          => now(),
        ]);

        $otpHint = null;
        if ($method === AccountDeletionRequest::METHOD_PHONE_OTP) {
            $otpHint = $this->sendDeletionOtp($user, $deletion);
        }

        return response()->json([
            'response_code' => 'deletion_request_created_200',
            'message'       => translate('messages.deletion_request_created') ?? 'Deletion request created.',
            'data'          => array_merge($this->publicView($deletion), [
                'otp_sent' => $otpHint,
            ]),
        ], 200);
    }

    /**
     * POST /api/v1/customer/account-deletion/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'verification_method' => 'required|in:email_password,phone_otp',
            'password'            => 'required_if:verification_method,email_password|string|min:1|max:255',
            'otp'                 => 'required_if:verification_method,phone_otp|string|min:4|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => $validator->errors()->first()]],
            ], 403);
        }

        if (RateLimiter::tooManyAttempts($this->throttleKey($request, 'verify'), 10)) {
            return $this->rateLimited();
        }
        RateLimiter::hit($this->throttleKey($request, 'verify'), 60);

        $user = Auth::user();
        if (!$user) {
            return $this->unauthenticated();
        }

        $method = $request->input('verification_method');

        $deletion = AccountDeletionRequest::where('user_id', $user->id)
            ->where('verification_method', $method)
            ->where('status', AccountDeletionRequest::STATUS_PENDING)
            ->orderByDesc('id')
            ->first();

        if (!$deletion) {
            return response()->json([
                'errors' => [['code' => 'no_pending_request', 'message' => 'No pending deletion request found.']],
            ], 404);
        }

        $ok = false;
        if ($method === AccountDeletionRequest::METHOD_EMAIL_PASSWORD) {
            $storedHash = $user->getRawOriginal('password');
            $ok = $storedHash && Hash::check($request->input('password'), $storedHash);
        } else {
            $ok = $this->verifyDeletionOtp($user, $deletion, $request->input('otp'));
        }

        if (!$ok) {
            AccountDeletionLog::create([
                'deletion_request_id' => $deletion->id,
                'action'              => 'verification_failed',
                'actor_type'          => 'user',
                'actor_id'            => $user->id,
                'status'              => $deletion->status,
                'target_table'        => null,
                'affected_rows'       => null,
                'message'             => 'Verification attempt failed.',
                'metadata'            => ['method' => $method],
                'created_at'          => now(),
            ]);
            return response()->json([
                'errors' => [['code' => 'verification_failed', 'message' => 'Verification failed. Please check your credentials and try again.']],
            ], 401);
        }

        $deletion->status      = AccountDeletionRequest::STATUS_VERIFIED;
        $deletion->verified_at = now();
        $deletion->save();

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'verification_passed',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'target_table'        => null,
            'affected_rows'       => null,
            'message'             => 'Verification passed.',
            'metadata'            => ['method' => $method],
            'created_at'          => now(),
        ]);

        return response()->json([
            'response_code' => 'deletion_verified_200',
            'message'       => 'Identity verified. Please confirm deletion.',
            'data'          => $this->publicView($deletion),
        ], 200);
    }

    /**
     * POST /api/v1/customer/account-deletion/confirm
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'request_uuid' => 'required|uuid',
            'confirm'      => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => $validator->errors()->first()]],
            ], 403);
        }

        if (!$request->boolean('confirm')) {
            return response()->json([
                'errors' => [['code' => 'not_confirmed', 'message' => 'Confirmation flag not set.']],
            ], 403);
        }

        $user = Auth::user();
        if (!$user) {
            return $this->unauthenticated();
        }

        $deletion = AccountDeletionRequest::where('user_id', $user->id)
            ->where('request_uuid', $request->input('request_uuid'))
            ->first();

        if (!$deletion) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Deletion request not found.']],
            ], 404);
        }

        if ($deletion->status !== AccountDeletionRequest::STATUS_VERIFIED) {
            return response()->json([
                'errors' => [['code' => 'not_verified', 'message' => 'Deletion request must be verified before confirmation.']],
            ], 403);
        }

        \App\Jobs\DeleteAccountJob::dispatch($deletion->id);

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'confirm_received',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'target_table'        => null,
            'affected_rows'       => null,
            'message'             => 'Deletion confirmed by user. Job dispatched.',
            'metadata'            => null,
            'created_at'          => now(),
        ]);

        return response()->json([
            'response_code' => 'deletion_confirmed_200',
            'message'       => 'Account deletion is being processed.',
            'data'          => $this->publicView($deletion),
        ], 200);
    }

    /**
     * GET /api/v1/customer/account-deletion/status
     */
    public function status(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'request_uuid' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => $validator->errors()->first()]],
            ], 403);
        }

        $user = Auth::user();
        if (!$user) {
            return $this->unauthenticated();
        }

        $deletion = AccountDeletionRequest::where('user_id', $user->id)
            ->where('request_uuid', $request->input('request_uuid'))
            ->first();

        if (!$deletion) {
            return response()->json([
                'errors' => [['code' => 'not_found', 'message' => 'Deletion request not found.']],
            ], 404);
        }

        return response()->json([
            'response_code' => 'deletion_status_200',
            'message'       => 'OK',
            'data'          => $this->publicView($deletion),
        ], 200);
    }

    // ========================================================================
    // OTP helpers — use the existing phone_verifications table and SmsGateway
    // trait so we share infrastructure with login.
    // ========================================================================

    /**
     * Send a 6-digit OTP to the user's phone. Uses SmsGateway::send() (or the
     * Message Central VerifyNow path when active) and writes the row to
     * phone_verifications.
     */
    private function sendDeletionOtp(User $user, AccountDeletionRequest $deletion): array
    {
        $phone = $user->getRawOriginal('phone');
        if (empty($phone)) {
            return ['sent' => false, 'reason' => 'no_phone'];
        }

        if (RateLimiter::tooManyAttempts($this->throttleKey(request(), 'otp_send'), 3)) {
            return ['sent' => false, 'reason' => 'rate_limited'];
        }
        RateLimiter::hit($this->throttleKey(request(), 'otp_send'), 60);

        try {
            $otp = rand(100000, 999999);
            if (function_exists('getEnvMode') && getEnvMode() === 'demo') {
                $otp = '123456';
            }

            // Send via the existing provider chain.
            $response = \App\Traits\SmsGateway::send($phone, $otp);
            $sent = ($response === 'success');

            // Persist to phone_verifications (same table as login uses).
            $normalized = preg_replace('/[^0-9+]/', '', $phone);
            if ($normalized !== '' && strpos($normalized, '+') !== 0) {
                $normalized = '+' . $normalized;
            }

            if ($sent) {
                \DB::table('phone_verifications')->updateOrInsert(
                    ['phone' => $normalized],
                    [
                        'token'         => $otp,
                        'otp_hit_count' => 0,
                        'is_verified'   => 0,
                        'verified_at'   => null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]
                );
            }

            return ['sent' => $sent];
        } catch (\Throwable $e) {
            Log::warning('account_deletion: sendDeletionOtp failed', [
                'user_id'    => $user->id,
                'request_id' => $deletion->id,
                'error'      => $e->getMessage(),
            ]);
            return ['sent' => false, 'reason' => 'provider_error'];
        }
    }

    /**
     * Verify a 6-digit OTP against the phone_verifications row.
     * The row is invalidated on success (is_verified=1, then deleted).
     */
    private function verifyDeletionOtp(User $user, AccountDeletionRequest $deletion, string $otp): bool
    {
        $phone = $user->getRawOriginal('phone');
        if (empty($phone) || empty($otp)) {
            return false;
        }

        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        if ($normalized !== '' && strpos($normalized, '+') !== 0) {
            $normalized = '+' . $normalized;
        }

        $row = \DB::table('phone_verifications')->where('phone', $normalized)->first();
        if (!$row) {
            return false;
        }

        // Already used?
        if ((int) ($row->is_verified ?? 0) === 1) {
            return false;
        }

        if (!hash_equals((string) $row->token, (string) $otp)) {
            // increment hit count but cap attempts.
            \DB::table('phone_verifications')
                ->where('phone', $normalized)
                ->update([
                    'otp_hit_count' => \DB::raw('otp_hit_count + 1'),
                    'updated_at'    => now(),
                ]);
            return false;
        }

        // Mark verified then immediately invalidate (one-shot).
        \DB::table('phone_verifications')
            ->where('phone', $normalized)
            ->update([
                'is_verified' => 1,
                'verified_at' => now(),
                'updated_at'  => now(),
            ]);

        // Delete the row so it cannot be reused.
        \DB::table('phone_verifications')->where('phone', $normalized)->delete();

        return true;
    }
}
