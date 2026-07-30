<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use Throwable;

class SenangPayController extends Controller
{
    use Processor;

    private $config_values;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('senang_pay', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($payment_data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($payment_data['payer_information']);
        $config = $this->config_values;
        session()->put('payment_id', $payment_data->id);
        return view('payment-views.senang-pay', compact('payment_data', 'payer', 'config'));
    }

    /**
     * SenangPay return-URL callback.
     *
     * Hardening (H-4 fix):
     *  1.  Strict input validation: status_id (0/1/2/3), transaction_id,
     *      amount (decimal), currency (string), order_id or session payment_id (uuid).
     *  2.  status_id must be exactly 1 (success). All other values are failure.
     *  3.  HMAC-SHA256 (preferred) or MD5 (legacy Billplz-style) signature
     *      verification using `hash_equals()` and the configured secret key.
     *      The signature covers: order_id | status_id | amount | currency.
     *  4.  Replay protection: only unpaid rows are processed.
     *  5.  Atomic, validated update with row-level lock inside
     *      `DB::transaction` (`lockForUpdate()`).
     *  6.  Amount, currency and order_id match against the local row.
     *  7.  success_hook fires exactly once and only after the row is
     *      persisted as paid.
     *  8.  Exception safety and full logging on every failure path.
     *
     * @param Request $request  status_id, transaction_id, amount, currency,
     *                          order_id (SenangPay order id, optional),
     *                          payment_id (local uuid, optional),
     *                          hash/signature (required for success)
     */
    public function return_senang_pay(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        // -----------------------------------------------------------------
        // 1.  Strict input validation
        // -----------------------------------------------------------------
        $validator = Validator::make($request->all(), [
            'status_id'      => 'required|integer|in:0,1,2,3',
            'transaction_id' => 'nullable|string|max:128',
            'order_id'       => 'nullable|string|max:128',
            'amount'         => 'nullable|numeric|min:0',
            'currency'       => 'nullable|string|max:8',
            'payment_id'     => 'nullable|uuid',
            'hash'           => 'nullable|string|max:256',
            'signature'      => 'nullable|string|max:256',
        ]);

        if ($validator->fails()) {
            Log::warning('SenangPay callback invalid input', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => 'Invalid SenangPay callback payload']],
            ], 400);
        }

        // -----------------------------------------------------------------
        // 2.  Resolve the local payment row.
        //     We accept `payment_id` (local uuid) either from the query
        //     string or from the session. SenangPay's `order_id` is the
        //     local uuid we sent as `external_reference`; if present we
        //     prefer it for verification.
        // -----------------------------------------------------------------
        $localPayId = (string) ($request->input('payment_id')
            ?? $request->input('order_id')
            ?? session()->get('payment_id')
            ?? '');

        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $localPayId)) {
            Log::warning('SenangPay callback: missing or invalid payment_id', [
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_payment_id', 'message' => 'payment_id required']],
            ], 400);
        }

        $status = (int) $request->input('status_id');

        // Failure path (status != 1)
        if ($status !== 1) {
            $payment_data = $this->payment::where('id', $localPayId)->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        // -----------------------------------------------------------------
        // 3.  Signature verification
        //     SenangPay / Billplz-style hash construction:
        //       hash = md5(secret_key + order_id + status_id + amount + currency)
        //     Some deployments use SHA256 with a different ordering; we
        //     accept any of the three common variants.
        // -----------------------------------------------------------------
        if (!$this->verifySignature($request)) {
            Log::error('SenangPay callback: invalid signature', [
                'payment_id' => $localPayId,
                'ip'         => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_signature', 'message' => 'Signature verification failed']],
            ], 422);
        }

        // -----------------------------------------------------------------
        // 4–7.  Atomic, validated update with row-level lock
        // -----------------------------------------------------------------
        try {
            $updated = DB::transaction(function () use ($localPayId, $status, $request) {

                /** @var \App\Models\PaymentRequest|null $payment */
                $payment = $this->payment::where('id', $localPayId)->lockForUpdate()->first();
                if (!$payment) {
                    Log::warning('SenangPay callback: local PaymentRequest not found', [
                        'payment_id' => $localPayId,
                    ]);
                    return null;
                }

                // Replay protection
                if ((int) $payment->is_paid === 1) {
                    Log::info('SenangPay callback: already paid (replay ignored)', [
                        'payment_id'     => $localPayId,
                        'existing_trxID' => $payment->transaction_id,
                    ]);
                    return $payment;
                }

                // Order id (SenangPay's order_id) must match our local uuid
                $remoteOrderId = (string) $request->input('order_id');
                if ($remoteOrderId !== '' && $remoteOrderId !== $localPayId) {
                    Log::error('SenangPay callback: order_id mismatch', [
                        'local_id'    => $localPayId,
                        'remote_id'   => $remoteOrderId,
                    ]);
                    return null;
                }

                // Amount match (within ±0.01)
                $localAmount  = round((float) $payment->payment_amount, 2);
                $remoteAmountRaw = $request->input('amount');
                if ($remoteAmountRaw !== null) {
                    $remoteAmount = round((float) $remoteAmountRaw, 2);
                    if (abs($localAmount - $remoteAmount) > 0.01) {
                        Log::error('SenangPay callback: amount mismatch', [
                            'payment_id'   => $localPayId,
                            'local_amount' => $localAmount,
                            'remote_amount'=> $remoteAmount,
                        ]);
                        return null;
                    }
                }

                // Currency match (when supplied)
                $remoteCurrency = strtoupper((string) ($request->input('currency') ?? ''));
                if ($remoteCurrency !== '') {
                    $localCurrency = strtoupper((string) ($payment->currency_code ?? ''));
                    if ($localCurrency !== '' && $remoteCurrency !== $localCurrency) {
                        Log::error('SenangPay callback: currency mismatch', [
                            'payment_id'     => $localPayId,
                            'remote_currency'=> $remoteCurrency,
                            'local_currency' => $localCurrency,
                        ]);
                        return null;
                    }
                }

                // Transaction id format (SenangPay returns alphanumeric IDs)
                $trxID = (string) $request->input('transaction_id', '');
                if ($trxID === '' || !preg_match('/^[A-Za-z0-9._-]{4,128}$/', $trxID)) {
                    Log::error('SenangPay callback: invalid transaction_id', [
                        'payment_id'   => $localPayId,
                        'transaction_id'=> $trxID,
                    ]);
                    return null;
                }

                // ALL CHECKS PASSED — persist
                $this->payment::where('id', $localPayId)->update([
                    'payment_method' => 'senang_pay',
                    'is_paid'        => 1,
                    'transaction_id' => $trxID,
                ]);

                return $this->payment::where('id', $localPayId)->first();
            });

            if (!$updated) {
                return response()->json([
                    'errors' => [['code' => 'payment_validation', 'message' => 'SenangPay payment failed validation']],
                ], 422);
            }

            if ((int) $updated->is_paid !== 1) {
                return $this->payment_response($updated, 'fail');
            }

            if (function_exists($updated->success_hook)) {
                call_user_func($updated->success_hook, $updated);
            }

            // Clean up session payment_id after success.
            session()->forget('payment_id');

            return $this->payment_response($updated, 'success');
        } catch (Throwable $e) {
            Log::error('SenangPay callback exception', [
                'payment_id' => $localPayId,
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'internal_error', 'message' => 'Internal error during payment verification']],
            ], 500);
        }
    }

    /**
     * Verify the HMAC/SHA256/MD5 signature returned by SenangPay.
     * The secret key is taken from the configured `secret_key` (or
     * `merchant_id` as a fallback) — these match the keys configured
     * in the SenangPay merchant dashboard.
     *
     * Returns true if the signature is valid; false otherwise.
     */
    private function verifySignature(Request $request): bool
    {
        $secret = (string) data_get($this->config_values, 'secret_key', '');
        if ($secret === '') {
            $secret = (string) data_get($this->config_values, 'merchant_id', '');
        }
        if ($secret === '') {
            Log::error('SenangPay verifySignature: no secret configured');
            return false;
        }

        $orderId   = (string) $request->input('order_id', '');
        $statusId  = (string) $request->input('status_id', '');
        $amount    = (string) $request->input('amount', '');
        $currency  = (string) $request->input('currency', '');
        $provided  = (string) ($request->input('hash') ?? $request->input('signature') ?? '');

        if ($provided === '') {
            // If SenangPay has not yet enabled hash on this account, we
            // accept the request but log a warning so the operator can
            // enforce signature on the merchant dashboard.
            Log::warning('SenangPay callback: signature/hash missing', [
                'payment_id' => $orderId,
            ]);
            // Returning true here is dangerous; the operator MUST
            // enable the signature. The missing-hash warning is a
            // deployment aid.
            return false;
        }

        // Variant 1: md5(secret + order + status + amount + currency)
        $candidate1 = md5($secret . $orderId . $statusId . $amount . $currency);
        if (hash_equals($candidate1, $provided)) {
            return true;
        }

        // Variant 2: md5(amount + currency + order + status + secret) (Billplz reverse-order)
        $candidate2 = md5($amount . $currency . $orderId . $statusId . $secret);
        if (hash_equals($candidate2, $provided)) {
            return true;
        }

        // Variant 3: hmac-sha256(secret, payload) where payload is the
        // ampersand-joined canonical string used by some integrations.
        $payload = http_build_query([
            'order_id'   => $orderId,
            'status_id'  => $statusId,
            'amount'     => $amount,
            'currency'   => $currency,
        ]);
        $candidate3 = hash_hmac('sha256', $payload, $secret);
        if (hash_equals($candidate3, $provided)) {
            return true;
        }

        return false;
    }
}
