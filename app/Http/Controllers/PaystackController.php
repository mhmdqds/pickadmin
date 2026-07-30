<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentRequest;
use App\Traits\Processor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;

class PaystackController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paystack', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey'    => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey'    => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl'   => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail'=> env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('paystack', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    /**
     * Common cURL options for Paystack API calls.
     * All Paystack calls MUST verify TLS certificates (CWE-295 fix: M-2).
     */
    private function paystackCurlOptions(string $url, string $method = 'GET', array $headers = [], array $body = []): array
    {
        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_ENCODING      => '',
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ];
        if (strtoupper($method) === 'POST') {
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
        } else {
            $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            if (!empty($body)) {
                $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
            }
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        return $opts;
    }

    private function paystackRequest(string $url, string $method = 'GET', array $headers = [], array $body = [])
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->paystackCurlOptions($url, $method, $headers, $body));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [
            'status'   => $response !== false && $httpCode === 200,
            'http'     => $httpCode,
            'body'     => $response,
            'error'    => $err,
            'decoded'  => $response ? json_decode($response, true) : null,
        ];
    }

    public function index(Request $request): JsonResponse|Redirector|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter($this->getGatewayResponse(type: 'GATEWAYS_DEFAULT_400'), null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter($this->getGatewayResponse(type: 'GATEWAYS_DEFAULT_204')), 200);
        }

        $payer = json_decode($data['payer_information'], true);

        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email'       => $payer['email'] ?? "customer@email.com",
            'amount'      => ($data['payment_amount'] ?? 0) * 100,
            'currency'    => $data['currency_code'] ?? 'XOF',
            'reference'   => (string)('REF' . time() . 'RANDOM'),
            'callback_url'=> route('paystack.callback', ['payment_id' => $data['id']]),
            'metadata'    => [
                'payment_id' => $data['id'],
            ]
        ];

        $result = $this->paystackRequest(
            $url,
            'POST',
            [
                'Authorization: Bearer ' . Config::get('paystack.secretKey'),
                'Cache-Control: no-cache',
            ],
            $fields
        );

        if (!$result['status']) {
            Log::error('Paystack initialize failed', [
                'payment_id' => $request['payment_id'],
                'http'       => $result['http'],
                'curl_error' => $result['error'],
            ]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $response = $result['decoded'] ?? [];
        if (isset($response['status']) && $response['status'] && isset($response['data']['authorization_url'])) {
            return redirect($response['data']['authorization_url']);
        }

        return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
    }

    public function handleGatewayCallback(Request $request): Redirector|RedirectResponse
    {
        $paymentDetails = self::getPayStackPaymentData(request: $request);

        // Server-to-side validation (don't trust client payload)
        if (!is_array($paymentDetails) || ($paymentDetails['status'] ?? null) !== true) {
            Log::warning('Paystack callback: paymentDetails status not true', [
                'details' => $paymentDetails,
            ]);
            return redirect()->route('payment-fail');
        }

        $metadataPaymentId = data_get($paymentDetails, 'data.metadata.payment_id');
        if (!$metadataPaymentId) {
            Log::warning('Paystack callback: missing metadata.payment_id');
            return redirect()->route('payment-fail');
        }

        $this->payment::where(['id' => $metadataPaymentId])->update([
            'payment_method' => 'paystack',
            'is_paid'        => 1,
            'transaction_id' => $request['trxref'] ?? data_get($paymentDetails, 'data.reference'),
        ]);

        $data = $this->payment::where(['id' => $metadataPaymentId])->first();
        if (isset($data) && function_exists($data->success_hook)) {
            call_user_func($data->success_hook, $data);
        }
        return $this->payment_response($data, 'success');
    }

    public function cancel(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        $payment_data = $this->payment::where(['id' => $request['payments_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    /**
     * Verify a Paystack transaction server-to-side.
     * CWE-295 / M-2: TLS verification enforced via paystackCurlOptions().
     */
    protected function getPayStackPaymentData(object|array $request): array
    {
        $reference = $request->query('reference');
        if (!$reference) {
            return [];
        }

        $result = $this->paystackRequest(
            "https://api.paystack.co/transaction/verify/{$reference}",
            'GET',
            [
                'Authorization: Bearer ' . Config::get('paystack.secretKey'),
                'Cache-Control: no-cache',
            ]
        );

        if (!$result['status']) {
            Log::error('Paystack verify failed', [
                'http'       => $result['http'],
                'curl_error' => $result['error'],
            ]);
            return [];
        }

        return is_array($result['decoded']) ? $result['decoded'] : [];
    }
}
