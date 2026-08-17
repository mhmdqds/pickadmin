<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Traits\Processor;
use App\Models\PaymentRequest;
use Throwable;

class PaypalPaymentController extends Controller
{
    use Processor;

    /**
     * Whitelist of `success_hook` / `failure_hook` function names that
     * `Processor::payment_response()` is allowed to invoke. Any DB value
     * outside this list is rejected (FIX-14 / CWE-94 hardening).
     */
    private const ALLOWED_HOOKS = [
        'order_place',
        'order_failed',
        'wallet_success',
        'wallet_failed',
        'sub_success',
        'sub_fail',
        'trip_payment_success',
        'trip_payment_fail',
        'collect_cash_success',
        'collect_cash_fail',
    ];

    private $config_values;
    private $base_url;

    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('paypal', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }

        if($config){
            $this->base_url = ($config->mode == 'test') ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
        }
        $this->payment = $payment;
    }

    /**
     * Common cURL options for PayPal API calls.
     * All PayPal calls MUST verify TLS certificates (CWE-295 fix: M-2).
     */
    private function paypalCurlOptions(string $url, string $method = 'GET', array $headers = [], array $body = []): array
    {
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
        } elseif (strtoupper($method) !== 'GET') {
            $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            if (!empty($body)) {
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            }
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        return $opts;
    }

    private function paypalRequest(string $url, string $method = 'GET', array $headers = [], array $body = [])
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->paypalCurlOptions($url, $method, $headers, $body));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            return ['error' => 'transport_error', 'detail' => $err];
        }
        return json_decode($response, true) ?? ['raw' => $response];
    }

    public function token()
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->paypalCurlOptions(
            $this->base_url . '/v1/oauth2/token',
            'POST',
            ['Content-Type: application/x-www-form-urlencoded'],
            ['grant_type' => 'client_credentials']
        ));
        // PayPal OAuth2 token endpoint requires HTTP Basic Auth
        curl_setopt($ch, CURLOPT_USERPWD, $this->config_values->client_id . ':' . $this->config_values->client_secret);

        $accessToken = curl_exec($ch);
        if (curl_errno($ch)) {
            \Log::error('PayPal token error', ['curl_error' => curl_error($ch)]);
        }
        curl_close($ch);
        return $accessToken;
    }

    /**
     * Create a PayPal Order server-side, persist the returned
     * PayPal order id on the local payment_request, and redirect
     * the user to the PayPal-hosted approval URL.
     *
     * FIX-01 / FIX-02 / FIX-05: local <-> PayPal order binding
     * established here, with the trusted server-side currency.
     */
    public function payment(Request $request)
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


        if ($data->additional_data != null) {
            $business = json_decode($data->additional_data);
            $business_name = $business->business_name ?? "my_business";
        } else {
            $business_name = "my_business";
        }

        $accessToken = json_decode($this->token(), true);

        if (!isset($accessToken['access_token'])) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }
        $accessToken = $accessToken['access_token'];

        // FIX-05: use the trusted server-side currency, not a hardcoded "USD".
        $currency = strtoupper($data->currency_code ?? 'USD');
        if (!$this->isPayPalSupportedCurrency($currency)) {
            Log::error('PayPal currency not supported', [
                'payment_id' => $data->id,
                'currency'   => $currency,
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $payment_data = [];
        $payment_data['purchase_units'] = [[
            'reference_id' => $data->id,
            'name'         => $business_name,
            'desc'         => 'payment ID #' . $data->id,
            'amount'       => [
                'currency_code' => $currency,
                'value'         => number_format((float)$data->payment_amount, 2, '.', ''),
            ],
        ]];
        $payment_data['invoice_id']          = $data->id;
        $payment_data['invoice_description'] = "Order #{$payment_data['invoice_id']} Invoice";
        $payment_data['total']               = number_format((float)$data->payment_amount, 2, '.', '');
        $payment_data['intent']              = 'CAPTURE';
        $payment_data['application_context'] = [
            'return_url' => route('paypal.success', ['payment_id' => $data->id]),
            'cancel_url' => route('paypal.cancel',  ['payment_id' => $data->id]),
        ];

        // FIX-21: deterministic Request-Id per local payment_id.
        $requestId = hash('sha256', 'paypal-create:' . $data->id);

        $ch = curl_init();
        curl_setopt_array($ch, $this->paypalCurlOptions(
            $this->base_url . '/v2/checkout/orders',
            'POST',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
                'Paypal-Request-Id:' . $requestId,
            ],
            $payment_data
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            "Authorization: Bearer $accessToken",
            'Paypal-Request-Id:' . $requestId,
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('PayPal create-order error', ['curl_error' => curl_error($ch)]);
        }
        curl_close($ch);

        $response = json_decode($response);

        if (!isset($response->id) || !isset($response->links) || !is_array($response->links) || count($response->links) < 2) {
            // FIX-22: do not log the (potentially sensitive) response body.
            Log::error('PayPal create-order returned malformed body', [
                'payment_id' => $data->id,
                'has_id'     => isset($response->id),
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        // FIX-02 / FIX-01 binding: persist the PayPal order id.
        try {
            DB::table('payment_requests')
                ->where('id', $data->id)
                ->update(['paypal_order_id' => $response->id]);
        } catch (Throwable $e) {
            Log::error('PayPal persist order_id failed', [
                'payment_id' => $data->id,
                'error'      => $e->getMessage(),
            ]);
        }

        $links = $response->links;
        return Redirect::away($links[1]->href);
    }

    /**
     * Allow-list of PayPal-supported currencies (FIX-05).
     */
    private function isPayPalSupportedCurrency(string $code): bool
    {
        static $supported = [
            'AUD','BRL','CAD','CNY','CZK','DKK','EUR','HKD','HUF','ILS',
            'JPY','MYR','MXN','NOK','NZD','PHP','PLN','GBP','RUB','SGD',
            'SEK','CHF','TWD','THB','TRY','USD','ZAR',
        ];
        return in_array($code, $supported, true);
    }

    /**
     * Called when the user clicks Cancel on PayPal.
     */
    public function cancel(Request $request)
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (!isset($data)) {
            return response()->json(['message' => 'Payment not found'], 404);
        }
        return $this->payment_response($data, 'cancel');
    }

    /**
     * PayPal return-URL callback. Enforces:
     *   (a) payment_id exists and not yet paid
     *   (b) request.token === stored paypal_order_id (FIX-01)
     *   (c) capture status === COMPLETED
     *   (d) captured amount == local amount (FIX-03)
     *   (e) captured currency == local currency (FIX-05)
     *   (f) atomic write under lockForUpdate (FIX-06 / FIX-07)
     */
    public function success(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
            'token'      => 'required|string|max:64',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid callback'], 400);
        }

        $paymentId     = $request['payment_id'];
        $paypalOrderId = $request['token'];

        // FIX-01 / FIX-06: lock the local row, verify the PayPal
        // token matches what we stored at create-order time.
        $row = DB::transaction(function () use ($paymentId, $paypalOrderId) {
            $r = $this->payment::where(['id' => $paymentId])->lockForUpdate()->first();
            if (!$r) {
                return null;
            }
            if ((int)$r->is_paid === 1) {
                return $r; // idempotent
            }
            if (empty($r->paypal_order_id) || !hash_equals((string)$r->paypal_order_id, (string)$paypalOrderId)) {
                Log::warning('PayPal order-id mismatch on success', [
                    'payment_id'         => $paymentId,
                    'stored_paypal_id'   => $r->paypal_order_id,
                    'returned_paypal_id' => $paypalOrderId,
                ]);
                return null;
            }
            return $r;
        });

        if (!$row) {
            return response()->json(['message' => 'Payment not found or token mismatch'], 404);
        }

        if ((int)$row->is_paid === 1) {
            return $this->payment_response($row, 'success');
        }

        $accessToken = json_decode($this->token(), true);
        $accessToken = $accessToken['access_token'] ?? null;
        if (!$accessToken) {
            Log::error('PayPal success: could not mint access token', ['payment_id' => $paymentId]);
            return $this->payment_response($row, 'fail');
        }

        // FIX-21: deterministic Request-Id so PayPal de-duplicates
        // a network-level retry of the same capture.
        $requestId = hash('sha256', 'paypal-capture:' . $row->id . ':' . $paypalOrderId);

        $ch = curl_init();
        curl_setopt_array($ch, $this->paypalCurlOptions(
            $this->base_url . "/v2/checkout/orders/{$paypalOrderId}/capture",
            'POST',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
                'Paypal-Request-Id:' . $requestId,
            ]
        ));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            Log::error('PayPal capture error', ['curl_error' => curl_error($ch)]);
        }
        curl_close($ch);

        $response = json_decode($result);

        if (!isset($response->status) || $response->status !== 'COMPLETED') {
            Log::warning('PayPal capture not COMPLETED', [
                'payment_id' => $paymentId,
                'status'     => $response->status ?? null,
            ]);
            $this->runWhitelistHookIfPresent($row, 'failure');
            return $this->payment_response($row, 'fail');
        }

        // FIX-03 / FIX-04 / FIX-05: amount + currency equivalence.
        $expectedAmount   = number_format((float)$row->payment_amount, 2, '.', '');
        $expectedCurrency = strtoupper($row->currency_code ?? 'USD');

        $captured = $this->extractCaptured($response);
        if ($captured === null) {
            Log::warning('PayPal capture response missing capture object', [
                'payment_id' => $paymentId,
            ]);
            $this->runWhitelistHookIfPresent($row, 'failure');
            return $this->payment_response($row, 'fail');
        }

        $capturedAmount   = number_format((float)$captured['amount'], 2, '.', '');
        $capturedCurrency = strtoupper($captured['currency']);
        $captureId        = $captured['id'];

        if ($capturedAmount !== $expectedAmount || $capturedCurrency !== $expectedCurrency) {
            Log::warning('PayPal amount/currency mismatch — REJECTED', [
                'payment_id'        => $paymentId,
                'expected_amount'   => $expectedAmount,
                'captured_amount'   => $capturedAmount,
                'expected_currency' => $expectedCurrency,
                'captured_currency' => $capturedCurrency,
            ]);
            $this->runWhitelistHookIfPresent($row, 'failure');
            return $this->payment_response($row, 'fail');
        }

        // FIX-06 / FIX-07 / FIX-08: atomic write under lock.
        $updated = DB::transaction(function () use ($row, $captureId, $capturedAmount, $capturedCurrency) {
            $r = $this->payment::where(['id' => $row->id])
                ->where('is_paid', 0)
                ->lockForUpdate()
                ->first();
            if (!$r) {
                return null;
            }
            $r->payment_method      = 'paypal';
            $r->is_paid             = 1;
            $r->transaction_id      = $captureId;
            $r->paypal_capture_id   = $captureId;
            $r->captured_amount     = $capturedAmount;
            $r->captured_currency   = $capturedCurrency;
            $r->paypal_processed_at = now();
            $r->save();
            return $r;
        });

        if (!$updated) {
            return $this->payment_response($row, 'success');
        }

        $this->runWhitelistHookIfPresent($updated, 'success');
        return $this->payment_response($updated, 'success');
    }

    /**
     * Pull the captured amount/currency/id out of the PayPal
     * capture response. Returns null on a malformed payload.
     */
    private function extractCaptured(object $response): ?array
    {
        $purchaseUnits = $response->purchase_units ?? null;
        if (!is_array($purchaseUnits) || empty($purchaseUnits)) {
            return null;
        }
        $payments = $purchaseUnits[0]->payments ?? null;
        $captures = $payments->captures ?? null;
        if (!is_array($captures) || empty($captures)) {
            return null;
        }
        $c = $captures[0];
        if (!isset($c->id) || !isset($c->amount->value) || !isset($c->amount->currency_code)) {
            return null;
        }
        return [
            'id'       => (string)$c->id,
            'amount'   => (string)$c->amount->value,
            'currency' => (string)$c->amount->currency_code,
        ];
    }

    /**
     * POST /payment/paypal/webhook
     *
     * Webhook reconciliation: verifies PayPal signature, dedupes by
     * event_id, matches to local row, updates bookkeeping columns.
     * Does NOT flip `is_paid` on its own — success() is the
     * authoritative writer (FIX-16 / FIX-17 / FIX-18 / FIX-19).
     */
    public function webhook(Request $request)
    {
        $eventId   = $request->input('id');
        $eventType = $request->input('event_type');
        $resource  = $request->input('resource', []);

        $verifyUrl = ($this->config_values->mode ?? 'live') === 'live'
            ? 'https://api-m.paypal.com/v1/notifications/verify-webhook-signature'
            : 'https://api-m.sandbox.paypal.com/v1/notifications/verify-webhook-signature';

        $webhookId = DB::table('payment_requests')
            ->whereNotNull('paypal_webhook_id')
            ->value('paypal_webhook_id');

        $verifyBody = [
            'auth_algo'         => $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url'          => $request->header('PAYPAL-CERT-URL'),
            'transmission_id'   => $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig'  => $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id'        => $webhookId,
            'webhook_event'     => $request->all(),
        ];

        $verifyResponse = Http::withBasicAuth(
            $this->config_values->client_id ?? '',
            $this->config_values->client_secret ?? ''
        )->withHeaders(['Content-Type' => 'application/json'])
         ->post($verifyUrl, $verifyBody);

        if (!$verifyResponse->ok() || ($verifyResponse->json('verification_status') ?? null) !== 'SUCCESS') {
            Log::warning('PayPal webhook signature verification failed', [
                'event_id'   => $eventId,
                'event_type' => $eventType,
            ]);
            return response()->json(['message' => 'invalid signature'], 400);
        }

        $existing = DB::table('payment_requests')
            ->where('paypal_webhook_event_id', $eventId)
            ->first();
        if ($existing) {
            return response()->json(['message' => 'already processed'], 200);
        }

        $resourceId = is_array($resource) ? ($resource['id'] ?? null) : null;
        $row = $resourceId
            ? DB::table('payment_requests')->where('paypal_order_id', $resourceId)->first()
            : null;
        if (!$row) {
            return response()->json(['message' => 'no matching row'], 200);
        }

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            DB::table('payment_requests')
                ->where('id', $row->id)
                ->update([
                    'paypal_webhook_event_id' => $eventId,
                    'paypal_processed_at'     => now(),
                ]);
            return response()->json(['message' => 'recorded'], 200);
        }

        if ($eventType === 'PAYMENT.CAPTURE.REFUNDED') {
            Log::info('PayPal capture refunded', [
                'payment_request_id' => $row->id,
                'event_id'           => $eventId,
            ]);
        }

        return response()->json(['message' => 'ignored'], 200);
    }

    /**
     * Invoke a success/failure hook by NAME, but only if the name is
     * on the explicit whitelist (FIX-14, CWE-94).
     */
    private function runWhitelistHookIfPresent(PaymentRequest $row, string $kind): void
    {
        $name = $kind === 'success' ? ($row->success_hook ?? null) : ($row->failure_hook ?? null);
        if (!$name || !in_array($name, self::ALLOWED_HOOKS, true)) {
            Log::warning('PayPal hook rejected by whitelist', [
                'payment_id' => $row->id,
                'kind'       => $kind,
                'hook'       => $name,
            ]);
            return;
        }
        if (!function_exists($name)) {
            Log::warning('PayPal hook not defined', [
                'payment_id' => $row->id,
                'kind'       => $kind,
                'hook'       => $name,
            ]);
            return;
        }
        $name($row);
    }
}
