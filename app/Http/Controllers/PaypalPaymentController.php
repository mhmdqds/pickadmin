<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Traits\Processor;
use App\Models\PaymentRequest;

class PaypalPaymentController extends Controller
{
    use Processor;

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
     * Responds with a welcome message with instructions
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

        if ($data['additional_data'] != null) {
            $business = json_decode($data['additional_data']);
            $business_name = $business->business_name ?? "my_business";
        } else {
            $business_name = "my_business";
        }

        $accessToken = json_decode($this->token(), true);

        if (isset($accessToken['access_token'])) {
            $accessToken = $accessToken['access_token'];
            $payment_data = [];
            $payment_data['purchase_units'] = [
                [
                    'reference_id' => $data->id,
                    'name' => $business_name,
                    'desc'  => 'payment ID :' . $data->id,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => $data->payment_amount * 100
                    ]
                ]
            ];

            $payment_data['invoice_id'] = $data->id;
            $payment_data['invoice_description'] = "Order #{$payment_data['invoice_id']} Invoice";
            $payment_data['total'] = $data->payment_amount * 100;
            $payment_data['intent'] = 'CAPTURE';
            $payment_data['application_context'] = [
                'return_url' => route('paypal.success', ['payment_id' => $data->id]),
                'cancel_url' => route('paypal.cancel', ['payment_id' => $data->id])
            ];

            // Build cURL with TLS verification (CWE-295 / M-2 fix)
            $ch = curl_init();
            curl_setopt_array($ch, $this->paypalCurlOptions(
                $this->base_url . '/v2/checkout/orders',
                'POST',
                [
                    'Content-Type: application/json',
                    "Authorization: Bearer $accessToken",
                    'Paypal-Request-Id:' . Str::uuid(),
                ],
                $payment_data
            ));
            // Override POST fields to use JSON
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                "Authorization: Bearer $accessToken",
                'Paypal-Request-Id:' . Str::uuid(),
            ]);

            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                \Log::error('PayPal create-order error', ['curl_error' => curl_error($ch)]);
            }
            curl_close($ch);
        } else {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $response = json_decode($response);

        if (!isset($response->links) || !is_array($response->links) || count($response->links) < 2) {
            \Log::error('PayPal create-order returned malformed body', ['body' => $response]);
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $links = $response->links;
        return Redirect::away($links[1]->href);
    }

    /**
     * Responds with a welcome message with instructions
     */
    public function cancel(Request $request)
    {
        $data = $this->payment::where(['id' => $request['payment_id']])->first();
        return $this->payment_response($data, 'cancel');
    }

    /**
     * Responds with a welcome message with instructions
     */
    public function success(Request $request)
    {
        $accessToken = json_decode($this->token(), true);
        $accessToken = $accessToken['access_token'];

        // Build cURL with TLS verification (CWE-295 / M-2 fix)
        $ch = curl_init();
        curl_setopt_array($ch, $this->paypalCurlOptions(
            $this->base_url . "/v2/checkout/orders/{$request->token}/capture",
            'POST',
            [
                'Content-Type: application/json',
                "Authorization: Bearer  $accessToken",
                'Paypal-Request-Id:' . Str::uuid(),
            ]
        ));

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            \Log::error('PayPal capture error', ['curl_error' => curl_error($ch)]);
        }
        curl_close($ch);

        $response = json_decode($result);

        if (isset($response->status) && $response->status === 'COMPLETED') {
            $this->payment::where(['id' => $request['payment_id']])->update([
                'payment_method' => 'paypal',
                'is_paid' => 1,
                'transaction_id' => $response->id,
            ]);

            $data = $this->payment::where(['id' => $request['payment_id']])->first();

            if (isset($data) && function_exists($data->success_hook)) {
                call_user_func($data->success_hook, $data);
            }

            return $this->payment_response($data, 'success');
        }

        $payment_data = $this->payment::where(['id' => $request['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
}
