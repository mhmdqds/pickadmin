<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\PaymentRequest;
use App\Traits\Processor;

class FlutterwaveV3Controller extends Controller
{
    use Processor;

    private $config_values;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('flutterwave', 'payment_config');
        if (!is_null($config) && $config->mode == 'live') {
            $this->config_values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $this->config_values = json_decode($config->test_values);
        }
        $this->payment = $payment;
        $this->user = $user;
    }

    /**
     * Common cURL options for Flutterwave API calls.
     * CWE-295 / M-2: TLS verification enforced.
     */
    private function flutterwaveCurlOptions(string $url, string $method = 'POST', array $headers = [], array $body = [], bool $isJson = true): array
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
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ];
        $opts[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        if ($isJson) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body);
        } else {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($body);
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        return $opts;
    }

    public function initialize(Request $request)
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
        $payer = json_decode($data['payer_information']);

        // Prepare rave request
        $request_body = [
            'tx_ref' => (string) time(),
            'amount' => $data->payment_amount,
            'currency' => $data->currency_code ?? 'NGN',
            'payment_options' => 'card',
            'redirect_url' => route('flutterwave-v3.callback', ['payment_id' => $data->id]),
            'customer' => [
                'email' => $payer->email,
                'name' => $payer->name
            ],
            'meta' => [
                'price' => $data->payment_amount
            ],
            'customizations' => [
                'title' => $business_name,
                'description' => $data->id
            ]
        ];

        // CWE-295 / M-2: TLS verification enforced via flutterwaveCurlOptions()
        $ch = curl_init();
        curl_setopt_array($ch, $this->flutterwaveCurlOptions(
            'https://api.flutterwave.com/v3/payments',
            'POST',
            [
                'Authorization: Bearer ' . $this->config_values->secret_key,
                'Content-Type: application/json'
            ],
            $request_body,
            true
        ));

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            Log::error('Flutterwave initialize failed', [
                'http' => $httpCode,
                'curl_error' => $err,
            ]);
            return 'We can not process your payment';
        }

        $res = json_decode($response);
        if (isset($res->status) && $res->status == 'success' && isset($res->data->link)) {
            return redirect()->away($res->data->link);
        }

        Log::error('Flutterwave initialize returned non-success', [
            'http' => $httpCode,
            'body' => $response,
        ]);
        return 'We can not process your payment';
    }

    public function callback(Request $request)
    {
        $status = $request->input('status');
        $txid   = $request->input('transaction_id');

        // Server-to-side verification (don't trust client status flag)
        $ch = curl_init();
        curl_setopt_array($ch, $this->flutterwaveCurlOptions(
            "https://api.flutterwave.com/v3/transactions/{$txid}/verify",
            'GET',
            [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->config_values->secret_key,
            ],
            [],
            true
        ));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            Log::error('Flutterwave verify failed', [
                'http' => $httpCode,
                'curl_error' => $err,
            ]);
            $payment_data = $this->payment::where('id', $request->input('payment_id'))->first();
            if ($payment_data && function_exists($payment_data->failure_hook)) {
                call_user_func($payment_data->failure_hook, $payment_data);
            }
            return $this->payment_response($payment_data, 'fail');
        }

        $res = json_decode($response);
        if (isset($res->status) && $res->status && isset($res->data->charged_amount, $res->data->meta->price)) {
            $amountPaid = $res->data->charged_amount;
            $amountToPay = $res->data->meta->price;
            if ($amountPaid >= $amountToPay) {
                $this->payment::where(['id' => $request->input('payment_id')])->update([
                    'payment_method' => 'flutterwave',
                    'is_paid'        => 1,
                    'transaction_id' => $txid,
                ]);

                $data = $this->payment::where(['id' => $request->input('payment_id')])->first();
                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }
                return $this->payment_response($data, 'success');
            }
        }

        $payment_data = $this->payment::where('id', $request->input('payment_id'))->first();
        if ($payment_data && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }
}
