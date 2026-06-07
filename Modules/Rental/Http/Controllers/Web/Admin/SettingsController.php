<?php

namespace Modules\Rental\Http\Controllers\Web\Admin;

use App\Models\DataSetting;
use Modules\Rental\Entities\RentalEmailTemplate;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\Traits\FileManagerTrait;
use Illuminate\Routing\Controller;
use Brian2694\Toastr\Facades\Toastr;
use App\CentralLogics\Helpers;

class SettingsController extends Controller
{

    use FileManagerTrait;

    public function __construct(private DataSetting $settings)
    {
        $this->settings = $settings;
    }
    public function homePageDownApp()
    {
        return view('rental::admin.home-page-setup.download-app', [
            'title_data' => $this->settings->where('key', 'module_home_page_data_title')->withoutGlobalScope('translate')->with('translations')->first(),
            'sub_title_data' => $this->settings->where('key', 'module_home_page_data_sub_title')->withoutGlobalScope('translate')->with('translations')->first(),
            'image' =>  $this->settings->where('key', 'module_home_page_data_image')->first(),
            'language'=> getWebConfig('language'),
        ]);
    }

    public function homePageDownAppUpdate(Request $request)
    {
        $request->validate([
            'title.*' => 'max:30',
            'title.0' => 'required',
            'sub_title.*' => 'max:110',
            'sub_title.0' => 'required',
            'image' => 'nullable|max:2048',
        ],
        [
            'title.0.required'=>translate('default_title_is_required'),
            'sub_title.0.required'=>translate('default_sub_title_is_required'),
            'title.*.max'=>translate('max_title_length_is_30_char'),
            'sub_title.*.required'=>translate('max_sub_title_length_is_120_char'),
        ]);

        $type = 'module_home_page_data';
        $fields = ['title', 'sub_title'];
        foreach ($fields as $field) {
            $this->updateSettingAndTranslations($request, "{$type}_{$field}", $type, $field, $request->lang, 'DataSetting');
        }

        if ($request->hasFile('image')) {
            $this->updateImageSetting($request->file('image'), 'module_home_page_data_image', 'react_landing/', $type);
        }

        Toastr::success(translate('messages.Download_app_section_data_updated_successfully'));
        return back();

    }
    public function vendorsRegistration()
    {
        return view('rental::admin.home-page-setup.vendor-registration', [
            'title_data' => $this->settings->where('key', 'module_vendor_registration_data_title')->withoutGlobalScope('translate')->with('translations')->first(),
            'sub_title_data' => $this->settings->where('key', 'module_vendor_registration_data_sub_title')->withoutGlobalScope('translate')->with('translations')->first(),
            'button_title_data' => $this->settings->where('key', 'module_vendor_registration_data_button_title')->withoutGlobalScope('translate')->with('translations')->first(),
            'image' =>  $this->settings->where('key', 'module_vendor_registration_data_image')->first(),
            'language'=> getWebConfig('language'),
        ]);
    }
    public function vendorsRegistrationUpdate(Request $request)
    {
        $request->validate([
            'title.*' => 'max:30',
            'title.0' => 'required',
            'sub_title.*' => 'max:110',
            'sub_title.0' => 'required',
            'button_title.*' => 'max:110',
            'button_title.0' => 'required',
            'image' => 'nullable|max:2048',
        ],
        [
            'title.0.required'=>translate('default_title_is_required'),
            'sub_title.0.required'=>translate('default_sub_title_is_required'),
            'button_title.0.required'=>translate('default_button_title_is_required'),
            'title.*.max'=>translate('max_title_length_is_30_char'),
            'sub_title.*.required'=>translate('max_sub_title_length_is_120_char'),
            'button_title.*.required'=>translate('max_button_title_length_is_20_char'),
        ]);

        $type = 'module_vendor_registration_data';

        $fields = ['title', 'sub_title', 'button_title'];
        foreach ($fields as $field) {
            $this->updateSettingAndTranslations($request, "{$type}_{$field}", $type, $field, $request->lang, 'DataSetting');
        }

        if ($request->hasFile('image')) {
            $this->updateImageSetting($request->file('image'), 'module_vendor_registration_data_image', 'react_landing/', $type);
        }

        Toastr::success(translate('messages.vendor_registration_section_data_updated_successfully'));
        return back();

    }


    private function updateSettingAndTranslations($request, $key, $type, $field, $lang, $modelName)
    {
        $setting = $this->settings->where('key', $key)->firstOrNew();
        $setting->type = $type;
        $setting->key = $key;
        $setting->value = $request->$field[array_search('default', $lang)];
        $setting->save();

        Helpers::add_or_update_translations(
            request: $request,
            key_data: $key,
            name_field: $field,
            model_name: $modelName,
            data_id: $setting->id,
            data_value: $setting->value
        );
        return true;
    }


    private function updateImageSetting($imageFile, $key, $path, $type)
    {
        $imageSetting = $this->settings->where('key', $key)->firstOrNew();
        $imageSetting->type = $type;
        $imageSetting->key = $key;

        if (empty($imageSetting->value)) {
            $imageSetting->value = $this->upload($path, 'png', $imageFile);
        } else {
            $imageSetting->value = $this->updateAndUpload($path, $imageSetting->value, 'png', $imageFile);
        }
        $imageSetting->save();
        return true;
    }

    public function email_index(Request $request, $type, $tab)
    {
        $template = $request->query('template', null);

        $viewPaths = [
            'new-order' => 'place-order-format',
            'provider-registration' => 'provider-registration-format',
            'registration' => 'registration-format',
            'approve' => 'approve-format',
            'deny' => 'deny-format',
            'withdraw-request' => 'withdraw-request-format',
            'withdraw-approve' => 'withdraw-approve-format',
            'withdraw-deny' => 'withdraw-deny-format',
            'refund-request' => 'refund-request-format',
            'suspend' => 'suspend-format',
            'registration-otp' => 'registration-otp-format',
            'refund-request-deny' => 'refund-request-deny-format',
            'refund-order' => 'refund-order-format',
            'unsuspend' => 'unsuspend-format',
            'subscription-successful' => 'subscription-successful-format',
            'subscription-renew' => 'subscription-renew-format',
            'subscription-shift' => 'subscription-shift-format',
            'subscription-cancel' => 'subscription-cancel-format',
            'subscription-deadline' => 'subscription-deadline-format',
            'subscription-plan_upadte' => 'subscription-plan_upadte-format',
        ];

        if (isset($viewPaths[$tab])) {
            return view("rental::admin.business-settings.email-format-setting.$type-email-formats." . $viewPaths[$tab], compact('template'));
        }

        abort(404);

    }

    public function update_email_index(Request $request, $type, $tab)
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $request->validate([
            'title.*' => 'nullable|max:255',
            'button_name.*' => 'nullable|max:255',
            'footer_text.*' => 'nullable|max:255',
            'copyright_text.*' => 'nullable|max:255',
        ], [
            'title.*.max' => 'The title may not be greater than 255 characters.',
            'button_name.*.max' => 'The button_name may not be greater than 255 characters.',
            'footer_text.*.max' => 'The footer_text may not be greater than 255 characters.',
            'copyright_text.*.max' => 'The copyright_text may not be greater than 255 characters.',
        ]);

        $emailTypes = [
            'new-order' => 'new_order',
            'forget-password' => 'forget_password',
            'provider-registration' => 'provider_registration',
            'dm-registration' => 'dm_registration',
            'registration' => 'registration',
            'approve' => 'approve',
            'deny' => 'deny',
            'withdraw-request' => 'withdraw_request',
            'withdraw-approve' => 'withdraw_approve',
            'withdraw-deny' => 'withdraw_deny',
            'campaign-request' => 'campaign_request',
            'campaign-approve' => 'campaign_approve',
            'campaign-deny' => 'campaign_deny',
            'refund-request' => 'refund_request',
            'login' => 'login',
            'suspend' => 'suspend',
            'cash-collect' => 'cash_collect',
            'registration-otp' => 'registration_otp',
            'login-otp' => 'login_otp',
            'order-verification' => 'order_verification',
            'refund-request-deny' => 'refund_request_deny',
            'add-fund' => 'add_fund',
            'refund-order' => 'refund_order',
            'product-deny' => 'product_deny',
            'product-approved' => 'product_approved',
            'offline-payment-deny' => 'offline_payment_deny',
            'offline-payment-approve' => 'offline_payment_approve',
            'pos-registration' => 'pos_registration',
            'unsuspend' => 'unsuspend',
            'subscription-successful' => 'subscription-successful',
            'subscription-renew' => 'subscription-renew',
            'subscription-shift' => 'subscription-shift',
            'subscription-cancel' => 'subscription-cancel',
            'subscription-deadline' => 'subscription-deadline',
            'subscription-plan_upadte' => 'subscription-plan_upadte',
            'new-advertisement' => 'new_advertisement',
            'update-advertisement' => 'update_advertisement',
            'advertisement-pause' => 'advertisement_pause',
            'advertisement-approved' => 'advertisement_approved',
            'advertisement-create' => 'advertisement_create',
            'advertisement-deny' => 'advertisement_deny',
            'advertisement-resume' => 'advertisement_resume',
        ];

        $email_type = $emailTypes[$tab] ?? null;

        if (!$email_type) {
            Toastr::error(translate('Invalid email template type.'));
            return back();
        }

        $template = RentalEmailTemplate::where('type', $type)->where('email_type', $email_type)->first() ?? new RentalEmailTemplate();

        if ($request->title[array_search('default', $request->lang)] == '') {
            Toastr::error(translate('default_data_is_required'));
            return back();
        }
        $template->title = $request->title[array_search('default', $request->lang)];
        $template->body = $request->body[array_search('default', $request->lang)];
        $template->body_2 = $request?->body_2 ? $request->body_2[array_search('default', $request->lang)] : null;
        $template->button_name = $request->button_name ? $request->button_name[array_search('default', $request->lang)] : '';
        $template->footer_text = $request->footer_text[array_search('default', $request->lang)];
        $template->copyright_text = $request->copyright_text[array_search('default', $request->lang)];
        $template->background_image = $request->has('background_image') ? Helpers::update('email_template/', $template->background_image, 'png', $request->file('background_image')) : $template->background_image;
        $template->image = $request->has('image') ? Helpers::update('email_template/', $template->image, 'png', $request->file('image')) : $template->image;
        $template->logo = $request->has('logo') ? Helpers::update('email_template/', $template->logo, 'png', $request->file('logo')) : $template->logo;
        $template->icon = $request->has('icon') ? Helpers::update('email_template/', $template->icon, 'png', $request->file('icon')) : $template->icon;
        $template->email_type = $email_type;
        $template->type = $type;
        $template->button_url = $request->button_url ?? '';
        $template->email_template = $request->email_template;
        $template->privacy = $request->privacy ? '1' : 0;
        $template->refund = $request->refund ? '1' : 0;
        $template->cancelation = $request->cancelation ? '1' : 0;
        $template->contact = $request->contact ? '1' : 0;
        $template->facebook = $request->facebook ? '1' : 0;
        $template->instagram = $request->instagram ? '1' : 0;
        $template->twitter = $request->twitter ? '1' : 0;
        $template->linkedin = $request->linkedin ? '1' : 0;
        $template->pinterest = $request->pinterest ? '1' : 0;
        $template->save();
        $default_lang = str_replace('_', '-', app()->getLocale());
        foreach ($request->lang as $index => $key) {
            if ($default_lang == $key && !($request->title[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'title'
                        ],
                        ['value' => $request->title[array_search('default', $request->lang)] ?? '']
                    );
                }
            } else {

                if ($request->title[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'title'
                        ],
                        ['value' => $request->title[$index]]
                    );
                }
            }
            if ($default_lang == $key && !($request->body[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'body'
                        ],
                        ['value' => $request->body[array_search('default', $request->lang)] ?? '']
                    );
                }
            } else {
                if ($request->body[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'body'
                        ],
                        ['value' => $request->body[$index]]
                    );
                }
            }
            if ($request?->body_2 && $default_lang == $key && !($request->body_2[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'body_2'
                        ],
                        ['value' => $template->body_2]
                    );
                }
            } else {

                if ($request?->body_2 && $request->body_2[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'body_2'
                        ],
                        ['value' => $request->body_2[$index]]
                    );
                }
            }
            if ($default_lang == $key && !($request->button_name && $request->button_name[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'button_name'
                        ],
                        ['value' => $request->button_name[array_search('default', $request->lang)] ?? '']
                    );
                }
            } else {

                if ($request->button_name && $request->button_name[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'button_name'
                        ],
                        ['value' => $request->button_name[$index]]
                    );
                }
            }
            if ($default_lang == $key && !($request->footer_text[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'footer_text'
                        ],
                        ['value' => $request->footer_text[array_search('default', $request->lang)] ?? '']
                    );
                }
            } else {

                if ($request->footer_text[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'footer_text'
                        ],
                        ['value' => $request->footer_text[$index]]
                    );
                }
            }
            if ($default_lang == $key && !($request->copyright_text[$index])) {
                if ($key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'copyright_text'
                        ],
                        ['value' => $request->copyright_text[array_search('default', $request->lang)] ?? '']
                    );
                }
            } else {

                if ($request->copyright_text[$index] && $key != 'default') {
                    Translation::updateOrInsert(
                        [
                            'translationable_type' => 'Modules\Rental\Entities\RentalEmailTemplate',
                            'translationable_id' => $template->id,
                            'locale' => $key,
                            'key' => 'copyright_text'
                        ],
                        ['value' => $request->copyright_text[$index]]
                    );
                }
            }
        }

        Toastr::success(translate('messages.template_added_successfully'));
        return back();
    }

    public function update_email_status(Request $request, $type, $tab, $status)
    {
        if (env('APP_MODE') == 'demo') {
            Toastr::info(translate('messages.update_option_is_disable_for_demo'));
            return back();
        }

        $tabKeys = [
            'place-order' => 'rental_place_order_mail_status_',
            'forgot-password' => 'rental_forget_password_mail_status_',
            'provider-registration' => 'rental_provider_registration_mail_status_',
            'registration' => 'rental_registration_mail_status_',
            'approve' => 'rental_approve_mail_status_',
            'deny' => 'rental_deny_mail_status_',
            'withdraw-request' => 'rental_withdraw_request_mail_status_',
            'withdraw-approve' => 'rental_withdraw_approve_mail_status_',
            'withdraw-deny' => 'rental_withdraw_deny_mail_status_',
            'refund-request' => 'rental_refund_request_mail_status_',
            'login' => 'rental_login_mail_status_',
            'suspend' => 'rental_suspend_mail_status_',
            'cash-collect' => 'rental_cash_collect_mail_status_',
            'registration-otp' => 'rental_registration_otp_mail_status_',
            'refund-request-deny' => 'rental_refund_request_deny_mail_status_',
            'refund-order' => 'rental_refund_order_mail_status_',
            'unsuspend' => 'rental_unsuspend_mail_status_',
            'subscription-successful' => 'rental_subscription_successful_mail_status_',
            'subscription-renew' => 'rental_subscription_renew_mail_status_',
            'subscription-shift' => 'rental_subscription_shift_mail_status_',
            'subscription-cancel' => 'rental_subscription_cancel_mail_status_',
            'subscription-deadline' => 'rental_subscription_deadline_mail_status_',
            'subscription-plan_upadte' => 'rental_subscription_plan_upadte_mail_status_',
        ];

        if (isset($tabKeys[$tab])) {
            Helpers::businessUpdateOrInsert(['key' => $tabKeys[$tab] . $type], ['value' => $status]);
            Toastr::success(translate('messages.email_status_updated'));
        } else {
            Toastr::error(translate('messages.invalid_email_status_update'));
        }

        return back();

    }

}
