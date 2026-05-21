<?php

namespace App\Http\Controllers;

use App\Models\DeliveryMan;
use App\Models\DataSetting;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Gregwar\Captcha\CaptchaBuilder;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Modules\RideShare\Interface\UserManagement\Service\DriverLevelServiceInterface;
use Modules\RideShare\Entities\UserManagement\RiderDetail;

class RiderRegistrationController extends Controller
{
    public function __construct(
        protected DriverLevelServiceInterface $driverLevelService,
    )
    {
    }

    public function create()
    {
        if (!addon_published_status('RideShare')) {
            abort(404);
        }

        $settings = DataSetting::where('type', RIDE_SHARE_BUSINESS_SETTINGS)->where('key', 'toggle_rider_registration')->first();

        if (!isset($settings) || $settings->value == '0') {
            Toastr::error(translate('messages.rider_registration_is_disabled'));
            return back();
        }

        $custome_recaptcha = new CaptchaBuilder;
        $custome_recaptcha->build();
        Session::put('six_captcha', $custome_recaptcha->getPhrase());

        return view('rider-registration', compact('custome_recaptcha'));
    }

    public function store(Request $request)
    {
        if (!addon_published_status('RideShare')) {
            abort(404);
        }

        $settings = DataSetting::where('type', RIDE_SHARE_BUSINESS_SETTINGS)->where('key', 'toggle_rider_registration')->first();

        if (!isset($settings) || $settings->value == '0') {
            Toastr::error(translate('messages.rider_registration_is_disabled'));
            return back();
        }

        if ($request->referral_code) {
            $referal_user = DeliveryMan::withoutGlobalScope('delivery_only')->where('ref_code', $request->referral_code)->first();
            if (!$referal_user || !$referal_user->status) {
                Toastr::error(translate('referer_code_not_found'));
                return back()->withInput();
            }
            Helpers::deliverymanReferralNotification($referal_user);
        }

        $recaptcha = Helpers::get_business_settings('recaptcha');
        if (isset($recaptcha) && $recaptcha['status'] == 1) {
            $request->validate([
                'g-recaptcha-response' => [
                    function ($attribute, $value, $fail) {
                        $secret_key = Helpers::get_business_settings('recaptcha')['secret_key'];
                        $gResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                            'secret' => $secret_key,
                            'response' => $value,
                            'remoteip' => \request()->ip(),
                        ]);

                        if (!$gResponse->successful()) {
                            $fail(translate('ReCaptcha Failed'));
                        }
                    },
                ],
            ]);
        } else if (session('six_captcha') != $request->custome_recaptcha) {
            Toastr::error(translate('messages.ReCAPTCHA Failed'));
            return back()->withInput();
        }

        $request->validate([
            'f_name' => 'required|max:100',
            'l_name' => 'nullable|max:100',
            'identity_number' => 'required|max:30',
            'email' => 'required|unique:delivery_men',
            'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:delivery_men',
            'zone_id' => 'required',
            // 'vehicle_id' => 'required',
            'password' => ['required', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ], [
            'f_name.required' => translate('messages.first_name_is_required'),
            'zone_id.required' => translate('messages.select_a_zone'),
            'vehicle_id.required' => translate('messages.select_a_vehicle'),
        ]);

        if ($request->has('image')) {
            $image_name = Helpers::upload('delivery-man/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $id_img_names = [];
        if (!empty($request->file('identity_image'))) {
            foreach ($request->identity_image as $img) {
                $identity_image = Helpers::upload('delivery-man/', 'png', $img);
                array_push($id_img_names, ['img' => $identity_image, 'storage' => Helpers::getDisk()]);
            }
            $identity_image = json_encode($id_img_names);
        } else {
            $identity_image = json_encode([]);
        }

        $firstLevel = $this->driverLevelService->findOneBy(criteria: ['user_type' => DRIVER, 'sequence' => 1]);
        if (!$firstLevel) {
            Toastr::error(translate('messages.rider_level_not_found'));
            return back()->withInput();
        }

        $dm = new DeliveryMan();
        $dm->f_name = $request->f_name;
        $dm->l_name = $request->l_name;
        $dm->email = $request->email;
        $dm->phone = $request->phone;
        $dm->identity_number = $request->identity_number;
        $dm->identity_type = $request->identity_type;
        $dm->vehicle_id = $request->vehicle_id;
        $dm->zone_id = $request->zone_id;
        $dm->identity_image = $identity_image;
        $dm->image = $image_name;
        $dm->active = 0;
        $dm->earning = 1; // Freelancer
        $dm->is_ride = 1;
        $dm->is_delivery = 0;
        $dm->user_level_id = $firstLevel->id;
        $dm->password = bcrypt($request->password);
        $dm->application_status = 'pending';
        $dm->ref_by = $referal_user?->id ?? null;
        $dm->ref_code = Helpers::generate_referer_code('deliveryman');
        $dm->save();

        $riderDetails = new RiderDetail();
        $riderDetails->user_id = $dm->id;
        $riderDetails->is_online = false;
        $riderDetails->availability_status = 'unavailable';
        $riderDetails->save();

        // Notification and Mail (mirroring DeliveryManController)
        try {
            $admin = \App\Models\Admin::where('role_id', 1)->first();

            if (config('mail.status') && Helpers::get_mail_status('registration_mail_status_dm') == '1' && Helpers::getNotificationStatusData('deliveryman', 'deliveryman_registration', 'mail_status')) {
                Mail::to($request->email)->send(new \App\Mail\DmSelfRegistration('pending', $dm));
            }
            if (config('mail.status') && Helpers::get_mail_status('dm_registration_mail_status_admin') == '1' && Helpers::getNotificationStatusData('admin', 'deliveryman_self_registration', 'mail_status')) {
                Mail::to($admin['email'])->send(new \App\Mail\DmRegistration('pending', $dm));
            }
        } catch (\Exception $ex) {
            info($ex->getMessage());
        }

        Toastr::success(translate('messages.application_placed_successfully'));
        return redirect()->route('home');
    }
}
