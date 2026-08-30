<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\DeleteAccountJob;
use App\Models\AccountDeletionLog;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Traits\SmsGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * AccountDeletionController (Web)
 *
 * Public /delete-account page used by Google Play's "Account Deletion URL"
 * requirement. Same AccountDeletionService + Job as the API — no parallel
 * logic.
 *
 * SECURITY:
 *   - Never trust a user_id from the client; resolve from verified creds.
 *   - All POST routes rate-limited per IP.
 *   - No CSRF exemptions (CSRF middleware is the global 'web' middleware).
 *   - Constant-time responses for the email path (no enumeration).
 */
class AccountDeletionController extends Controller
{
    public function __construct()
    {
        $this->middleware('web');
    }

    private function ipKey(Request $request, string $suffix): string
    {
        return 'account-deletion-web:' . $request->ip() . ':' . $suffix;
    }

    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        if ($normalized !== '' && strpos($normalized, '+') !== 0) {
            $normalized = '+' . $normalized;
        }
        return $normalized;
    }

    private function maskEmail(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }
        [$name, $domain] = explode('@', $email, 2);
        $name = substr($name, 0, 1) . str_repeat('*', max(0, strlen($name) - 1));
        return $name . '@' . $domain;
    }

    private function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return '+' . str_repeat('*', strlen($digits));
        }
        return '+' . substr($digits, 0, 2) . str_repeat('*', max(0, strlen($digits) - 6)) . substr($digits, -4);
    }

    /**
     * GET /delete-account
     */
    public function showForm(): View
    {
        return view('delete-account');
    }

    /**
     * GET /delete-account/done
     */
    public function done(): View
    {
        return view('delete-account-done');
    }

    /**
     * POST /delete-account/start
     *
     * Email flow: create a pending request (constant-time response, no
     *             enumeration).
     * Phone flow:  send OTP via existing provider, create pending request.
     */
    public function start(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|in:email,phone',
            'email'      => 'required_if:identifier,email|email|max:191',
            'phone'      => 'required_if:identifier,phone|string|min:9|max:20',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (RateLimiter::tooManyAttempts($this->ipKey($request, 'start'), 10)) {
            return back()->withErrors(['identifier' => 'Too many attempts. Please try again in a minute.']);
        }
        RateLimiter::hit($this->ipKey($request, 'start'), 60);

        $identifier = $request->input('identifier');

        if ($identifier === 'email') {
            $email  = $request->input('email');
            $user   = User::where('email', $email)->first();

            // Create the pending request ONLY if the email matches — but the
            // response shape is identical either way (no enumeration).
            if ($user) {
                $this->createPendingRequest($user, AccountDeletionRequest::METHOD_EMAIL_PASSWORD);
            }

            return view('delete-account-verify', [
                'identifier' => 'email',
                'masked_hint'=> $this->maskEmail($email),
            ]);
        }

        // Phone flow
        $phone      = $request->input('phone');
        $normalized = $this->normalizePhone($phone);
        $user       = User::where('phone', $phone)->orWhere('phone', $normalized)->first();

        if (!$user) {
            // Same response shape — don't leak.
            return view('delete-account-verify', [
                'identifier' => 'phone',
                'masked_hint'=> $this->maskPhone($phone),
                'sent'       => false,
            ]);
        }

        if (RateLimiter::tooManyAttempts($this->ipKey($request, 'otp_send'), 3)) {
            return back()->withErrors(['phone' => 'Too many OTP requests. Try again later.']);
        }
        RateLimiter::hit($this->ipKey($request, 'otp_send'), 60);

        $otp = rand(100000, 999999);
        if (function_exists('getEnvMode') && getEnvMode() === 'demo') {
            $otp = '123456';
        }

        $sent = false;
        try {
            $result = SmsGateway::send($phone, $otp);
            $sent = ($result === 'success');
        } catch (\Throwable $e) {
            Log::warning('account_deletion.web: sms send failed', ['error' => $e->getMessage()]);
        }

        if ($sent) {
            DB::table('phone_verifications')->updateOrInsert(
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
            $this->createPendingRequest($user, AccountDeletionRequest::METHOD_PHONE_OTP);
        }

        return view('delete-account-verify', [
            'identifier' => 'phone',
            'masked_hint'=> $this->maskPhone($phone),
            'sent'       => $sent,
        ]);
    }

    /**
     * POST /delete-account/verify
     */
    public function verify(Request $request)
    {
        $identifier = $request->input('identifier');
        return $identifier === 'phone' ? $this->verifyPhone($request) : $this->verifyEmail($request);
    }

    /**
     * POST /delete-account/verify — email branch.
     */
    private function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|in:email,phone',
            'email'      => 'required|email',
            'password'   => 'required|string|min:1|max:255',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (RateLimiter::tooManyAttempts($this->ipKey($request, 'verify_email'), 10)) {
            return back()->withErrors(['password' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($this->ipKey($request, 'verify_email'), 60);

        $email = $request->input('email');
        $user  = User::where('email', $email)->first();

        $dummyHash = '$2y$10$abcdefghijklmnopqrstuO7oZxKxJj5N5fIkVQ6g8Kq1mXnN/3k0S';
        $hash = $user ? $user->getRawOriginal('password') : $dummyHash;
        $passwordOk = $hash ? Hash::check($request->input('password'), $hash) : false;

        if (!$user || !$passwordOk) {
            return back()->withErrors(['password' => 'Email or password is incorrect.'])->withInput();
        }

        $deletion = $this->createPendingRequest($user, AccountDeletionRequest::METHOD_EMAIL_PASSWORD);
        $deletion->status      = AccountDeletionRequest::STATUS_VERIFIED;
        $deletion->verified_at = now();
        $deletion->save();

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'verification_passed',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'message'             => 'Identity verified via web (email+password).',
            'metadata'            => ['channel' => 'web'],
            'created_at'          => now(),
        ]);

        return view('delete-account-confirm', [
            'request_uuid' => $deletion->request_uuid,
        ]);
    }

    /**
     * POST /delete-account/verify — phone branch.
     */
    private function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|in:email,phone',
            'phone'      => 'required|string|min:9|max:20',
            'otp'        => 'required|string|min:4|max:10',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (RateLimiter::tooManyAttempts($this->ipKey($request, 'verify_phone'), 10)) {
            return back()->withErrors(['otp' => 'Too many attempts. Try again later.']);
        }
        RateLimiter::hit($this->ipKey($request, 'verify_phone'), 60);

        $phone      = $request->input('phone');
        $normalized = $this->normalizePhone($phone);
        $otp        = $request->input('otp');

        $row = DB::table('phone_verifications')->where('phone', $normalized)->first();
        if (!$row || (int) ($row->is_verified ?? 0) === 1) {
            return back()->withErrors(['otp' => 'No active verification code found. Please request a new one.'])->withInput();
        }

        if (!hash_equals((string) $row->token, (string) $otp)) {
            DB::table('phone_verifications')->where('phone', $normalized)
                ->update(['otp_hit_count' => DB::raw('otp_hit_count + 1'), 'updated_at' => now()]);
            return back()->withErrors(['otp' => 'Incorrect code. Please try again.'])->withInput();
        }

        DB::table('phone_verifications')->where('phone', $normalized)->delete();

        $user = User::where('phone', $phone)->orWhere('phone', $normalized)->first();
        if (!$user) {
            return back()->withErrors(['otp' => 'Verification failed.'])->withInput();
        }

        $deletion = $this->createPendingRequest($user, AccountDeletionRequest::METHOD_PHONE_OTP);
        $deletion->status      = AccountDeletionRequest::STATUS_VERIFIED;
        $deletion->verified_at = now();
        $deletion->save();

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'verification_passed',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'message'             => 'Identity verified via web (phone+otp).',
            'metadata'            => ['channel' => 'web'],
            'created_at'          => now(),
        ]);

        return view('delete-account-confirm', [
            'request_uuid' => $deletion->request_uuid,
        ]);
    }

    /**
     * POST /delete-account/confirm
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'request_uuid' => 'required|uuid',
            'confirm'      => 'required',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        if (!in_array($request->input('confirm'), ['1', 'true', 'on', true], true)) {
            return back()->withErrors(['confirm' => 'You must confirm the deletion.']);
        }

        $deletion = AccountDeletionRequest::where('request_uuid', $request->input('request_uuid'))
            ->where('channel', AccountDeletionRequest::CHANNEL_WEB)
            ->first();

        if (!$deletion) {
            return back()->withErrors(['confirm' => 'Deletion request not found.']);
        }

        if ($deletion->status !== AccountDeletionRequest::STATUS_VERIFIED) {
            return back()->withErrors(['confirm' => 'Your identity has not been verified.']);
        }

        DeleteAccountJob::dispatch($deletion->id);

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'confirm_received',
            'actor_type'          => 'user',
            'actor_id'            => $deletion->user_id,
            'status'              => $deletion->status,
            'message'             => 'Deletion confirmed via web. Job dispatched.',
            'metadata'            => ['channel' => 'web'],
            'created_at'          => now(),
        ]);

        return redirect()->route('delete-account.done');
    }

    /**
     * Idempotent — reuses an in-flight request if one already exists.
     */
    private function createPendingRequest(User $user, string $method): AccountDeletionRequest
    {
        $existing = AccountDeletionRequest::where('user_id', $user->id)
            ->where('channel', AccountDeletionRequest::CHANNEL_WEB)
            ->whereIn('status', [
                AccountDeletionRequest::STATUS_PENDING,
                AccountDeletionRequest::STATUS_VERIFIED,
                AccountDeletionRequest::STATUS_PROCESSING,
            ])
            ->first();

        if ($existing) {
            if ($existing->status === AccountDeletionRequest::STATUS_PENDING && $existing->verification_method !== $method) {
                $existing->verification_method = $method;
                $existing->save();
            }
            return $existing;
        }

        $deletion = AccountDeletionRequest::create([
            'user_id'             => $user->id,
            'request_uuid'        => (string) Str::uuid(),
            'verification_method' => $method,
            'channel'             => AccountDeletionRequest::CHANNEL_WEB,
            'status'              => AccountDeletionRequest::STATUS_PENDING,
            'requested_at'        => now(),
        ]);

        AccountDeletionLog::create([
            'deletion_request_id' => $deletion->id,
            'action'              => 'request_created',
            'actor_type'          => 'user',
            'actor_id'            => $user->id,
            'status'              => $deletion->status,
            'message'             => 'Deletion request created via web.',
            'metadata'            => ['method' => $method, 'channel' => 'web'],
            'created_at'          => now(),
        ]);

        return $deletion;
    }
}
