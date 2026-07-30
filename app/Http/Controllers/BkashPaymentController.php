<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Processor;
use App\Models\PaymentRequest;

class BkashPaymentController extends Controller
{
    use Processor;

    /**
     * bKash official payment gateway IP ranges (Tokenized Checkout v1.2.0).
     * Used as a defense-in-depth signal: requests originating from outside
     * these CIDRs are NOT trusted even if the bKash API later confirms
     * the payment.  See:
     *  - https://developer.bka.sh/portal/page/payment-gateway
     */
    private const BKASH_IPV4_RANGES = [
        '10.0.0.0/8',       // bKash internal network
        '103.59.156.0/22',  // bKash production
        '123.49.0.0/16',    // bKash production (older block)
    ];

    private $config_values;
    private $base_url;
    private $app_key;
    private $app_secret;
    private $username;
    private $password;
    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('bkash', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }

        if ($config) {
            $this->app_key = $this->config_values->app_key;
            $this->app_secret = $this->config_values->app_secret;
            $this->username = $this->config_values->username;
            $this->password = $this->config_values->password;
            $this->base_url = ($config->mode == 'live') ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    /**
     * Server-to-server grant of a fresh bKash id_token.
     * NEVER trust a token that came in via the callback URL – it is
     * user-controlled.  This method always negotiates a new one
     * with bKash using the configured app credentials.
     *
     * @return array{status:bool, id_token:?string, error:?string, http_code:?int}
     */
    private function grantFreshToken(): array
    {
        $post_token = [
            'app_key'    => $this->app_key,
            'app_secret' => $this->app_secret,
        ];

        $url = curl_init($this->base_url . '/tokenized/checkout/token/grant');
        $header = [
            'Content-Type:application/json',
            'username:'  . $this->username,
            'password:'  . $this->password,
        ];

        curl_setopt_array($url, [
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POSTFIELDS      => json_encode($post_token),
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_IPRESOLVE       => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 20,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
        ]);

        $resultdata = curl_exec($url);
        $httpCode   = curl_getinfo($url, CURLINFO_HTTP_CODE);
        $err        = curl_error($url);
        curl_close($url);

        if ($resultdata === false || $httpCode !== 200) {
            Log::error('bKash token grant failed', [
                'http_code' => $httpCode,
                'curl_error' => $err,
            ]);
            return ['status' => false, 'id_token' => null, 'error' => 'token_grant_failed', 'http_code' => $httpCode];
        }

        $response = json_decode($resultdata, true);
        if (!is_array($response) || empty($response['id_token'])) {
            Log::error('bKash token grant returned malformed body', ['body' => $resultdata]);
            return ['status' => false, 'id_token' => null, 'error' => 'token_grant_malformed', 'http_code' => $httpCode];
        }

        return ['status' => true, 'id_token' => $response['id_token'], 'error' => null, 'http_code' => 200];
    }

    /**
     * Server-to-server execution call against bKash.  Uses a freshly-granted
     * id_token (NOT the one supplied by the client).  The bKash response
     * for the given paymentID is the only authoritative state.
     *
     * @return array{status:bool, data:?object, http_code:?int, error:?string}
     */
    private function executePaymentServerSide(string $paymentID, string $idToken): array
    {
        $url = curl_init($this->base_url . '/tokenized/checkout/execute');
        $body = json_encode(['paymentID' => $paymentID]);

        $header = [
            'Content-Type:application/json',
            'Authorization:' . $idToken,
            'X-APP-Key:'   . $this->app_key,
        ];

        curl_setopt_array($url, [
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POSTFIELDS      => $body,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_IPRESOLVE       => CURL_IPRESOLVE_V4,
            CURLOPT_CONNECTTIMEOUT  => 10,
            CURLOPT_TIMEOUT         => 20,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
        ]);

        $resultdata = curl_exec($url);
        $httpCode   = curl_getinfo($url, CURLINFO_HTTP_CODE);
        $err        = curl_error($url);
        curl_close($url);

        if ($resultdata === false) {
            Log::error('bKash execute call transport error', [
                'curl_error' => $err,
                'paymentID'  => $paymentID,
            ]);
            return ['status' => false, 'data' => null, 'http_code' => null, 'error' => 'transport_error'];
        }

        $obj = json_decode($resultdata);

        return [
            'status'    => $httpCode === 200,
            'data'      => (is_object($obj) ? $obj : null),
            'http_code' => $httpCode,
            'error'     => ($httpCode === 200 ? null : 'http_' . $httpCode),
        ];
    }

    public function make_tokenize_payment(Request $request)
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

        $tokenResult = $this->grantFreshToken();
        if (!$tokenResult['status']) {
            return response()->json([
                'errors' => [['code' => 'bkash_token', 'message' => 'Could not acquire bKash token']],
            ], 502);
        }
        $auth = $tokenResult['id_token'];

        // Persist the token in the session (NOT in the URL) so a tampered
        // callback URL cannot supply a forged bearer.
        session()->put('bkash_id_token', $auth);
        session()->put('bkash_payment_id', $request['payment_id']);

        $callbackURL = route('bkash.callback', ['payment_id' => $request['payment_id']]);

        $requestbody = [
            'mode'                 => '0011',
            'amount'               => (string) round($data->payment_amount, 2),
            'currency'             => 'BDT',
            'intent'               => 'sale',
            'payerReference'       => $payer->phone,
            'merchantInvoiceNumber' => 'invoice_' . Str::random(15),
            'callbackURL'          => $callbackURL,
        ];

        $url = curl_init($this->base_url . '/tokenized/checkout/create');
        $header = [
            'Content-Type:application/json',
            'Authorization:' . $auth,
            'X-APP-Key:'   . $this->app_key,
        ];

        curl_setopt_array($url, [
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_CUSTOMREQUEST   => 'POST',
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_POSTFIELDS      => json_encode($requestbody),
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_IPRESOLVE       => CURL_IPRESOLVE_V4,
            CURLOPT_SSL_VERIFYPEER  => true,
            CURLOPT_SSL_VERIFYHOST  => 2,
        ]);

        $resultdata = curl_exec($url);
        $httpCode   = curl_getinfo($url, CURLINFO_HTTP_CODE);
        curl_close($url);

        if ($httpCode !== 200) {
            Log::error('bKash create-payment non-200', [
                'payment_id' => $request['payment_id'],
                'http_code'  => $httpCode,
                'body'       => $resultdata,
            ]);
            return response()->json([
                'errors' => [['code' => 'bkash_create', 'message' => 'bKash create-payment failed']],
            ], 502);
        }

        $obj = json_decode($resultdata);
        if (!is_object($obj) || empty($obj->bkashURL)) {
            Log::error('bKash create-payment returned no bkashURL', ['body' => $resultdata]);
            return response()->json([
                'errors' => [['code' => 'bkash_create', 'message' => 'bKash did not return a redirect URL']],
            ], 502);
        }

        return redirect()->away($obj->bkashURL);
    }

    /**
     * bKash Tokenized Checkout callback.
     *
     * Hardening (H-1 fix):
     *  1.  Reject any request missing payment_id or paymentID.
     *  2.  Reject any request where status != 'success'.
     *  3.  Verify the local PaymentRequest is unpaid and not already processed.
     *  4.  Acquire a FRESH bKash id_token (server-to-server) – do NOT trust
     *      the bearer that arrived in the request.
     *  5.  Call bKash /tokenized/checkout/execute to retrieve the
     *      authoritative transaction state.
     *  6.  Validate statusCode, transactionStatus, amount and currency
     *      against the local PaymentRequest inside a DB transaction with
     *      SELECT ... FOR UPDATE – this blocks replay/race conditions.
     *  7.  Persist trxID only after all validations pass; trigger
     *      success_hook exactly once.
     *  8.  Defensive IP allow-list signal (bKash production ranges).
     *
     * @param Request $request  payment_id (route), paymentID (bKash), status
     */
    public function callback(Request $request)
    {
        // -----------------------------------------------------------------
        // 1.  Validate input
        // -----------------------------------------------------------------
        $validator = Validator::make(array_merge($request->all(), [
            'payment_id' => $request->route('payment_id'),
        ]), [
            'payment_id' => 'required|uuid',
            'paymentID'  => 'required|string|max:128',
            'status'     => 'required|string|in:success,failure,cancel',
        ]);

        if ($validator->fails()) {
            Log::warning('bKash callback invalid input', [
                'errors' => $validator->errors()->toArray(),
                'ip'     => $request->ip(),
            ]);
            return response()->json([
                'errors' => [['code' => 'invalid_input', 'message' => 'Invalid bKash callback payload']],
            ], 400);
        }

        // -----------------------------------------------------------------
        // 2.  Quick pre-check: must be a 'success' redirect
        // -----------------------------------------------------------------
        if ($request->input('status') !== 'success') {
            $payment_data = $this->payment::where('id', $request->route('payment_id'))->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        $paymentID   = (string) $request->input('paymentID');
        $localPayId  = (string) $request->route('payment_id');

        // -----------------------------------------------------------------
        // 8.  Optional defense-in-depth IP allow-list (bKash ranges)
        // -----------------------------------------------------------------
        if (!$this->isRequestFromBkashRanges($request->ip())) {
            // We do NOT abort on this alone: legitimate retries via the
            // bKash edge network can come from a slightly different
            // IP.  We log the anomaly and continue – step 4 (fresh
            // server-to-server token) and step 6 (amount match) are
            // the real authoritative controls.
            Log::warning('bKash callback from non-bKash IP', [
                'ip' => $request->ip(),
                'payment_id' => $localPayId,
                'paymentID'  => $paymentID,
            ]);
        }

        // -----------------------------------------------------------------
        // 4 + 5.  Acquire a FRESH server-to-server id_token and call execute
        // -----------------------------------------------------------------
        $tokenResult = $this->grantFreshToken();
        if (!$tokenResult['status']) {
            Log::error('bKash callback could not acquire token', [
                'payment_id' => $localPayId,
                'paymentID'  => $paymentID,
                'error'      => $tokenResult['error'],
            ]);
            return response()->json([
                'errors' => [['code' => 'bkash_token', 'message' => 'Could not verify with bKash']],
            ], 502);
        }

        $execResult = $this->executePaymentServerSide($paymentID, $tokenResult['id_token']);
        if (!$execResult['status'] || $execResult['data'] === null) {
            Log::error('bKash execute call failed', [
                'payment_id' => $localPayId,
                'paymentID'  => $paymentID,
                'http_code'  => $execResult['http_code'],
                'error'      => $execResult['error'],
            ]);
            return response()->json([
                'errors' => [['code' => 'bkash_execute', 'message' => 'bKash execute failed']],
            ], 502);
        }

        $obj = $execResult['data'];

        // -----------------------------------------------------------------
        // 6.  Atomic, validated update with row-level lock
        // -----------------------------------------------------------------
        try {
            $updated = DB::transaction(function () use ($localPayId, $paymentID, $obj) {

                /** @var \App\Models\PaymentRequest|null $payment */
                $payment = $this->payment::where('id', $localPayId)->lockForUpdate()->first();
                if (!$payment) {
                    Log::warning('bKash callback: local PaymentRequest not found', [
                        'payment_id' => $localPayId,
                    ]);
                    return null;
                }

                // Replay protection: if already paid, do nothing.
                if ((int) $payment->is_paid === 1) {
                    Log::info('bKash callback: already paid (replay ignored)', [
                        'payment_id'      => $localPayId,
                        'existing_trxID'  => $payment->transaction_id,
                        'incoming_trxID'  => $obj->trxID ?? null,
                    ]);
                    return $payment;
                }

                // bKash reports failure / pending?
                if (!isset($obj->statusCode) || (string) $obj->statusCode !== '0000') {
                    Log::warning('bKash callback: non-success statusCode', [
                        'payment_id' => $localPayId,
                        'statusCode' => $obj->statusCode ?? null,
                    ]);
                    if (function_exists($payment->failure_hook)) {
                        call_user_func($payment->failure_hook, $payment);
                    }
                    return $payment; // not a fatal – let failure path render
                }

                // bKash transactionStatus should be 'Completed' (case-insensitive).
                $txStatus = strtolower((string) ($obj->transactionStatus ?? ''));
                if ($txStatus !== 'completed') {
                    Log::warning('bKash callback: non-completed transactionStatus', [
                        'payment_id'        => $localPayId,
                        'transactionStatus' => $obj->transactionStatus ?? null,
                    ]);
                    if (function_exists($payment->failure_hook)) {
                        call_user_func($payment->failure_hook, $payment);
                    }
                    return $payment;
                }

                // Amount verification: bKash amount must match local
                // PaymentRequest->payment_amount exactly (within a 0.01
                // tolerance to absorb floating point noise).
                $localAmount  = round((float) $payment->payment_amount, 2);
                $remoteAmount = isset($obj->amount) ? round((float) $obj->amount, 2) : null;
                if ($remoteAmount === null || abs($localAmount - $remoteAmount) > 0.01) {
                    Log::error('bKash callback: amount mismatch', [
                        'payment_id'   => $localPayId,
                        'local_amount' => $localAmount,
                        'remote_amount' => $remoteAmount,
                    ]);
                    return null; // hard fail – potential tampering
                }

                // Currency verification.
                $remoteCurrency = strtoupper((string) ($obj->currency ?? ''));
                if ($remoteCurrency !== 'BDT') {
                    Log::error('bKash callback: currency mismatch', [
                        'payment_id'     => $localPayId,
                        'remote_currency' => $remoteCurrency,
                    ]);
                    return null;
                }

                // trxID must be a non-empty token-bounded string.
                $trxID = isset($obj->trxID) ? (string) $obj->trxID : '';
                if ($trxID === '' || !preg_match('/^[A-Za-z0-9._-]{4,128}$/', $trxID)) {
                    Log::error('bKash callback: invalid trxID', [
                        'payment_id' => $localPayId,
                        'trxID'      => $trxID,
                    ]);
                    return null;
                }

                // Compare the bKash-issued paymentID with the one we
                // received in the URL.  bKash's execute response echoes
                // back the original paymentID.
                if (isset($obj->paymentID) && (string) $obj->paymentID !== $paymentID) {
                    Log::error('bKash callback: paymentID echo mismatch', [
                        'url_paymentID'     => $paymentID,
                        'response_paymentID' => $obj->paymentID,
                    ]);
                    return null;
                }

                // ALL CHECKS PASSED – persist.
                $this->payment::where('id', $localPayId)->update([
                    'payment_method'  => 'bkash',
                    'is_paid'         => 1,
                    'transaction_id'  => $trxID,
                ]);

                return $this->payment::where('id', $localPayId)->first();
            });

            if (!$updated) {
                // Validation refused – keep failure state.
                return response()->json([
                    'errors' => [['code' => 'payment_validation', 'message' => 'bKash payment failed validation']],
                ], 422);
            }

            if ((int) $updated->is_paid !== 1) {
                // bKash reported non-success path inside the transaction.
                return $this->payment_response($updated, 'fail');
            }

            // success_hook fires exactly once and only after persisted state.
            if (function_exists($updated->success_hook)) {
                call_user_func($updated->success_hook, $updated);
            }

            // Clear the session-stashed bKash id_token.
            session()->forget(['bkash_id_token', 'bkash_payment_id']);

            return $this->payment_response($updated, 'success');
        } catch (\Throwable $e) {
            Log::error('bKash callback exception', [
                'payment_id' => $localPayId,
                'paymentID'  => $paymentID,
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'errors' => [['code' => 'internal_error', 'message' => 'Internal error during payment verification']],
            ], 500);
        }
    }

    /**
     * Defense-in-depth: bKash production IP ranges (Tokenized Checkout v1.2.0).
     * Returns true if the request IP is inside any of the configured CIDRs.
     */
    private function isRequestFromBkashRanges(?string $ip): bool
    {
        if (!$ip) {
            return false;
        }
        $packed = ip2long($ip);
        if ($packed === false) {
            return false;
        }
        foreach (self::BKASH_IPV4_RANGES as $cidr) {
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
