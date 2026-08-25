<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Setting;

class SMSModuleController extends Controller
{
    public function sms_index()
    {
        $published_status = addon_published_status('Gateways');

        $routes = config('addon_admin_routes');
        $desiredName = 'sms_setup';
        $payment_url = '';
        foreach ($routes as $routeArray) {
            foreach ($routeArray as $route) {
                if ($route['name'] === $desiredName) {
                    $payment_url = $route['url'];
                    break 2;
                }
            }
        }

        // Whitelist of SMS providers that must always have a card on the page.
        $whitelist = [
            'twilio' => [
                'gateway' => 'twilio',
                'mode'    => 'live',
                'status'  => 0,
                'sid' => '',
                'messaging_service_sid' => '',
                'token' => '',
                'from' => '',
                'otp_template' => 'Your verification code is #OTP#',
            ],
            'nexmo' => [
                'gateway' => 'nexmo',
                'mode'    => 'live',
                'status'  => 0,
                'api_key' => '',
                'api_secret' => '',
                'token' => '',
                'from' => '',
                'otp_template' => 'Your verification code is #OTP#',
            ],
            '2factor' => [
                'gateway' => '2factor',
                'mode'    => 'live',
                'status'  => 0,
                'api_key' => '',
                'otp_template' => 'Your OTP is: #OTP#',
            ],
            'msg91' => [
                'gateway' => 'msg91',
                'mode'    => 'live',
                'status'  => 0,
                'template_id' => '',
                'auth_key' => '',
            ],
            'alphanet_sms' => [
                'gateway' => 'alphanet_sms',
                'mode'    => 'live',
                'status'  => 0,
                'api_key' => '',
                'sender_id' => '',
                'otp_template' => 'Your verification code is #OTP#',
            ],
            'message_central' => [
                'gateway'      => 'message_central',
                'mode'         => 'live',
                'status'       => 0,
                'customer_id'  => '',
                'auth_token'   => '',
                'country_code' => '',
                'otp_template' => 'Your verification code is #OTP#',
            ],
        ];

        // Self-heal: ensure every whitelisted provider has at least one row in
        // addon_settings so the admin page renders a card for it on first visit.
        // If a row already exists (because the operator saved it before), leave
        // its live_values untouched.
        foreach ($whitelist as $key_name => $defaults) {
            $exists = Setting::where([
                'key_name'      => $key_name,
                'settings_type' => 'sms_config',
            ])->first();

            if (!$exists) {
                $payload = json_encode($defaults);
                DB::table('addon_settings')->insert([
                    'key_name'      => $key_name,
                    'live_values'   => $payload,
                    'test_values'   => $payload,
                    'settings_type' => 'sms_config',
                    'mode'          => 'test',
                    'is_active'     => 0,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        $data_values = Setting::where('settings_type', 'sms_config')
            ->whereIn('key_name', array_keys($whitelist))
            ->get() ?? [];

        return view('admin-views.business-settings.sms-index', compact('data_values', 'published_status', 'payment_url'));
    }

    public function sms_update(Request $request, $module)
    {
        $login_setup_status = Helpers::get_business_settings('otp_login_status')??0;
        $is_firebase_active=Helpers::get_business_settings('firebase_otp_verification') ?? 0;
        $phone_verification_status = Helpers::get_business_settings('phone_verification_status')??0;
        if(!$is_firebase_active && $login_setup_status && ($request['status']==0)){
            Toastr::warning(translate('otp_login_status_is_enabled_in_login_setup._First_disable_from_login_setup.'));
            return redirect()->back();
        }
        if(!$is_firebase_active && $phone_verification_status && ($request['status']==0)){
            Toastr::warning(translate('phone_verification_status_is_enabled_in_login_setup._First_disable_from_login_setup.'));
            return redirect()->back();
        }
        if ($module == 'twilio') {
                $additional_data = [
                    'status' => $request['status'],
                    'sid' => $request['sid'],
                    'messaging_service_sid' => $request['messaging_service_sid'],
                    'token' => $request['token'],
                    'from' => $request['from'],
                    'otp_template' => $request['otp_template'],
                ];

        } elseif ($module == 'nexmo') {
            $additional_data = [
                'status' =>$request['status'],
                'api_key' => $request['api_key'],
                'api_secret' => $request['api_secret'],
                'token' =>$request['token'] ?? null,
                'from' => $request['from'],
                'otp_template' => $request['otp_template'],
            ];

        } elseif ($module == '2factor') {
            $additional_data = [
                'status' => $request['status'],
                'api_key' => $request['api_key'],
                'otp_template' => $request['otp_template'] ?? 'Your OTP is: #OTP#',
            ];
        } elseif ($module == 'msg91') {
            $additional_data = [
                'status' => $request['status'],
                'template_id' => $request['template_id'],
                'auth_key' => $request['auth_key'],
            ];
        } elseif ($request['gateway'] == 'alphanet_sms') {
            $additional_data = [
                'status' => $request['status'],
                'api_key' =>$request['api_key'],
                'sender_id' =>$request['sender_id'] ?? null,
                'otp_template' =>$request['otp_template'],
            ];
        } elseif ($module == 'message_central') {
            // Server-side validation for Message Central fields.
            // Operator-set fields only: customer_id, auth_token, country_code, otp_template.
            // senderId and otpLength are hard-coded in the provider method (no operator control).
            $request->validate([
                'status'        => 'required|in:0,1',
                'customer_id'   => 'required|string|max:191',
                'auth_token'    => 'required|string|max:255',
                'country_code'  => 'required|string|max:10',
                'otp_template'  => 'required|string|max:1000',
            ]);

            $additional_data = [
                'status'        => $request['status'],
                'customer_id'   => $request['customer_id'],
                'auth_token'    => $request['auth_token'],
                'country_code'  => $request['country_code'],
                'otp_template'  => $request['otp_template'],
            ];
        }

        $data= ['gateway' => $module ,
        'mode' =>  isset($request['status']) == 1  ?  'live': 'test'
        ];

    $credentials= json_encode(array_merge($data, $additional_data));
    DB::table('addon_settings')->updateOrInsert(['key_name' => $module, 'settings_type' => 'sms_config'], [
        'key_name' => $module,
        'live_values' => $credentials,
        'test_values' => $credentials,
        'settings_type' => 'sms_config',
        'mode' => isset($request['status']) == 1  ?  'live': 'test',
        'is_active' => isset($request['status']) == 1  ?  1: 0 ,
    ]);

    if ($request['status'] == 1) {
        foreach (['twilio','nexmo','2factor','msg91','alphanet_sms','message_central'] as $gateway) {
            if ($module != $gateway) {
                $keep = Setting::where(['key_name' => $gateway, 'settings_type' => 'sms_config'])->first();
                if (isset($keep)) {
                    $hold = $keep->live_values;
                    $hold['status'] = 0;
                    Setting::where(['key_name' => $gateway, 'settings_type' => 'sms_config'])->update([
                        'live_values' => $hold,
                        'test_values' => $hold,
                        'is_active' => 0,
                    ]);
                }
            }
        }
    }
        return back();
    }
}
