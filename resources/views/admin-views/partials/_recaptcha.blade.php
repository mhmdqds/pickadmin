@php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

    <input type="hidden" name="set_default_captcha" id="set_default_captcha_value" value="0">
    <div class="row g-2 py-2 d-none" id="reload-captcha">
        <div class="col-sm-6 pr-0">
            <input type="text" class="form-control form-control-lg" name="custome_recaptcha" id="custome_recaptcha"
                placeholder="{{translate('Enter recaptcha value')}}" autocomplete="off"
                value="{{getEnvMode() == 'dev' ? session('six_captcha') : ''}}">
        </div>
        <div class="col-sm-6 bg-white rounded d-flex">
            <img src="<?php    echo $custome_recaptcha->inline(); ?>" class="rounded w-100" />
            <div class="p-3 pr-0 capcha-spin reloadCaptcha">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>

            </div>
        </div>
    </div>

@else
    <div class="row g-2" id="reload-captcha">
        <div class="col-sm-6 pr-0">
            <input type="text" class="form-control form-control-lg" name="custome_recaptcha" id="custome_recaptcha"
                placeholder="{{translate('Enter recaptcha value')}}" autocomplete="off"
                value="{{getEnvMode() == 'dev' ? session('six_captcha') : ''}}">
        </div>
        <div class="col-sm-6 bg-white rounded d-flex">
            <img src="<?php    echo $custome_recaptcha->inline(); ?>" class="rounded w-100" />
            <div class="p-3 pr-0 capcha-spin reloadCaptcha">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>

            </div>
        </div>
    </div>
@endif
