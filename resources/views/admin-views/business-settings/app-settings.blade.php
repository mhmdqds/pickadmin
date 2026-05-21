@extends('layouts.admin.app')

@section('title',translate('messages.app_settings'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/setting.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.app_settings')}}
                </span>
            </h1>

        </div>
        <!-- End Page Header -->

        <?php
        $businessSettingKeys = [
            'app_minimum_version_android',
            'app_url_android',
            'app_minimum_version_ios',
            'app_url_ios',

            'app_minimum_version_android_store',
            'app_url_android_store',
            'app_minimum_version_ios_store',
            'app_url_ios_store',

            'app_minimum_version_android_deliveryman',
            'app_url_android_deliveryman',
            'app_minimum_version_ios_deliveryman',
            'app_url_ios_deliveryman',

            'app_minimum_version_android_rider',
            'app_url_android_rider',
            'app_minimum_version_ios_rider',
            'app_url_ios_rider',


            'language',
        ];

        $businessSettings = \App\Models\BusinessSetting::whereIn('key', $businessSettingKeys)->pluck('value', 'key');

        $app_minimum_version_android = $businessSettings->get('app_minimum_version_android');
        $app_url_android = $businessSettings->get('app_url_android');
        $app_minimum_version_ios = $businessSettings->get('app_minimum_version_ios');
        $app_url_ios = $businessSettings->get('app_url_ios');
        $app_minimum_version_android_store = $businessSettings->get('app_minimum_version_android_store');
        $app_url_android_store = $businessSettings->get('app_url_android_store');
        $app_minimum_version_ios_store = $businessSettings->get('app_minimum_version_ios_store');
        $app_url_ios_store = $businessSettings->get('app_url_ios_store');
        $app_minimum_version_android_deliveryman = $businessSettings->get('app_minimum_version_android_deliveryman');
        $app_url_android_deliveryman = $businessSettings->get('app_url_android_deliveryman');
        $app_minimum_version_ios_deliveryman = $businessSettings->get('app_minimum_version_ios_deliveryman');
        $app_url_ios_deliveryman = $businessSettings->get('app_url_ios_deliveryman');
        $app_minimum_version_android_rider = $businessSettings->get('app_minimum_version_android_rider');
        $app_url_android_rider = $businessSettings->get('app_url_android_rider');
        $app_minimum_version_ios_rider = $businessSettings->get('app_minimum_version_ios_rider');
        $app_url_ios_rider = $businessSettings->get('app_url_ios_rider');
        $language = $businessSettings->get('language');

        $appSettings = \App\Models\DataSetting::withoutGlobalScope('translate')
            ->where('type', 'app_settings')
            ->whereIn('key', ['download_user_app_section_status', 'download_user_app_title'])
            ->get()
            ->keyBy('key');

        $download_user_app_section_status = $appSettings->get('download_user_app_section_status');
        $download_user_app_title = $appSettings->get('download_user_app_title');
        ?>

        <form action="{{ route('admin.business-settings.app-settings-update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="type" value="download_section">
            <div class="card mb-20">
                <div class="card-header">
                    <div class="w-100">
                        <div class="row g-3 align-items-center justify-content-between">
                            <div class="col-xxl-9 col-lg-8 col-md-7 col-sm-6">
                                <div>
                                    <h5 class="card-title text-dark mb-0">
                                        <span>{{ translate('Show User App Download Section') }}</span>
                                    </h5>
                                    <p class="mb-0 fs-12">
                                        {{ translate('Here you setup your Customer app version & app download URL') }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-xxl-3 col-lg-4 col-md-5 col-sm-6">
                                <div class="py-2 px-3 rounded d-flex justify-content-between border align-items-center w-300">
                                    <h5 class="text-capitalize fs-14 fw-normal mb-0">{{ translate('Status') }}</h5>
                                    <label class="toggle-switch toggle-switch-sm" for="CheckboxStatus">
                                        <input type="checkbox"
                                            class="toggle-switch-input dynamic-checkbox-toggle"
                                            id="CheckboxStatus"
                                            data-id="CheckboxStatus"
                                            data-type="toggle"
                                            data-image-on="{{ asset('/public/assets/admin/img/status-ons.png') }}"
                                            data-image-off="{{ asset('/public/assets/admin/img/off-danger.png') }}"
                                            data-title-on="{{ translate('Do you want to turn on this section?') }}"
                                            data-title-off="{{ translate('Do you want to turn off this section?') }}"
                                            data-text-on="<p>{{ translate('If you turn on, this section will be shown in the app.') }}</p>"
                                            data-text-off="<p>{{ translate('If you turn off, this section will not be shown in the app.') }}</p>"
                                            name="download_user_app_section_status"
                                            value="1"
                                            {{ $download_user_app_section_status?->value ? 'checked' : '' }}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="bg--secondary rounded h-100 p-xxl-4 p-3 mb-20">
                        @if($language)
                            <ul class="nav nav-tabs mb-4 border-0">
                                <li class="nav-item">
                                    <a class="nav-link lang_link active" href="#" id="default-link">
                                        {{ translate('messages.default') }}
                                    </a>
                                </li>
                                @foreach(json_decode($language) as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link" href="#" id="{{ $lang }}-link">
                                            {{ \App\CentralLogics\Helpers::get_language_name($lang) . ' (' . strtoupper($lang) . ')' }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="lang_form default-form">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="row g-1">
                                        <div class="col-12">
                                            <label for="download_user_app_title" class="form-label">
                                                {{ translate('Title') }}
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    title="{{ translate('Write_the_title_within_60_characters') }}">
                                                    <i class="tio-info color-A7A7A7"></i>
                                                </span>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input id="download_user_app_title"
                                                type="text"
                                                maxlength="60"
                                                name="download_user_app_title[]"
                                                class="form-control min-h-40px"
                                                value="{{ $download_user_app_title?->getRawOriginal('value') ?? '' }}"
                                                placeholder="{{ translate('Title here...') }}">
                                            <span class="text-end text-counting color-A7A7A7 d-block mt-1">
                                                {{ strlen($download_user_app_title?->getRawOriginal('value') ?? '') }}/60
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="lang[]" value="default">

                        @if($language)
                            @foreach(json_decode($language) as $lang)
                                <?php
                                $download_user_app_title_translate = [];
                                if (isset($download_user_app_title->translations) && count($download_user_app_title->translations)) {
                                    foreach ($download_user_app_title->translations as $t) {
                                        if ($t->locale == $lang && $t->key == 'download_user_app_title') {
                                            $download_user_app_title_translate[$lang]['value'] = $t->value;
                                        }
                                    }
                                }
                                ?>
                                <div class="lang_form d-none" id="{{ $lang }}-form1">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <div class="row g-1">
                                                <div class="col-12">
                                                    <label for="download_user_app_title_{{ $lang }}" class="form-label">
                                                        {{ translate('Title') }} ({{ strtoupper($lang) }})
                                                        <span class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            title="{{ translate('Write_the_title_within_60_characters') }}">
                                                            <i class="tio-info color-A7A7A7"></i>
                                                        </span>
                                                    </label>
                                                    <input id="download_user_app_title_{{ $lang }}"
                                                        type="text"
                                                        maxlength="60"
                                                        name="download_user_app_title[]"
                                                        class="form-control min-h-40px"
                                                        value="{{ $download_user_app_title_translate[$lang]['value'] ?? '' }}"
                                                        placeholder="{{ translate('Title here...') }}">
                                                    <span class="text-end text-counting color-A7A7A7 d-block mt-1">
                                                        {{ strlen($download_user_app_title_translate[$lang]['value'] ?? '') }}/60
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                            @endforeach
                        @endif
                    </div>
                    <div class="info-notes-bg px-3 py-2 rounded fz-11 gap-2 align-items-center d-flex mt-20">
                        <img src="{{asset('public/assets/admin/img/info-idea.svg')}}" alt="">
                        <span>
                            {{translate('App download button URL link is setup successfully. Data is synced from')}}
                        </span>
                    </div>
                    <div class="btn--container justify-content-end mt-20">
                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary call-demo">{{translate('messages.save')}}</button>
                    </div>
                </div>
            </div>
        </form>

        <form action="{{route('admin.business-settings.app-settings-update')}}" method="post"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="user_app" >
        <div class="card mb-20">
            <div class="card-header">
                <div>
                    <h5 class="card-title text-dark mb-0">
                        <span>{{ translate('User App Version Control') }}</span>
                    </h5>
                    <p class="mb-0 fs-12">
                        {{ translate('Here you setup your Customer app version & app download URL') }}
                    </p>
                </div>
            </div>
            <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/andriod.png')}}" class="mr-2" alt="">
                                {{ translate('For android') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label  for="app_minimum_version_android" class="form-label">
                                        {{translate('Minimum_User_App_Version')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_user_app_version_required_for_the_app_functionality.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_minimum_version_android" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control" step="0.001" name="app_minimum_version_android"
                                        value="{{ $app_minimum_version_android ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_android" class="form-label">
                                        {{translate('Download_URL_for_User_App')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_user_app_version_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_url_android" type="url" placeholder="{{translate('messages.app_url')}}" class="form-control" name="app_url_android"
                                        value="{{ $app_url_android ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/ios.png')}}" class="mr-2" alt="">
                                {{ translate('For iOS') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label  for="app_minimum_version_ios" class="form-label">{{translate('Minimum_User_App_Version')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_user_app_version_required_for_the_app_functionality.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_minimum_version_ios" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control" step="0.001" name="app_minimum_version_ios"
                                        value="{{ $app_minimum_version_ios ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_ios" class="form-label">
                                        {{translate('Download_URL_for_User_App')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_user_app_version_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_url_ios" type="url" placeholder="{{translate('messages.app_url')}}" class="form-control" name="app_url_ios"
                                        value="{{ $app_url_ios ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="info-notes-bg px-3 py-2 rounded fz-11  gap-2 align-items-center d-flex mt-20">
                        <img src="{{asset('public/assets/admin/img/info-idea.svg')}}" alt="">
                        <span>
                            {{translate('Configure the User App download URL in this setting. The link will be shown in all sections where users can download the app.')}}
                        </span>
                    </div>
                    <div class="btn--container justify-content-end mt-20">
                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit"  class="btn btn--primary call-demo">{{translate('messages.save')}}</button>
                    </div>
                </div>
            </div>
        </form>


        <form action="{{route('admin.business-settings.app-settings-update')}}" method="post"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="store_app" >
            <div class="card mb-20">
                <div class="card-header">
                    <div>
                        <h5 class="card-title text-dark mb-0">
                            <span>{{ translate('Store_App_Version_Control') }}</span>
                        </h5>
                        <p class="mb-0 fs-12">
                            {{ translate('Here you setup your Vendor app version & app download URL') }}
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/andriod.png')}}" class="mr-2" alt="">
                                {{ translate('For android') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label  for="app_minimum_version_android_store" class="form-label text-capitalize">{{translate('Minimum_Store_App_Version_for_store')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_store_app_version_required_for_the_app_functionality.') }}">
                                        <i class="tio-info-outined"></i>
                                    </span>
                                    </label>
                                    <input id="app_minimum_version_android_store" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_android_store"
                                        step="0.001"   min="0" value="{{ $app_minimum_version_android_store ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_android_store" class="form-label text-capitalize">
                                        {{translate('Download_URL_for_Store_App_for_store')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_store_app_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_url_android_store" type="url" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_android_store"
                                        value="{{ $app_url_android_store ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/ios.png')}}" class="mr-2" alt="">
                                {{ translate('For iOS') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label for="app_minimum_version_ios_store" class="form-label text-capitalize">{{translate('Minimum_Store_App_Version')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_store_app_version_required_for_the_app_functionality.') }}">
                                        <i class="tio-info-outined"></i>
                                    </span>
                                    </label>
                                    <input id="app_minimum_version_ios_store" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_ios_store"
                                    step="0.001"  min="0" value="{{ $app_minimum_version_ios_store ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_ios_store" class="form-label text-capitalize">
                                        {{translate('Download_URL_for_Store_App')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_store_app_version_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_url_ios_store" type="url" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_ios_store"
                                    value="{{ $app_url_ios_store ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-20">
                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit"  class="btn btn--primary call-demo"  >{{translate('messages.save')}}</button>
                    </div>
                </div>
            </div>
        </form>


        <form action="{{route('admin.business-settings.app-settings-update')}}" method="post"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="type" value="deliveryman_app" >
            <div class="card mb-20">
                <div class="card-header">
                    <div>
                        <h5 class="card-title text-dark mb-0">
                            <span>{{ translate('Deliveryman_App_Version_Control') }}</span>
                        </h5>
                        <p class="mb-0 fs-12">
                            {{ translate('Here you setup your Deliveryman app version & app download URL') }}
                        </p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/andriod.png')}}" class="mr-2" alt="">
                                {{ translate('For android') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label for="app_minimum_version_android_deliveryman" class="form-label text-capitalize">{{translate('Minimum_Deliveryman_App_Version')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_deliveryman_app_version_required_for_the_app_functionality.') }}">
                                        <i class="tio-info-outined"></i>
                                    </span>
                                    </label>
                                    <input type="number" id="app_minimum_version_android_deliveryman" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_android_deliveryman"
                                        step="0.001"   min="0" value="{{ $app_minimum_version_android_deliveryman ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_android_deliveryman"  class="form-label text-capitalize">
                                        {{translate('Download_URL_for_Deliveryman_App')}} ({{translate('messages.android')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_deliveryman_app_version_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input type="url" id="app_url_android_deliveryman" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_android_deliveryman"
                                    value="{{ $app_url_android_deliveryman ?? '' }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title mb-3">
                                <img src="{{asset('/public/assets/admin/img/ios.png')}}" class="mr-2" alt="">
                                {{ translate('For iOS') }}
                            </h5>
                            <div class="__bg-F8F9FC-card">
                                <div class="form-group">
                                    <label  for="app_minimum_version_ios_deliveryman" class="form-label text-capitalize">{{translate('Minimum_Deliveryman_App_Version')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('The_minimum_deliveryman_app_version_required_for_the_app_functionality.') }}">
                                        <i class="tio-info-outined"></i>
                                    </span>
                                    </label>
                                    <input id="app_minimum_version_ios_deliveryman" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_ios_deliveryman"
                                    step="0.001"  min="0" value="{{ $app_minimum_version_ios_deliveryman ?? '' }}">
                                </div>
                                <div class="form-group mb-md-0">
                                    <label for="app_url_ios_deliveryman" class="form-label text-capitalize">
                                        {{translate('Download_URL_for_Deliveryman_App')}} ({{translate('messages.ios')}})
                                        <span class="input-label-secondary text--title" data-toggle="tooltip"
                                        data-placement="right"
                                        data-original-title="{{ translate('Users_will_download_the_latest_deliveryman_app_version_using_this_URL.') }}">
                                            <i class="tio-info-outined"></i>
                                        </span>
                                    </label>
                                    <input id="app_url_ios_deliveryman" type="url" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_ios_deliveryman"
                                    value="{{ $app_url_ios_deliveryman ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-20">
                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit"  class="btn btn--primary call-demo">{{translate('messages.save')}}</button>
                    </div>
                </div>
            </div>
        </form>

        @if(addon_published_status('RideShare'))
                <form action="{{route('admin.business-settings.app-settings-update')}}" method="post"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="type" value="rider_app" >
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h5 class="card-title text-dark mb-0">
                                    <span>{{ translate('Rider_App_Version_Control') }}</span>
                                </h5>
                                <p class="mb-0 fs-12">
                                    {{ translate('Here you setup your Rider app version & app download URL') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h5 class="card-title mb-3">
                                        <img src="{{asset('/public/assets/admin/img/andriod.png')}}" class="mr-2" alt="">
                                        {{ translate('For android') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group">
                                            <label for="app_minimum_version_android_rider" class="form-label text-capitalize">{{translate('Minimum_Rider_App_Version')}} ({{translate('messages.android')}})
                                                <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('The_minimum_rider_app_version_required_for_the_app_functionality.') }}">
                                                <i class="tio-info-outined"></i>
                                            </span>
                                            </label>
                                            <input type="number" id="app_minimum_version_android_rider" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_android_rider"
                                                step="0.001"   min="0" value="{{ $app_minimum_version_android_rider ?? '' }}">
                                        </div>
                                        <div class="form-group mb-md-0">
                                            <label for="app_url_android_rider"  class="form-label text-capitalize">
                                                {{translate('Download_URL_for_Rider_App')}} ({{translate('messages.android')}})
                                                <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('Users_will_download_the_latest_rider_app_version_using_this_URL.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input type="url" id="app_url_android_rider" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_android_rider"
                                            value="{{ $app_url_android_rider ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title mb-3">
                                        <img src="{{asset('/public/assets/admin/img/ios.png')}}" class="mr-2" alt="">
                                        {{ translate('For iOS') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group">
                                            <label  for="app_minimum_version_ios_rider" class="form-label text-capitalize">{{translate('Minimum_Rider_App_Version')}} ({{translate('messages.ios')}})
                                                <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('The_minimum_rider_app_version_required_for_the_app_functionality.') }}">
                                                <i class="tio-info-outined"></i>
                                            </span>
                                            </label>
                                            <input id="app_minimum_version_ios_rider" type="number" placeholder="{{translate('messages.app_minimum_version')}}" class="form-control h--45px" name="app_minimum_version_ios_rider"
                                            step="0.001"  min="0" value="{{ $app_minimum_version_ios_rider ?? '' }}">
                                        </div>
                                        <div class="form-group mb-md-0">
                                            <label for="app_url_ios_rider" class="form-label text-capitalize">
                                                {{translate('Download_URL_for_Rider_App')}} ({{translate('messages.ios')}})
                                                <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('Users_will_download_the_latest_rider_app_version_using_this_URL.') }}">
                                                    <i class="tio-info-outined"></i>
                                                </span>
                                            </label>
                                            <input id="app_url_ios_rider" type="url" placeholder="{{translate('messages.Download_Url')}}" class="form-control h--45px" name="app_url_ios_rider"
                                            value="{{ $app_url_ios_rider ?? '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end mt-20">
                                <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                                <button type="submit"  class="btn btn--primary call-demo">{{translate('messages.save')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
        @endif
    </div>


@endsection
