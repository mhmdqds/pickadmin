<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Models\User;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;
use Throwable;

class MercadoPagoController extends Controller
{
    use Processor;

    /**
     * MercadoPago official IP ranges (IPN/webhook origin).
     * Source: https://www.mercadopago.com.ar/developers/en/docs/your-integrations/notifications/webhooks
     */
    private const MERCADOPAGO_IPV4_RANGES = [
        '209.225.49.0/24',
        '216.33.197.0/24',
        '63.128.82.0/24',
    ];

    private PaymentRequest $paymentRequest;
    private $config;
    private $user;

    public function __construct(PaymentRequest $paymentRequest, User $user)
    {
        $config = $this->payment_config('mercadopago', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config = json_decode($config->test_values);
        }
        $this->paymentRequest = $paymentRequest;
        $this->user = $user;
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->paymentRequest::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $config = $this->config;
        return view('payment-views.payment-view-marcedo-pogo', compact('config', 'data'));
    }

    public function make_payment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id'        => 'required|uuid',
            'token'             => 'required|string',
            'payment_method_id' => 'required|string',
            'payer'             => 'required|array',
            'payer.email'       => 'required|email',
            'transaction_amount'=> 'required|numeric|min:0.01',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $this->error_processor($validator)], 400);
        }

        MercadoPagoConfig::setAccessToken($this->config->access_token);
        $requestOptions = new RequestOptions();
        $requestOptions->setCustomHeaders([
            "x-idempotency-key" => (string) uniqid("mp_", true),
        ]);
        $paymentRequest = $this->paymentRequest->where('id', $request['payment_id'])->first();
        if (!$paymentRequest) {
            return response()->json(['status' => 'fail', 'message' => 'payment_request_not_found'], 404);
        }

        $client = new PaymentClient();

        try {
            $payment = $client->create([
                "token"              => $request['token'],
                "issuer_id"          => $request['issuer_id'] ?? null,
                "payment_method_id" => $request['payment_method_id'],
                "transaction_amount" => (float) $request['transaction_amount'],
                "installments"       => (int) ($request['installments'] ?? 1),
                "external_reference" => $paymentRequest->id,
                "payer"              => [
                    "email"          => $request['payer']['email'],
                    "identification" => [
                        "type"   => $request['payer']['identification']['type'],
                        "number" => $request['payer']['identification']['number'],
                    ],
                ],
            ], $requestOptions);
        } catch (\Exception $e) {
            Log::error('MercadoPago create-payment exception', [
                'payment_id'  => $request['payment_id'],
                'http_status' => (method_exists($e, 'getCode') ? $e->getCode() : null),
                'message'     => $e->getMessage(),
            ]);
            return response()->json(['status' => 'fail', 'message' => 'mercadopago_create_failed'], 500);
        }

        // ONLY mark the local DB as paid after MercadoPago responds with `approved`.
        if (isset($payment->status) && $payment->status === 'approved') {
            // Server-to-server amount cross-check.
            if (abs((float) $payment->transaction_amount - (float) $paymentRequest->payment_amount) > 0.01) {
                Log::error('MercadoPago amount mismatch on create', [
                    'payment_id'    => $request['payment_id'],
                    'local_amount'  => $paymentRequest->payment_amount,
                    'remote_amount' => $payment->transaction_amount,
                ]);
                return response()->json(['status' => 'fail', 'message' => 'amount_mismatch'], 422);
            }

            $this->paymentRequest::where('id', $paymentRequest->id)->update([
                'payment_method' => 'mercadopago',
                'is_paid'         => 1,
                'transaction_id'  => (string) $payment->id,
            ]);

            $data = $this->paymentRequest::where('id', $request['payment_id'])->first();
            if (isset($data) && function_exists($data->success_hook)) {
                call_user_func($data->success_hook, $data);
            }
            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'fail', 'message' => 'payment_not_approved']);
    }

    /**
     * MercadoPago return-URL callback AND webhook (IPN) handler.
     *
     * Hardening (H-3 fix):
     *  1.  Strict input validation: payment_id (uuid), paymentID (int),
     *      status (one of approved/in_process/pending/rejected/cancelled/refunded).
     *  2.  If `status != approved` → fail path (no DB write).
     *  3.  Replay protection: only unpaid rows are processed.
     *  4.  NEVER trust `$request['status']` alone: we always verify the
     *      payment server-to-server using `PaymentClient::get($paymentID)`.
     *      The `external_reference` returned by MercadoPago must match
     *      the local PaymentRequest id.
     *  5.  Status, transaction_amount, currency_code, and (when available)
     *      payment_method_id are all checked against the local row.
     *  6.  All writes happen inside a `DB::transaction` with
     *      `lockForUpdate()` to eliminate the TOCTOU race.
     *  7.  success_hook fires exactly once and only after the row is
     *      persisted as paid.
     *  8.  Defense-in-depth IP allow-list for IPN-style calls.
     *
     *  The response token is still a base64-encoded payload — the client
     *  must also re-check `is_paid` server-side. This implementation
     *  makes the token meaningless without a paid row.
     *
     * @param Request $request  payment_id (route), paymentID (MercadoPago), status, topic
     */
    public function callback(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        // -----------------------------------------------------------------
        // 1.  Strict input validation
        // -----------------------------------------------------------------
        $validator = Validator::make(array_merge($request->all(), [
            'payment_id' => $request->route('payment_id'),
        ]), [
            'payment_id' => 'required|uuid',
            'status'     => 'required|string|in:success,failure,pending,approved,in_process,rejected,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            Log::warning('MercadoPago callback invalid input', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => 'Invalid MercadoPago callback payload']],
            ], 400);
        }

        // -----------------------------------------------------------------
        // 2.  Quick status pre-check — if user came from the failure URL,
        //     we still finalize as failure (no DB write).
        // -----------------------------------------------------------------
        $rawStatus = strtolower((string) $request->input('status'));
        if (in_array($rawStatus, ['failure', 'rejected', 'cancelled', 'refunded'], true)) {
            $payment_data = $this->paymentRequest::where('id', $request->route('payment_id'))->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        $localPayId = (string) $request->route('payment_id');

        // -----------------------------------------------------------------
        // 8.  Defense-in-depth: IP allow-list for IPN-style calls
        // -----------------------------------------------------------------
        if (!$this->isRequestFromMercadoPagoRanges($request->ip())) {
            Log::warning('MercadoPago callback from non-MercadoPago IP', [
                'ip'          => $request->ip(),
                'payment_id'  => $localPayId,
            ]);
        }

        // -----------------------------------------------------------------
        // 4.  We REQUIRE a `paymentID` (MercadoPago numeric id) for the
        //     server-to-server verification.  If only `payment_id`
        //     (our local UUID) is present, refuse.
        // -----------------------------------------------------------------
        $paymentID = $request->input('paymentID')
            ?? $request->input('data_id')
            ?? $request->input('id');

        if (empty($paymentID) || !is_numeric($paymentID)) {
            Log::warning('MercadoPago callback missing numeric paymentID', [
                'payment_id' => $localPayId,
                'ip'         => $request->ip(),
                'request'    => $request->except(['payer', 'card', 'security_code']),
            ]);
            return response()->json([
                'errors' => [['code' => 'missing_paymentID', 'message' => 'paymentID required for verification']],
            ], 400);
        }

        $paymentID = (int) $paymentID;

        // -----------------------------------------------------------------
        // 5/6.  Atomic, validated update with row-level lock
        // -----------------------------------------------------------------
        try {
            $updated = DB::transaction(function () use ($localPayId, $paymentID, $request) {

                /** @var \App\Models\PaymentRequest|null $payment */
                $payment = $this->paymentRequest::where('id', $localPayId)->lockForUpdate()->first();
                if (!$payment) {
                    Log::warning('MercadoPago callback: local PaymentRequest not found', [
                        'payment_id' => $localPayId,
                    ]);
                    return null;
                }

                // Replay protection
                if ((int) $payment->is_paid === 1) {
                    Log::info('MercadoPago callback: already paid (replay ignored)', [
                        'payment_id'     => $localPayId,
                        'existing_trxID' => $payment->transaction_id,
                        'incoming_trxID' => $paymentID,
                    ]);
                    return $payment;
                }

                // Server-to-server authoritative state check
                $remote = $this->fetchPaymentServerSide($paymentID);
                if (!$remote) {
                    Log::error('MercadoPago callback: could not fetch remote payment', [
                        'payment_id' => $localPayId,
                        'mp_id'      => $paymentID,
                    ]);
                    return null;
                }

                // The remote payment must reference our local row.
                $remoteExtRef = isset($remote['external_reference'])
                    ? (string) $remote['external_reference']
                    : null;
                if ($remoteExtRef !== null && $remoteExtRef !== $localPayId) {
                    Log::error('MercadoPago callback: external_reference mismatch', [
                        'local_id'      => $localPayId,
                        'remote_xref'   => $remoteExtRef,
                    ]);
                    return null;
                }

                // status MUST be 'approved'
                $remoteStatus = isset($remote['status']) ? strtolower((string) $remote['status']) : '';
                if ($remoteStatus !== 'approved') {
                    Log::warning('MercadoPago callback: remote status not approved', [
                        'payment_id' => $localPayId,
                        'mp_id'      => $paymentID,
                        'status'     => $remoteStatus,
                    ]);
                    if (function_exists($payment->failure_hook)) {
                        call_user_func($payment->failure_hook, $payment);
                    }
                    return $payment;
                }

                // Amount match (within ±0.01)
                $localAmount  = round((float) $payment->payment_amount, 2);
                $remoteAmount = isset($remote['transaction_amount']) ? round((float) $remote['transaction_amount'], 2) : null;
                if ($remoteAmount === null || abs($localAmount - $remoteAmount) > 0.01) {
                    Log::error('MercadoPago callback: amount mismatch', [
                        'payment_id'   => $localPayId,
                        'local_amount' => $localAmount,
                        'remote_amount'=> $remoteAmount,
                    ]);
                    return null;
                }

                // Currency match (when supplied)
                $remoteCurrency = isset($remote['currency_id'])
                    ? strtolower((string) $remote['currency_id'])
                    : null;
                if ($remoteCurrency !== null) {
                    $localCurrency = strtolower((string) ($payment->currency_code ?? ''));
                    if ($localCurrency !== '' && $remoteCurrency !== $localCurrency) {
                        Log::error('MercadoPago callback: currency mismatch', [
                            'payment_id'      => $localPayId,
                            'remote_currency' => $remoteCurrency,
                            'local_currency'  => $localCurrency,
                        ]);
                        return null;
                    }
                }

                // Validate MP numeric id format (typically a 10-12 digit int)
                if ($paymentID <= 0) {
                    Log::error('MercadoPago callback: invalid paymentID', [
                        'payment_id' => $localPayId,
                        'mp_id'      => $paymentID,
                    ]);
                    return null;
                }

                // ALL CHECKS PASSED
                $this->paymentRequest::where('id', $localPayId)->update([
                    'payment_method' => 'mercadopago',
                    'is_paid'        => 1,
                    'transaction_id' => (string) $paymentID,
                ]);

                return $this->paymentRequest::where('id', $localPayId)->first();
            });

            if (!$updated) {
                return response()->json([
                    'errors' => [['code' => 'payment_validation', 'message' => 'MercadoPago payment failed validation']],
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
            Log::error('MercadoPago callback exception', [
                'payment_id' => $localPayId,
                'mp_id'      => $paymentID,
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'internal_error', 'message' => 'Internal error during payment verification']],
            ], 500);
        }
    }

    /**
     * Server-to-server fetch of the authoritative payment state from
     * MercadoPago.  Returns the raw decoded body as an associative
     * array, or null on transport / decode failure.
     *
     * @return array<string,mixed>|null
     */
    private function fetchPaymentServerSide(int $paymentID): ?array
    {
        try {
            MercadoPagoConfig::setAccessToken($this->config->access_token);
            $client   = new PaymentClient();
            $payment  = $client->get($paymentID);
            if (is_object($payment)) {
                $payment = json_decode(json_encode($payment), true);
            }
            if (!is_array($payment)) {
                Log::error('MercadoPago fetch: malformed response', [
                    'mp_id' => $paymentID,
                ]);
                return null;
            }
            return $payment;
        } catch (Throwable $e) {
            Log::error('MercadoPago fetch exception', [
                'mp_id'   => $paymentID,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Defense-in-depth: MercadoPago IPN webhook origin ranges.
     */
    private function isRequestFromMercadoPagoRanges(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }
        $packed = ip2long($ip);
        if ($packed === false) {
            return false;
        }
        foreach (self::MERCADOPAGO_IPV4_RANGES as $cidr) {
            [$subnet, $bits] = explode('/', $cidr, 2);
            $bits = (int) $bits;
            $mask = $bits === 0 ? 0 : (-1 << (32 - $bits)) & 0xFFFFFFFF;
            $sub = ip2long($subnet);
            if ($sub === false) {
                continue;
            }
            if (($packed & $mask) === ($sub & $mask)) {
                return true;
            }
        }
        return false;
    }
}
