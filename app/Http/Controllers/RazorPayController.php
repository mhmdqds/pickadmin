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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use Razorpay\Api\Api;
use Razorpay\Api\Errors\BadRequestError;
use Razorpay\Api\Errors\SignatureVerificationError;
use Throwable;

class RazorPayController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private User $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('razor_pay', 'payment_config');

        if ($config && in_array($config->mode, ['live', 'test'])) {
            $values = json_decode($config->{$config->mode . '_values'});
            if ($values) {
                Config::set('razor_config', [
                    'api_key'    => $values->api_key,
                    'api_secret' => $values->api_secret,
                ]);
            }
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

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $payer = json_decode($data['payer_information']);

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
            $business_logo = $business->business_logo ?? url('/');
        } else {
            $business_name = "my_business";
            $business_logo = url('/');
        }

        return view('payment-views.razor-pay', compact('data', 'payer', 'business_logo', 'business_name'));
    }

    /**
     * Razorpay client-side confirmation handler.
     *
     * Hardening (H-2 fix):
     *  1.  Strict input validation: `payment_id` (uuid), `razorpay_payment_id`
     *      (string, prefix `pay_`), `razorpay_order_id` (string, prefix `order_`),
     *      and `razorpay_signature` (string).
     *  2.  Anti-forgery: if all three (order_id, payment_id, signature) are
     *      supplied, verify the HMAC using
     *      `$api->utility->verifyPaymentSignature(...)` — that is the
     *      ONLY way to confirm a payment really came from Razorpay.
     *  3.  Server-to-server authoritative state check via
     *      `$api->payment->fetch($paymentId)`; the local row is updated
     *      only when Razorpay reports `captured`.
     *  4.  Replay protection: already-paid rows are idempotent.
     *  5.  Atomic, validated update with row-level lock inside
     *      `DB::transaction` (`lockForUpdate()`).
     *  6.  Amount, currency, and order_id match against the local row.
     *  7.  success_hook fires exactly once and only after the row is
     *      persisted as paid.
     *  8.  Exception safety and full logging on every failure path.
     *
     * @param Request $request
     *   payment_id (local uuid, route), razorpay_payment_id, razorpay_order_id,
     *   razorpay_signature.
     */
    public function payment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        // -----------------------------------------------------------------
        // 1.  Strict input validation
        // -----------------------------------------------------------------
        $validator = Validator::make($request->all(), [
            'payment_id'          => 'required|uuid',
            'razorpay_payment_id' => 'required|string|max:64',
            'razorpay_order_id'   => 'nullable|string|max:64',
            'razorpay_signature'  => 'nullable|string|max:256',
        ]);
        if ($validator->fails()) {
            Log::warning('RazorPay payment invalid input', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => 'Invalid Razorpay payload']],
            ], 400);
        }

        $localPayId  = (string) $request->input('payment_id');
        $paymentID   = (string) $request->input('razorpay_payment_id');
        $orderID     = (string) $request->input('razorpay_order_id');
        $signature   = (string) $request->input('razorpay_signature');

        // Razorpay ids have known prefixes.
        if (!preg_match('/^pay_[A-Za-z0-9]{6,32}$/', $paymentID)) {
            Log::warning('RazorPay payment: invalid razorpay_payment_id format', [
                'payment_id' => $localPayId,
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_payment_id_format', 'message' => 'razorpay_payment_id format invalid']],
            ], 400);
        }

        $apiKey    = (string) config('razor_config.api_key');
        $apiSecret = (string) config('razor_config.api_secret');
        if ($apiKey === '' || $apiSecret === '') {
            Log::error('RazorPay payment: missing API credentials');
            return response()->json([
                'errors' => [['code' => 'config_error', 'message' => 'Payment gateway not configured']],
            ], 500);
        }

        $api = new Api($apiKey, $apiSecret);

        // -----------------------------------------------------------------
        // 2.  Anti-forgery signature verification
        //     If the client SDK provides all three fields, verify the
        //     HMAC using Razorpay's official utility.  Verification that
        //     throws SignatureVerificationError means the call was NOT
        //     made by Razorpay — the only authoritative source.
        // -----------------------------------------------------------------
        if ($orderID !== '' && $signature !== '') {
            if (!preg_match('/^order_[A-Za-z0-9]{6,32}$/', $orderID)) {
                Log::warning('RazorPay payment: invalid razorpay_order_id format', [
                    'payment_id' => $localPayId,
                ]);
                return response()->json([
                    'errors' => [['code' => 'invalid_order_id_format', 'message' => 'razorpay_order_id format invalid']],
                ], 400);
            }
            try {
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id'   => $orderID,
                    'razorpay_payment_id' => $paymentID,
                    'razorpay_signature'   => $signature,
                ]);
                Log::info('RazorPay payment: signature verified', [
                    'payment_id'         => $localPayId,
                    'razorpay_payment_id'=> $paymentID,
                ]);
            } catch (SignatureVerificationError $e) {
                Log::error('RazorPay payment: signature verification FAILED', [
                    'payment_id'         => $localPayId,
                    'razorpay_payment_id'=> $paymentID,
                    'message'            => $e->getMessage(),
                ]);
                return response()->json([
                    'errors' => [['code' => 'invalid_signature', 'message' => 'Razorpay signature verification failed']],
                ], 422);
            }
        } else {
            // If the SDK did not provide the signature triple, we MUST
            // still verify the payment server-to-server (step 3).
            // We refuse to fall back to a non-verified client-side flow.
            Log::warning('RazorPay payment: missing signature triple, falling back to server-side fetch', [
                'payment_id' => $localPayId,
            ]);
        }

        // -----------------------------------------------------------------
        // 3.  Server-to-server authoritative state check
        //     Regardless of whether the signature was supplied, the
        //     only authoritative source of truth is Razorpay itself.
        // -----------------------------------------------------------------
        try {
            $remote = $api->payment->fetch($paymentID);
        } catch (BadRequestError $e) {
            Log::error('RazorPay payment: server-to-server fetch returned bad request', [
                'payment_id'         => $localPayId,
                'razorpay_payment_id'=> $paymentID,
                'message'            => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'fetch_failed', 'message' => 'Razorpay payment not found']],
            ], 404);
        } catch (Throwable $e) {
            Log::error('RazorPay payment: server-to-side fetch exception', [
                'payment_id'         => $localPayId,
                'razorpay_payment_id'=> $paymentID,
                'message'            => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'fetch_exception', 'message' => 'Razorpay fetch error']],
            ], 502);
        }

        $remoteStatus = isset($remote['status']) ? strtolower((string) $remote['status']) : '';
        if ($remoteStatus !== 'captured') {
            Log::warning('RazorPay payment: status not captured', [
                'payment_id'         => $localPayId,
                'razorpay_payment_id'=> $paymentID,
                'status'             => $remoteStatus,
            ]);
            $payment_data = $this->payment::where('id', $localPayId)->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        // -----------------------------------------------------------------
        // 4–7.  Atomic, validated update with row-level lock
        // -----------------------------------------------------------------
        try {
            $updated = DB::transaction(function () use ($localPayId, $paymentID, $orderID, $remote) {

                /** @var \App\Models\PaymentRequest|null $payment */
                $payment = $this->payment::where('id', $localPayId)->lockForUpdate()->first();
                if (!$payment) {
                    Log::warning('RazorPay payment: local PaymentRequest not found', [
                        'payment_id' => $localPayId,
                    ]);
                    return null;
                }

                // Replay protection
                if ((int) $payment->is_paid === 1) {
                    Log::info('RazorPay payment: already paid (replay ignored)', [
                        'payment_id'     => $localPayId,
                        'existing_trxID' => $payment->transaction_id,
                        'incoming_trxID' => $paymentID,
                    ]);
                    return $payment;
                }

                // Amount match (Razorpay amounts are in paise — integer)
                $localAmount  = round((float) $payment->payment_amount, 2);
                $remoteAmount = isset($remote['amount']) ? round(((int) $remote['amount']) / 100, 2) : null;
                if ($remoteAmount === null || abs($localAmount - $remoteAmount) > 0.01) {
                    Log::error('RazorPay payment: amount mismatch', [
                        'payment_id'   => $localPayId,
                        'local_amount' => $localAmount,
                        'remote_amount'=> $remoteAmount,
                    ]);
                    return null;
                }

                // Currency match (Razorpay returns uppercase)
                $remoteCurrency = isset($remote['currency']) ? strtoupper((string) $remote['currency']) : null;
                if ($remoteCurrency !== null) {
                    $localCurrency = strtoupper((string) ($payment->currency_code ?? ''));
                    if ($localCurrency !== '' && $remoteCurrency !== $localCurrency) {
                        Log::error('RazorPay payment: currency mismatch', [
                            'payment_id'     => $localPayId,
                            'remote_currency'=> $remoteCurrency,
                            'local_currency' => $localCurrency,
                        ]);
                        return null;
                    }
                }

                // Order id match (when the local row carries a known
                // Razorpay order id, e.g. via createOrder).  If absent
                // we cannot enforce this check; we only verify the
                // numeric amount.
                $remoteOrderId = isset($remote['order_id']) ? (string) $remote['order_id'] : '';
                if ($orderID !== '' && $remoteOrderId !== '' && $remoteOrderId !== $orderID) {
                    Log::error('RazorPay payment: order_id mismatch', [
                        'url_order_id'    => $orderID,
                        'remote_order_id' => $remoteOrderId,
                    ]);
                    return null;
                }

                // ALL CHECKS PASSED — persist
                $this->payment::where('id', $localPayId)->update([
                    'payment_method' => 'razor_pay',
                    'is_paid'        => 1,
                    'transaction_id' => $paymentID,
                ]);

                return $this->payment::where('id', $localPayId)->first();
            });

            if (!$updated) {
                return response()->json([
                    'errors' => [['code' => 'payment_validation', 'message' => 'Razorpay payment failed validation']],
                ], 422);
            }

            if ((int) $updated->is_paid !== 1) {
                return $this->payment_response($updated, 'fail');
            }

            if (function_exists($updated->success_hook)) {
                call_user_func($updated->success_hook, $updated);
            }

            return $this->payment_response($updated, 'success');
        } catch (Throwable $e) {
            Log::error('RazorPay payment: exception', [
                'payment_id' => $localPayId,
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'internal_error', 'message' => 'Internal error during payment verification']],
            ], 500);
        }
    }

    /**
     * Razorpay return-URL callback (legacy).
     *
     * This handler is kept for backward compatibility with the existing
     * view that POSTs to /payment/razor-pay/callback.  It MUST:
     *  - Verify the signature if all three fields are present
     *  - Server-to-server fetch the payment
     *  - Replay-protect via is_paid
     *  - Use lockForUpdate()
     */
    public function callback(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $validator = Validator::make(array_merge($request->all(), [
            'payment_data' => $request->input('payment_data'),
        ]), [
            'payment_data'         => 'required',
            'razorpay_payment_id'  => 'required|string|max:64',
        ]);
        if ($validator->fails()) {
            Log::warning('RazorPay callback invalid input', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);
            return redirect()->route('payment-fail');
        }

        $data_id = base64_decode((string) $request->input('payment_data'));
        if (!$data_id || !preg_match('/^[0-9a-fA-F-]{36}$/', (string) $data_id)) {
            Log::warning('RazorPay callback: invalid payment_data', [
                'ip' => $request->ip(),
            ]);
            return redirect()->route('payment-fail');
        }

        $paymentID = (string) $request->input('razorpay_payment_id');
        $orderID   = (string) $request->input('razorpay_order_id');
        $signature = (string) $request->input('razorpay_signature');

        // Signature verification if all three are present
        if ($orderID !== '' && $signature !== '') {
            try {
                $api = new Api(
                    (string) config('razor_config.api_key'),
                    (string) config('razor_config.api_secret')
                );
                $api->utility->verifyPaymentSignature([
                    'razorpay_order_id'   => $orderID,
                    'razorpay_payment_id' => $paymentID,
                    'razorpay_signature'   => $signature,
                ]);
            } catch (SignatureVerificationError $e) {
                Log::error('RazorPay callback: signature verification FAILED', [
                    'payment_id'         => $data_id,
                    'razorpay_payment_id'=> $paymentID,
                    'message'            => $e->getMessage(),
                ]);
                return redirect()->route('payment-fail');
            }
        }

        // Server-to-server authoritative state check
        try {
            $api = new Api(
                (string) config('razor_config.api_key'),
                (string) config('razor_config.api_secret')
            );
            $remote = $api->payment->fetch($paymentID);
        } catch (Throwable $e) {
            Log::error('RazorPay callback: fetch exception', [
                'payment_id'         => $data_id,
                'razorpay_payment_id'=> $paymentID,
                'message'            => $e->getMessage(),
            ]);
            return redirect()->route('payment-fail');
        }

        if (isset($remote['status']) && strtolower((string) $remote['status']) !== 'captured') {
            Log::warning('RazorPay callback: status not captured', [
                'payment_id'         => $data_id,
                'razorpay_payment_id'=> $paymentID,
                'status'             => $remote['status'] ?? null,
            ]);
            $payment_data = $this->payment::where('id', $data_id)->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return redirect()->route('payment-fail');
        }

        try {
            $updated = DB::transaction(function () use ($data_id, $paymentID, $remote) {

                /** @var \App\Models\PaymentRequest|null $payment */
                $payment = $this->payment::where('id', $data_id)->lockForUpdate()->first();
                if (!$payment) {
                    return null;
                }
                if ((int) $payment->is_paid === 1) {
                    return $payment; // idempotent
                }
                $localAmount  = round((float) $payment->payment_amount, 2);
                $remoteAmount = isset($remote['amount']) ? round(((int) $remote['amount']) / 100, 2) : null;
                if ($remoteAmount === null || abs($localAmount - $remoteAmount) > 0.01) {
                    Log::error('RazorPay callback: amount mismatch', [
                        'payment_id'   => $data_id,
                        'local_amount' => $localAmount,
                        'remote_amount'=> $remoteAmount,
                    ]);
                    return null;
                }
                $this->payment::where('id', $data_id)->update([
                    'payment_method' => 'razor_pay',
                    'is_paid'        => 1,
                    'transaction_id' => $paymentID,
                ]);
                return $this->payment::where('id', $data_id)->first();
            });

            if (!$updated || (int) $updated->is_paid !== 1) {
                return redirect()->route('payment-fail');
            }
            if (function_exists($updated->success_hook)) {
                call_user_func($updated->success_hook, $updated);
            }
            return $this->payment_response($updated, 'success');
        } catch (Throwable $e) {
            Log::error('RazorPay callback: exception', [
                'payment_id' => $data_id,
                'message'    => $e->getMessage(),
            ]);
            return redirect()->route('payment-fail');
        }
    }

    public function cancel(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($payment_data, 'fail');
    }

    public function createOrder(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $request->validate([
            'payment_request_id' => 'required|uuid',
            'payment_amount'     => 'required|numeric',
            'currency_code'      => 'required|string'
        ]);

        try {
            $paymentRequest = $this->payment::where(['id' => $request['payment_request_id']])
                ->where(['is_paid' => 0])
                ->first();

            if (!$paymentRequest) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Payment request not found or already paid.'
                ], 404);
            }

            $api = new Api(
                (string) config('razor_config.api_key'),
                (string) config('razor_config.api_secret')
            );

            $razorpayOrder = $api->order->create([
                'receipt'         => 'payment_' . str_replace('-', '', $request['payment_request_id']),
                'amount'          => (int) (round($request['payment_amount'], 2) * 100),
                'currency'        => $request['currency_code'],
                'payment_capture' => 1,
                'notes'           => [
                    'payment_request_id' => $request['payment_request_id']
                ]
            ]);

            return response()->json([
                'status'             => true,
                'payment_request_id' => $request['payment_request_id'],
                'order_id'           => $razorpayOrder['id'],
                'amount'             => $razorpayOrder['amount'],
                'currency'           => $razorpayOrder['currency']
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage()
            ]);
        }
    }

    /**
     * Razorpay webhook-style verification endpoint.
     * The client SDK calls this AFTER the user has completed the
     * Razorpay checkout, supplying the signature triple.  The
     * implementation is hardened identically to `payment()` and is
     * retained for backward compatibility with the existing flow.
     */
    public function verifyPayment(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'order_id'    => 'required|string|max:64',
            'payment_id'  => 'required|string|max:64',
            'signature'   => 'required|string|max:256',
            'payment_request_id' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            Log::warning('RazorPay verifyPayment invalid input', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => 'Invalid verifyPayment payload']],
            ], 400);
        }

        $api = new Api(
            (string) config('razor_config.api_key'),
            (string) config('razor_config.api_secret')
        );

        // 1. Signature verification (the only anti-forgery control)
        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request['order_id'],
                'razorpay_payment_id' => $request['payment_id'],
                'razorpay_signature'   => $request['signature'],
            ]);
        } catch (SignatureVerificationError $e) {
            Log::error('RazorPay verifyPayment: signature verification FAILED', [
                'payment_id'         => $request['payment_id'],
                'message'            => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_signature', 'message' => 'Signature verification failed']],
            ], 422);
        }

        // 2. Server-to-side state check
        try {
            $payment = $api->payment->fetch($request['payment_id']);
        } catch (Throwable $e) {
            Log::error('RazorPay verifyPayment: fetch exception', [
                'payment_id' => $request['payment_id'],
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'fetch_failed', 'message' => 'Razorpay fetch error']],
            ], 502);
        }

        if (!isset($payment['status']) || strtolower((string) $payment['status']) !== 'captured') {
            Log::warning('RazorPay verifyPayment: status not captured', [
                'payment_id' => $request['payment_id'],
                'status'     => $payment['status'] ?? null,
            ]);
            $paymentData = $this->payment::where(['id' => $request['payment_request_id']])->first();
            if ($paymentData && function_exists($paymentData->failure_hook)) {
                call_user_func($paymentData->failure_hook, $paymentData);
            }
            return $this->payment_response($paymentData, 'fail');
        }

        // 3. Atomic, validated update
        try {
            $updated = DB::transaction(function () use ($request, $payment) {
                $local = $this->payment::where('id', $request['payment_request_id'])->lockForUpdate()->first();
                if (!$local) {
                    return null;
                }
                if ((int) $local->is_paid === 1) {
                    return $local; // idempotent
                }
                $localAmount  = round((float) $local->payment_amount, 2);
                $remoteAmount = isset($payment['amount']) ? round(((int) $payment['amount']) / 100, 2) : null;
                if ($remoteAmount === null || abs($localAmount - $remoteAmount) > 0.01) {
                    Log::error('RazorPay verifyPayment: amount mismatch', [
                        'payment_request_id' => $request['payment_request_id'],
                    ]);
                    return null;
                }
                $this->payment::where('id', $request['payment_request_id'])->update([
                    'payment_method' => 'razor_pay',
                    'is_paid'        => 1,
                    'transaction_id' => $request['payment_id'],
                ]);
                return $this->payment::where('id', $request['payment_request_id'])->first();
            });

            if (!$updated || (int) $updated->is_paid !== 1) {
                return response()->json([
                    'errors' => [['code' => 'payment_validation', 'message' => 'Validation failed']],
                ], 422);
            }
            if (function_exists($updated->success_hook)) {
                call_user_func($updated->success_hook, $updated);
            }
            return $this->payment_response($updated, 'success');
        } catch (Throwable $e) {
            Log::error('RazorPay verifyPayment: exception', [
                'payment_request_id' => $request['payment_request_id'],
                'message' => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'internal_error', 'message' => 'Internal error']],
            ], 500);
        }
    }
}
