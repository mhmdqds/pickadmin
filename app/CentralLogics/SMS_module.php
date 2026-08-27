<?php

namespace App\CentralLogics;

use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;

class SMS_module
{
    public static function send($receiver, $otp)
    {
        $config = self::get_settings('twilio');
        if (isset($config) && $config['status'] == 1) {
            return self::twilio($receiver, $otp);
        }

        $config = self::get_settings('nexmo');
        if (isset($config) && $config['status'] == 1) {
            return self::nexmo($receiver, $otp);
        }

        $config = self::get_settings('2factor');
        if (isset($config) && $config['status'] == 1) {
            return self::two_factor($receiver, $otp);
        }

        $config = self::get_settings('msg91');
        if (isset($config) && $config['status'] == 1) {
            return self::msg_91($receiver, $otp);
        }
        $config = self::get_settings('alphanet_sms');
        if (isset($config) && $config['status'] == 1) {
            return self::alphanet_sms($receiver, $otp);
        }

        $config = self::get_settings('message_central');
        if (isset($config) && $config['status'] == 1) {
            return self::message_central($receiver, $otp);
        }

        return 'not_found';
    }

    public static function twilio($receiver, $otp): string
    {
        $config = self::get_settings('twilio');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $sid = $config['sid'];
            $token = $config['token'];
            try {
                $twilio = new Client($sid, $token);
                $twilio->messages
                    ->create($receiver, // to
                        array(
                            "messagingServiceSid" => $config['messaging_service_sid'],
                            "body" => $message
                        )
                    );
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function nexmo($receiver, $otp): string
    {
        $config = self::get_settings('nexmo');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            try {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://rest.nexmo.com/sms/json');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "from=".$config['from']."&text=".$message."&to=".$receiver."&api_key=".$config['api_key']."&api_secret=".$config['api_secret']);

                $headers = array();
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                curl_close($ch);
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }
        public static function two_factor($receiver, $otp): string
    {
        $config = self::get_settings('2factor');
        $response = 'error';

        if (isset($config) && $config['status'] == 1) {
            $api_key = $config['api_key'];
            $otp_template = $config['otp_template'] ?? 'Your OTP is: #OTP#';

            $apiUrl = sprintf(
                'https://2factor.in/API/V1/%s/SMS/%s/%s/%s',
                urlencode($api_key),
                urlencode($receiver),
                urlencode($otp),
                urlencode($otp_template)
            );

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ($err) {
                $response = $err;
            } else {
                $response = 'success';
            }
        }

        return $response;
    }


    public static function msg_91($receiver, $otp): string
    {
        $config = self::get_settings('msg91');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $curl = curl_init();
            // $message = str_replace("#OTP#", $otp, $config['otp_template']);
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/v5/otp?template_id=" . $config['template_id'] . "&mobile=" . $receiver . "&authkey=" . $config['auth_key'] . "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{\"OTP\":\"$otp\"}",
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                ),
            ));

            // curl_setopt_array($curl, [
            //     CURLOPT_URL => "https://control.msg91.com/api/v5/flow",
            //     CURLOPT_RETURNTRANSFER => true,
            //     CURLOPT_ENCODING => "",
            //     CURLOPT_MAXREDIRS => 10,
            //     CURLOPT_TIMEOUT => 30,
            //     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            //     CURLOPT_CUSTOMREQUEST => "POST",
            //     CURLOPT_POSTFIELDS => json_encode([
            //         "template_id" => $config['template_id'],
            //         "short_url" => "0", // Set to "1" (On) or "0" (Off) as needed
            //         "short_url_expiry" => "3600", // Replace with expiry time in seconds (optional)
            //         "realTimeResponse" => "0",
            //         "recipients" => [
            //             [
            //                 "mobiles" => $receiver,
            //                 "OTP" => $otp,
            //                 // "VAR1" => $var // Replace with your second variable value, if any
            //             ]
            //         ]
            //     ]),
            //     CURLOPT_HTTPHEADER => [
            //         "accept: application/json",
            //         "authkey: " . $config['auth_key'],
            //         "content-type: application/json"
            //     ],
            // ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function alphanet_sms($receiver, $otp ,$message = null): string
    {
        $config = self::get_settings('alphanet_sms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            if($message ==  null){
                $message = str_replace("#OTP#", $otp, $config['otp_template']);
            }

            $receiver = str_replace("+", "", $receiver);
            $api_key = $config['api_key'];
            $sender_id = $config['sender_id'] ?? null;


            $postfields = array(
                'api_key' => $api_key,
                'msg' => $message,
                'to' => $receiver
            );

            if ($sender_id) {
                $postfields['sender_id'] = $sender_id;
            }


            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.sms.net.bd/sendsms',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $postfields,
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if ((int) data_get(json_decode($response,true),'error') === 0) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }




    public static function get_settings($name)
    {
        $config = DB::table('addon_settings')->where('key_name', $name)
        ->where('settings_type', 'sms_config')->first();

        if (isset($config) && !is_null($config->live_values)) {
            return json_decode($config->live_values, true);
        }
        return null;
    }

    /**
     * Send OTP via Message Central (Message Now API).
     *
     * Verified working endpoint (per production Postman screenshots):
     *   POST https://cpaas.messagecentral.com/verification/v3/send
     *     ?countryCode=1
     *     &customerId=C-D1E333296E22496
     *     &flowType=SMS
     *     &type=OTP
     *     &mobileNumber=3478732224
     *     &message=Your otp for verification is 787878
     *     &otpLength=6
     *     [&senderId=PickAdmin]
     *     [&messageType=OTP]
     *     [&templateId=...]
     *     [&entityId=...]
     *   Headers:
     *     authToken: <JWT>
     *     Content-Type: application/x-www-form-urlencoded  (or application/json)
     *
     * The authToken is sent as a HEADER (not in the URL), even though Postman
     * displays it inside the request URL bar — that's a Postman display quirk
     * for header values that are very long; the wire request is POST + query
     * + header.
     *
     * PickAdmin owns the OTP value and substitutes #OTP# in the template.
     * Mirrors App\Traits\SmsGateway::message_central for the legacy dispatch
     * path used when the Gateways addon module is disabled.
     */
    public static function message_central($receiver, $otp): string
    {
        $config = self::get_settings('message_central');
        $response = 'error';

         \Log::info('OTP provider $receiver', [
                '$receiver'       => $receiver,
            ]);
        if (isset($config) && (int) ($config['status'] ?? 0) === 1) {
            $customer_id  = $config['customer_id']  ?? null;
            $auth_token   = $config['auth_token']   ?? null;
            $country_code = $config['country_code'] ?? null;
            $otp_template = $config['otp_template'] ?? 'Your verification code is #OTP#';

            // senderId is hard-coded for all Message Central accounts in this
            // integration. Operator does not see or set this field.
            $sender_id = 'UTOMOB';

            // OTP length is hard-coded to 6 for this integration. The Message
            // Central Message Now API requires otpLength, but PickAdmin owns
            // the OTP value (substituted into the message body), so the OTP
            // length is purely informational from the API's perspective.
            $otp_length = 6;

            $message_type = $config['message_type'] ?? null;
            $template_id  = $config['template_id']  ?? null;
            $entity_id    = $config['entity_id']    ?? null;

            if (empty($customer_id) || empty($auth_token) || empty($country_code)) {
                return 'error';
            }

            // Phone normalization:
            //   1. Strip '+' and any non-digit characters.
            //   2. Strip the leading country code if present, so the mobile
            //      number does NOT contain the country code prefix.
            // Examples (with countryCode="1"):
            //   "+1 347 873 2224"  → "3478732224"
            //   "13478732224"      → "3478732224"
            //   "3478732224"       → "3478732224"
            //   "+91 98765 43210"  → "9876543210"  (with countryCode="91")
            $country_code_clean = ltrim((string) $country_code, '+');
            $mobile = preg_replace('/[^0-9]/', '', (string) $receiver);
            if ($country_code_clean !== '' && strpos($mobile, $country_code_clean) === 0) {
                $mobile = substr($mobile, strlen($country_code_clean));
            }
            if ($mobile === '') {
                return 'error';
            }

            // PickAdmin owns the OTP value.
            $message = str_replace('#OTP#', $otp, $otp_template);
            $result['country_code'] = $country_code_clean;

            // Build the QUERY STRING. Message Central accepts the parameters
            // either in the URL query string OR in a form-urlencoded body —
            // we use the URL form to match the working Postman call exactly.
            $query = [
                'countryCode'  => ltrim((string) $country_code, '+'),
                'customerId'   => $customer_id,
                'flowType'     => 'SMS',
                'type'         => 'OTP',
                'mobileNumber' => $mobile,
                'message'      => $message,
                'otpLength'    => 6,
            ];
            if (!empty($sender_id))    { $query['senderId']    = $sender_id; }
            if (!empty($message_type)) { $query['messageType'] = $message_type; }
            if (!empty($template_id))  { $query['templateId']  = $template_id; }
            if (!empty($entity_id))    { $query['entityId']    = $entity_id; }


            $url = 'https://cpaas.messagecentral.com/verification/v3/send?countryCode=' . $country_code_clean .
                '&customerId=' . $customer_id .
                '&flowType=SMS&type=OTP&otpLength=6&senderId=UTOMOB&mobileNumber=' . $mobile .
                '&message=' . urlencode($message);
            // authToken is sent as an HTTP HEADER (per Postman Headers tab),
            // NOT as part of the URL — even though Postman's URL bar visually
            // concatenates the header for display when the token is long.
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => '',
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'authToken: ' . $auth_token,
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            ]);

            $raw  = curl_exec($curl);
            $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $err  = curl_error($curl);
            curl_close($curl);
               
            if ($raw === false || !empty($err)) {
                return 'error';
            }

            $decoded      = json_decode((string) $raw, true);
            $responseCode = is_array($decoded) ? ($decoded['responseCode'] ?? null) : null;
            $messageCode  = is_array($decoded) ? ($decoded['message'] ?? null) : null;

            if ((int) $http === 200 && ((string) $responseCode === '200' || strcasecmp((string) $messageCode, 'SUCCESS') === 0 || strcasecmp((string) $messageCode, 'success') === 0)) {
                $response = 'success';

    $data = $decoded['data'] ?? [];
    
    $verificationId = $data['verificationId'] ?? null;
    
    if (!empty($verificationId)) {
    
        DB::table('phone_verifications')->updateOrInsert(
    
            [
                'phone' => $receiver
            ],
    
            [
                'token'            => $otp,
                'verification_id'  => $verificationId,
                'transaction_id'   => $data['transactionId'] ?? null,
                'reference_id'     => $data['referenceId'] ?? null,
                'flow_type'        => $data['flowType'] ?? 'SMS',
                'is_verified'      => 0,
                'verified_at'      => null,
                'updated_at'       => now(),
    
                // إذا كان السجل جديد
                'created_at'       => now(),
            ]
        );
    
        $result['verification_id'] = $verificationId;
        $result['transaction_id'] = $data['transactionId'] ?? null;
        $result['reference_id'] = $data['referenceId'] ?? null;
        $result['flow_type'] = $data['flowType'] ?? 'SMS';
    }
            } else {
                $safeError = is_array($decoded) ? ($decoded['errorMessage'] ?? $decoded['message'] ?? 'unknown_error') : 'invalid_response';
                try {
                    if (function_exists('info')) {
                        info('MessageCentral SMS send failed: ' . $safeError);
                    }
                } catch (\Throwable $t) {
                    // ignore logging failures
                }
                $response = 'error';
            }
        }

        return $response;
    }
}
