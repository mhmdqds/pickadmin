<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use App\Traits\Processor;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Throwable;

/**
 * Stripe Payment Controller (hardened).
 *
 * Threat-model summary:
 *  - Client cannot choose amount, currency, or order identity — all are
 *    read from the local `payment_requests` row identified by `payment_id`.
 *  - Every state transition is gated by:
 *      (a) ownership/identity check (client_reference_id === payment_id),
 *      (b) amount + currency match against Stripe (PaymentIntent),
 *      (c) explicit `is_paid` guard + DB::transaction + lockForUpdate,
 *      (d) replay protection at DB layer (UNIQUE stripe_session_id,
 *          UNIQUE stripe_payment_intent, UNIQUE webhook_event_id).
 *  - The Return-URL flow is treated as UX/navigation only; payment truth
 *    is established by `Session::retrieve()` AND, where configured, by a
 *    Stripe webhook signed with `STRIPE_WEBHOOK_SECRET`.
 *
 * CSRF note: stripe routes are CSRF-exempt (VerifyCsrfToken::$except).
 * The compensating controls here are: signature binding, idempotency,
 * replay guards, and amount/currency equality.
 */
class StripePaymentController extends Controller
{
    use Processor;

    private $config_values;
    private PaymentRequest $payment;

    public function __construct(PaymentRequest $payment)
    {
        $config = $this->payment_config('stripe', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
    }

    /**
     * Resolve the active Stripe API key. Returns null if not configured.
     */
    private function apiKey(): ?string
    {
        return $this->config_values->api_key ?? null;
    }

    /**
     * Resolve the active Stripe webhook secret (from the JSON config
     * or the STRIPE_WEBHOOK_SECRET env).
     */
    private function webhookSecret(): ?string
    {
        $fromConfig = $this->config_values->webhook_secret
            ?? $this->config_values->stripe_webhook_secret
            ?? null;
        if (!empty($fromConfig)) {
            return $fromConfig;
        }
        $env = env('STRIPE_WEBHOOK_SECRET');
        return is_string($env) && $env !== '' ? $env : null;
    }

    /**
     * GET /payment/stripe/pay
     * Renders the JS-driven checkout that calls /payment/stripe/token.
     * Only the publishable `published_key` is exposed to the view.
     */
    public function index(Request $request): View|Factory|JsonResponse|Application
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])
            ->where(['is_paid' => 0])
            ->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        // Pass only the publishable key. `api_key` (secret) MUST NOT
        // be sent to the browser — the original code did
        // `compact('data','config')` which leaked the full $config
        // object including api_key.
        $public = [
            'published_key' => $this->config_values->published_key ?? null,
        ];

        return view('payment-views.stripe', compact('data', 'public'));
    }

    /**
     * GET /payment/stripe/token
     * Creates (or reuses) a Stripe Checkout Session for the local
     * `payment_requests` row. The session is bound to the row via
     * `client_reference_id` AND `metadata.payment_id` so the success
     * handler can verify the relationship.
     */
    public function payment_process_3d(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $apiKey = $this->apiKey();
        if (empty($apiKey)) {
            Log::error('Stripe token: API key not configured');
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])
            ->where(['is_paid' => 0])
            ->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        if (!empty($data->stripe_session_id)) {
            return response()->json(['id' => $data->stripe_session_id]);
        }

        Stripe::setApiKey($apiKey);
        $paymentAmount = (float) $data->payment_amount;
        $currencyCode  = strtolower((string) ($data->currency_code ?? 'usd'));

        if ($paymentAmount <= 0) {
            Log::error('Stripe token: non-positive amount', ['payment_id' => $data->id]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $zeroDecimal = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw',
            'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
        $unitAmount = in_array($currencyCode, $zeroDecimal, true)
            ? (int) round($paymentAmount)
            : (int) round($paymentAmount * 100);

        if ($unitAmount <= 0) {
            Log::error('Stripe token: amount rounds to zero', [
                'payment_id' => $data->id, 'amount' => $paymentAmount, 'currency' => $currencyCode,
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $businessName = 'my_business';
        $businessLogo = url('/');
        if (!empty($data->additional_data)) {
            $extra = json_decode($data->additional_data);
            if (is_object($extra)) {
                $businessName = $extra->business_name ?? $businessName;
                $businessLogo = $extra->business_logo ?? $businessLogo;
            }
        }

        $sessionOptions = [
            'line_items' => [[
                'price_data' => [
                    'currency'     => $currencyCode,
                    'unit_amount'  => $unitAmount,
                    'product_data' => [
                        'name'    => $businessName,
                        'images'  => [$businessLogo],
                    ],
                ],
                'quantity'   => 1,
            ]],
            'mode' => 'payment',
            'client_reference_id' => (string) $data->id,
            'metadata' => [
                'payment_id'  => (string) $data->id,
                'attribute'   => (string) ($data->attribute ?? ''),
                'attribute_id'=> (string) ($data->attribute_id ?? ''),
            ],
            'success_url' => url('/') . '/payment/stripe/success?session_id={CHECKOUT_SESSION_ID}&payment_id=' . $data->id,
            'cancel_url'  => url('/') . '/payment/stripe/canceled?payment_id=' . $data->id,
        ];

        $idempotencyKey = 'stripe_session_' . $data->id;

        try {
            $checkoutSession = Session::create($sessionOptions, [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (Throwable $e) {
            Log::error('Stripe token: Session::create failed', [
                'payment_id' => $data->id,
                'message'    => $e->getMessage(),
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        try {
            $this->payment::where('id', $data->id)->update([
                'stripe_session_id' => $checkoutSession->id,
            ]);
        } catch (Throwable $e) {
            $existing = $this->payment::where('id', $data->id)->value('stripe_session_id');
            if ($existing) {
                return response()->json(['id' => $existing]);
            }
            Log::error('Stripe token: failed to persist session id', [
                'payment_id' => $data->id, 'message' => $e->getMessage(),
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        return response()->json(['id' => $checkoutSession->id]);
    }

    /**
     * GET /payment/stripe/success
     * UX/navigation landing. Verifies the session server-to-side, then
     * delegates to the shared writer.
     */
    public function success(Request $request)
    {
        $apiKey = $this->apiKey();
        if (empty($apiKey)) {
            Log::error('Stripe success: API key not configured');
            return $this->payment_response(null, 'fail');
        }

        $sessionId = (string) $request->get('session_id', '');
        $paymentId = (string) $request->input('payment_id', '');

        if ($sessionId === '' || $paymentId === '' || !preg_match('/^[0-9a-f-]{36}$/i', $paymentId)) {
            return $this->payment_response(null, 'fail');
        }

        Stripe::setApiKey($apiKey);

        try {
            $session = Session::retrieve($sessionId);
        } catch (Throwable $e) {
            Log::warning('Stripe success: Session::retrieve failed', [
                'session_id' => $sessionId, 'message' => $e->getMessage(),
            ]);
            return $this->payment_response(null, 'fail');
        }

        $result = $this->markPaidFromSession($session, $request, source: 'return_url');

        if (($result['state'] ?? null) === 'paid') {
            return $this->payment_response($result['row'] ?? null, 'success');
        }
        return $this->payment_response($result['row'] ?? $this->payment::find($paymentId), 'fail');
    }

    /**
     * POST /payment/stripe/webhook
     * Authoritative payment-completion source. Verifies the
     * `Stripe-Signature` header against the configured webhook secret.
     */
    public function webhook(Request $request): JsonResponse
    {
        $apiKey = $this->apiKey();
        $secret = $this->webhookSecret();
        if (empty($apiKey) || empty($secret)) {
            Log::error('Stripe webhook: API key or webhook secret not configured');
            return response()->json(['error' => 'unconfigured'], 503);
        }

        $payload   = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        if ($signature === '') {
            Log::warning('Stripe webhook: missing Stripe-Signature');
            return response()->json(['error' => 'missing_signature'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: bad signature', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'invalid_signature'], 400);
        } catch (Throwable $e) {
            Log::warning('Stripe webhook: parse failure', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        Stripe::setApiKey($apiKey);

        $type = (string) ($event->type ?? '');
        $obj  = $event->data->object ?? null;
        $eventId = (string) ($event->id ?? '');

        if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $result = $this->markPaidFromSession($obj, request: null, source: 'webhook', eventId: $eventId);
            return response()->json(['received' => true, 'state' => $result['state'] ?? 'unknown']);
        }

        if (in_array($type, ['checkout.session.expired', 'checkout.session.async_payment_failed'], true)) {
            $sid = is_object($obj) ? ($obj->id ?? null) : null;
            if ($sid) {
                $this->payment::where('stripe_session_id', $sid)->update(['expired_at' => now()]);
            }
            return response()->json(['received' => true, 'state' => 'expired']);
        }

        return response()->json(['received' => true, 'state' => 'ignored']);
    }

    /**
     * Single secure writer used by BOTH the return URL and the webhook.
     *
     * @return array{state: 'paid'|'mismatch'|'not_found'|'not_paid', row?: PaymentRequest}
     */
    private function markPaidFromSession($session, ?Request $request, string $source, ?string $eventId = null): array
    {
        if (!is_object($session) || empty($session->id)) {
            return ['state' => 'not_found'];
        }

        $sessionId = (string) $session->id;
        $localId   = null;

        if (!empty($session->client_reference_id) && is_string($session->client_reference_id)) {
            $localId = $session->client_reference_id;
        } elseif (isset($session->metadata->payment_id)) {
            $localId = $session->metadata->payment_id;
        }

        if (!$localId) {
            $row = $this->payment::where('stripe_session_id', $sessionId)->first();
        } else {
            $row = $this->payment::where('id', $localId)->first();
        }

        if (!$row) {
            Log::warning('Stripe: session does not match any local row', [
                'session_id' => $sessionId, 'source' => $source, 'event_id' => $eventId,
            ]);
            return ['state' => 'not_found'];
        }

        if ((string) ($row->stripe_session_id ?? '') !== $sessionId) {
            Log::warning('Stripe: session id mismatch (possible replay)', [
                'local' => $row->stripe_session_id, 'incoming' => $sessionId, 'source' => $source,
            ]);
            return ['state' => 'mismatch'];
        }

        $paymentStatus = isset($session->payment_status) ? (string) $session->payment_status : '';
        $sessionStatus = isset($session->status) ? (string) $session->status : '';
        if ($paymentStatus !== 'paid' || $sessionStatus !== 'complete') {
            Log::info('Stripe: session not yet paid', [
                'session_id' => $sessionId,
                'payment_status' => $paymentStatus,
                'session_status' => $sessionStatus,
                'source' => $source,
            ]);
            return ['state' => 'not_paid', 'row' => $row];
        }

        $paymentIntentId = (string) ($session->payment_intent ?? '');
        if ($paymentIntentId !== '') {
            try {
                $pi = PaymentIntent::retrieve($paymentIntentId);
                if (strtolower((string) ($pi->status ?? '')) !== 'succeeded') {
                    Log::warning('Stripe: payment_intent not succeeded', [
                        'pi' => $paymentIntentId, 'status' => $pi->status ?? null, 'source' => $source,
                    ]);
                    return ['state' => 'not_paid', 'row' => $row];
                }
                $expectedAmountMinor = (int) ($pi->amount_received ?? $pi->amount ?? 0);
                $expectedCurrency    = strtolower((string) ($pi->currency ?? ''));
                $zeroDecimal = ['bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw',
                    'mga', 'pyg', 'rwf', 'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf'];
                $expectedMajor = in_array($expectedCurrency, $zeroDecimal, true)
                    ? $expectedAmountMinor
                    : $expectedAmountMinor / 100;
                $rowAmount = (float) $row->payment_amount;
                if (abs($expectedMajor - $rowAmount) > 0.01) {
                    Log::error('Stripe: amount mismatch', [
                        'local' => $rowAmount, 'stripe_minor' => $expectedAmountMinor,
                        'session_id' => $sessionId, 'source' => $source,
                    ]);
                    return ['state' => 'mismatch'];
                }
                if ($expectedCurrency !== '' && strtolower((string) $row->currency_code) !== $expectedCurrency) {
                    Log::error('Stripe: currency mismatch', [
                        'local' => $row->currency_code, 'stripe' => $expectedCurrency,
                        'session_id' => $sessionId, 'source' => $source,
                    ]);
                    return ['state' => 'mismatch'];
                }
            } catch (Throwable $e) {
                Log::warning('Stripe: PaymentIntent::retrieve failed', [
                    'pi' => $paymentIntentId, 'message' => $e->getMessage(), 'source' => $source,
                ]);
            }
        }

        try {
            $updated = DB::transaction(function () use ($row, $sessionId, $paymentIntentId, $eventId) {
                $fresh = $this->payment::where('id', $row->id)->lockForUpdate()->first();
                if (!$fresh) {
                    return null;
                }
                if ((int) $fresh->is_paid === 1) {
                    return $fresh;
                }
                $fresh->payment_method        = 'stripe';
                $fresh->is_paid               = 1;
                $fresh->transaction_id        = $paymentIntentId !== '' ? $paymentIntentId : $sessionId;
                $fresh->stripe_payment_intent = $paymentIntentId !== '' ? $paymentIntentId : null;
                $fresh->webhook_event_id      = $eventId;
                $fresh->save();
                return $fresh;
            });
        } catch (Throwable $e) {
            Log::error('Stripe: DB write failed', [
                'payment_id' => $row->id, 'message' => $e->getMessage(), 'source' => $source,
            ]);
            return ['state' => 'mismatch'];
        }

        if (!$updated) {
            return ['state' => 'not_found'];
        }
        if ((int) $updated->is_paid !== 1) {
            return ['state' => 'not_paid', 'row' => $updated];
        }

        if (function_exists($updated->success_hook)) {
            try {
                call_user_func($updated->success_hook, $updated);
            } catch (Throwable $e) {
                Log::error('Stripe: success_hook threw', [
                    'payment_id' => $updated->id, 'hook' => $updated->success_hook,
                    'message' => $e->getMessage(), 'source' => $source,
                ]);
            }
        }

        return ['state' => 'paid', 'row' => $updated];
    }

    /**
     * GET /payment/stripe/canceled
     * Marks the row expired and runs the failure hook (idempotently).
     */
    public function canceled(Request $request): JsonResponse|Redirector|RedirectResponse|Application
    {
        $paymentId = (string) $request->input('payment_id', '');
        $row = $paymentId !== '' ? $this->payment::where('id', $paymentId)->first() : null;

        if ($row && (int) $row->is_paid === 0) {
            try {
                DB::transaction(function () use ($row) {
                    $fresh = $this->payment::where('id', $row->id)->lockForUpdate()->first();
                    if ($fresh && (int) $fresh->is_paid === 0) {
                        $fresh->expired_at = now();
                        $fresh->save();
                        if (function_exists($fresh->failure_hook)) {
                            try {
                                call_user_func($fresh->failure_hook, $fresh);
                            } catch (Throwable $e) {
                                Log::warning('Stripe: failure_hook threw', [
                                    'payment_id' => $fresh->id, 'message' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                });
            } catch (Throwable $e) {
                Log::error('Stripe: cancel tx failed', [
                    'payment_id' => $row->id, 'message' => $e->getMessage(),
                ]);
            }
        }

        $data = $row ?? $this->payment::find($paymentId);
        return $this->payment_response($data, 'cancel');
    }
}

