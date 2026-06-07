@extends('layouts.admin.app')

@section('title', translate('messages.Create New Provider'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('Modules/Rental/public/assets/css/admin/provider-create.css')}}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-20">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/provider.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ translate('messages.Create New Provider') }}
                    </span> </h1>

                </div>
            </div>
        </div>

        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first()?->value ?? null)
        <!-- End Page Header -->
        <form action="#" method="post" enctype="multipart/form-data" id="providerFormSubmit">
            @csrf

            <div id="businessSetup">
                <div class="custom-timeline d-flex flex-wrap gap-40px text-title mb-2">
                    <h4 class="single"><span class="count">1</span>{{translate('Business Basic Setup')}}</h4>
                    <h4 class="single opacity-70"><span class="count2">2</span>{{translate('Business Plan Setup')}}</h4>
                </div>

                <div class="row g-2">
                    <div class="col-lg-12">
                        <div class="card mt-4">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.General_Info') }}
                                    </h5>
                                    <p class="fs-12 mb-0">
                                        {{ translate('messages. Insert the basic information of the provider ') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <div class="card __bg-FAFAFA border-0">
                                            <div class="card-body">
                                                @if ($language)
                                                    <ul class="nav nav-tabs mb-4 flex-nowrap">
                                                        <li class="nav-item">
                                                            <a class="nav-link lang_link text-nowrap active" href="#"
                                                                id="default-link">{{ translate('Default') }}</a>
                                                        </li>
                                                        @foreach (json_decode($language) as $lang)
                                                            <li class="nav-item">
                                                                <a class="nav-link lang_link text-nowrap" href="#"
                                                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                                @if ($language)
                                                    <div class="lang_form" id="default-form">
                                                        <div class="form-group">
                                                            <label class="input-label font-semibold"
                                                                for="default_name">{{ translate('messages.name') }}
                                                                ({{ translate('messages.Default') }})
                                                            </label>
                                                            <input type="text" name="name[]" id="default_name"
                                                                class="form-control"
                                                                placeholder="{{ translate('messages.provider_name') }}" value="{{old('default_name')}}" required>
                                                        </div>
                                                        <input type="hidden" name="lang[]" value="default">
                                                        <div class="form-group mb-0">
                                                            <label class="input-label font-semibold"
                                                                for="exampleFormControlInput1">{{ translate('messages.address') }}
                                                                ({{ translate('messages.default') }})</label>
                                                            <textarea type="text" name="address[]" id="default_address" placeholder="{{ translate('messages.address') }}"
                                                                class="form-control min-h-90px ckeditor"></textarea>
                                                        </div>
                                                    </div>
                                                    @foreach (json_decode($language) as $lang)
                                                        <div class="d-none lang_form" id="{{ $lang }}-form">
                                                            <div class="form-group">
                                                                <label class="input-label font-semibold"
                                                                    for="{{ $lang }}_name">{{ translate('messages.name') }}
                                                                    ({{ strtoupper($lang) }})
                                                                </label>
                                                                <input type="text" name="name[]"
                                                                    id="{{ $lang }}_name" class="form-control"
                                                                    placeholder="{{ translate('messages.provider_name') }}">
                                                            </div>
                                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                                            <div class="form-group mb-0">
                                                                <label class="input-label font-semibold"
                                                                    for="exampleFormControlInput1">{{ translate('messages.address') }}
                                                                    ({{ strtoupper($lang) }})</label>
                                                                <textarea type="text" name="address[]" placeholder="{{ translate('messages.address') }}"
                                                                    class="form-control min-h-90px ckeditor"></textarea>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div id="default-form">
                                                        <div class="form-group">
                                                            <label class="input-label font-semibold"
                                                                for="exampleFormControlInput1">{{ translate('messages.name') }}
                                                                ({{ translate('messages.default') }})</label>
                                                            <input type="text" name="name[]" class="form-control"
                                                                placeholder="{{ translate('messages.provider_name') }}" required>
                                                        </div>
                                                        <input type="hidden" name="lang[]" value="default">
                                                        <div class="form-group mb-0">
                                                            <label class="input-label font-semibold"
                                                                for="exampleFormControlInput1">{{ translate('messages.address') }}
                                                            </label>
                                                            <textarea type="text" name="address[]" placeholder="{{ translate('messages.address') }}"
                                                                class="form-control min-h-90px ckeditor"></textarea>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-flex flex-column flex-sm-row gap-4">
                                            <div class="__custom-upload-img">
                                                @php($logo = \App\Models\BusinessSetting::where('key', 'logo')->first())
                                                @php($logo = $logo->value ?? '')
                                                <label class="form-label mb-1">
                                                    {{ translate('logo') }}
                                                </label>
                                                <div class="mb-20">
                                                    <p class="fs-12 max-width-170px">{{ translate('JPG, JPEG, PNG Less Than 2MB') }} <strong
                                                            class="font-semibold">({{ translate('Ratio 1:1') }})</strong></p>
                                                </div>
                                                <label
                                                    class="position-relative d-inline-block image--border cursor-pointer w-100 h-165 max-w-165">
                                                    <img class="h-165 aspect-ratio-1 rounded-10 display-none"
                                                        id="logoImageViewer"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/upload.png') }}"
                                                        src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                        alt="logo image"/>
                                                    <div class="upload-file__textbox p-2 h-100">
                                                        <img width="34" height="34"
                                                            src="{{ asset('public/assets/admin/img/document-upload.png') }}"
                                                            alt="" class="svg">
                                                        <h6 class="mt-2 text-center font-semibold fs-12">
                                                            <span
                                                                class="text-info">{{ translate('messages.Click to upload') }}</span>
                                                            <br>
                                                            {{ translate('messages.or drag and drop') }}
                                                        </h6>
                                                    </div>
                                                    <div class="icon-file-group outside">

                                                        <input type="file" name="logo" id="customFileEg1"
                                                                class="custom-file-input"
                                                                accept=".webp, .jpg, .png, .jpeg|image/*">
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="__custom-upload-img">
                                                @php($icon = \App\Models\BusinessSetting::where('key', 'icon')->first())
                                                @php($icon = $icon->value ?? '')
                                                <label class="form-label mb-1">
                                                    {{ translate('Cover') }}
                                                </label>
                                                <div class="mb-20">
                                                    <p class="fs-12">
                                                        {{ translate('JPG, JPEG, PNG Less Than 2MB') }}
                                                        <br>
                                                        <strong class="font-semibold">({{ translate('Ratio 2:1') }})</strong>
                                                    </p>
                                                </div>
                                                <label
                                                    class="position-relative d-inline-block image--border cursor-pointer w-100 h-165 min-w-330 min-w-100-mobile">
                                                    <img class="img--vertical-2 h-165 rounded-10 image--border display-none"
                                                        id="coverImageViewer"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                        src="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                        alt="Fav icon" />
                                                    <div class="upload-file__textbox p-2 h-100">
                                                        <img width="34" height="34"
                                                            src="{{ asset('public/assets/admin/img/document-upload.png') }}"
                                                            alt="" class="svg">
                                                        <h6 class="mt-2 text-center font-semibold fs-12">
                                                            <span
                                                                class="text-info">{{ translate('messages.Click to upload') }}</span>
                                                            <br>
                                                            {{ translate('messages.or drag and drop') }}
                                                        </h6>
                                                    </div>
                                                    <div class="icon-file-group outside">

                                                        <input type="file" name="cover_photo" id="coverImageUpload"
                                                                class="custom-file-input"
                                                                accept=".webp, .jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.Business_Info') }}
                                    </h5>
                                    <p class="fs-12 mb-0">
                                        {{ translate('messages.Insert the necessary information to operate the business') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 my-0">
                                    <div class="col-lg-6">
                                        <div class="form-group">
                                            <label class="input-label font-semibold"
                                                for="choice_zones">{{ translate('messages.business_zone') }}
                                                <span class="form-label-secondary" data-toggle="tooltip"
                                                    data-placement="right"
                                                    data-original-title="{{ translate('messages.Select the zone from where the business will be operated') }}">
                                                    <i class="tio-info text--title opacity-60"></i>
                                                </span>
                                            </label>
                                            <select name="zone_id" data-zone-coordinates-url="{{ route('admin.zone.get-coordinates', ['id' => 'PLACEHOLDER_ID']) }}" id="choice_zones" required
                                                class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_zone') }}">
                                                <option value="" selected disabled>
                                                    {{ translate('messages.select_zone') }}</option>
                                                @foreach ($zones as $zone)
                                                    @if (isset(auth('admin')->user()->zone_id))
                                                        @if (auth('admin')->user()->zone_id == $zone->id)
                                                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                        @endif
                                                    @else
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-5 pickup-zone-tag">
                                            <label class="input-label font-semibold"
                                                for="pickup_zones">{{ translate('messages.pickup_zone') }}<span
                                                    class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Select zones from where customer can choose their pickup locations for trip booking') }}">
                                                    <i class="tio-info text--title opacity-60"></i>
                                                </span></label>
                                            <select name="pickup_zones[]" id="pickup_zones"
                                                class="form-control  multiple-select2" multiple="multiple">
                                                @foreach ($zones as $zone)
                                                    @if (isset(auth('admin')->user()->zone_id))
                                                        @if (auth('admin')->user()->zone_id == $zone->id)
                                                            <option value="{{ $zone->id }}" selected>{{ $zone->name }}
                                                            </option>
                                                        @endif
                                                    @else
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="input-label" for="latitude">{{ translate('messages.latitude') }}
                                                <span class="input-label-secondary"
                                                      title="{{ translate('messages.provider_lat_lng_warning') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.provider_lat_lng_warning') }}"></span></label>
                                            <input type="text" id="latitude" name="latitude"
                                                   class="form-control __form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} -94.22213"
                                                   value="{{ old('latitude') }}" required readonly>
                                        </div>
                                        <div class="form-group mb-4">
                                            <label class="input-label" for="longitude">{{ translate('messages.longitude') }}
                                                <span class="input-label-secondary"
                                                      title="{{ translate('messages.provider_lat_lng_warning') }}"><img
                                                        src="{{ asset('/public/assets/admin/img/info-circle.svg') }}"
                                                        alt="{{ translate('messages.provider_lat_lng_warning') }}"></span></label>
                                            <input type="text" name="longitude" class="form-control __form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude"
                                                   value="{{ old('longitude') }}" required readonly>
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="input-label font-semibold"
                                                for="tax">{{ translate('messages.Vat/Tax') }}
                                                (%)</label>
                                            <input type="number" name="tax" class="form-control" id="tax"
                                                placeholder="{{ translate('messages.vat/tax') }}" min="0"
                                                step=".01" required value="{{ old('tax') }}">
                                        </div>
                                        <div class="position-relative">
                                            <label class="input-label font-semibold"
                                                for="tax">{{ translate('Approx. Pickup Time') }}</label>
                                            <div class="custom-group-btn">
                                                <div class="item flex-sm-grow-1">
                                                    <label class="floating-label"
                                                        for="min">{{ translate('Min') }}:</label>
                                                    <input id="min" type="number" name="minimum_delivery_time"
                                                        value=""
                                                        class="form-control h--45px border-0"
                                                        placeholder="{{ translate('messages.Ex :') }} 20"
                                                        pattern="^[0-9]{2}$" required>
                                                </div>
                                                <div class="separator"></div>
                                                <div class="item flex-sm-grow-1">
                                                    <label class="floating-label"
                                                        for="max">{{ translate('Max') }}:</label>
                                                    <input id="max" type="number" name="maximum_delivery_time"
                                                        value=""
                                                        class="form-control h--45px border-0"
                                                        placeholder="{{ translate('messages.Ex :') }} 30" pattern="[0-9]{2}$"
                                                        required>
                                                </div>
                                                <div class="separator"></div>
                                                <div class="item flex-shrink-0">
                                                    <select name="delivery_time_type" id="delivery_time_type"
                                                        class="custom-select border-0">
                                                        <option value="min"  >
                                                            {{ translate('messages.minutes') }}
                                                        </option>
                                                        <option value="hours" >
                                                            {{ translate('messages.hours') }}
                                                        </option>
                                                        <option value="days" >
                                                            {{ translate('messages.days') }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <input id="pac-input1" class="controls rounded" data-toggle="tooltip"
                                            data-placement="right"
                                            data-original-title="{{ translate('messages.search_your_location_here') }}"
                                            type="text" placeholder="{{ translate('messages.search_here') }}" />
                                        <div id="map" class="min-h-100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.owner_information') }}
                                    </h5>
                                    <p class="fs-12 mb-0">
                                        {{ translate('messages.Add the information of the Owner who operate the business') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="f_name">{{ translate('messages.first_name') }}</label>
                                            <input type="text" name="f_name" class="form-control" id="f_name"
                                                placeholder="{{ translate('messages.first_name') }}" value="{{old('f_name')}}"
                                                required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="l_name">{{ translate('messages.last_name') }}</label>
                                            <input type="text" name="l_name" class="form-control" id="l_name"
                                                placeholder="{{ translate('messages.last_name') }}" value="{{old('l_name')}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="phone">{{ translate('messages.phone') }}</label>
                                            <input type="tel" id="phone" name="phone" class="form-control"
                                                placeholder="{{ translate('messages.Ex:') }} 017********" value="{{old('phone')}}"
                                                required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.account_information') }}
                                    </h5>
                                    <p class="fs-12 mb-0">
                                        {{ translate('messages.Insert the necessary information to account information') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                for="exampleFormControlInput1">{{ translate('messages.email') }}</label>
                                            <input type="email" name="email" class="form-control" id="email"
                                                placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                                value="{{old('email')}}" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="js-form-message form-group mb-0">
                                            <label class="input-label"
                                                for="signupSrPassword">{{ translate('password') }}<span
                                                    class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}">
                                                    <i class="tio-info text--title opacity-60"></i>
                                                </span></label>

                                            <div class="input-group input-group-merge">
                                                <input type="password" class="js-toggle-password form-control"
                                                    name="password" id="signupSrPassword"
                                                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                    title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                                    placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                    aria-label="8+ characters required"
                                                    data-msg="Your password is invalid. Please try again."
                                                    data-hs-toggle-password-options='{
                                                "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                                "defaultClass": "tio-hidden-outlined",
                                                "showClass": "tio-visible-outlined",
                                                "classChangeTarget": ".js-toggle-passowrd-show-icon-1"
                                                }'>
                                                <div class="js-toggle-password-target-1 input-group-append">
                                                    <a class="input-group-text" href="javascript:;">
                                                        <i class="js-toggle-passowrd-show-icon-1 tio-visible-outlined"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="password-feedback" class="pass password-feedback">
                                            {{ translate('messages.password_not_matched') }}
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="js-form-message form-group mb-0">
                                            <label class="input-label"
                                                for="signupSrConfirmPassword">{{ translate('messages.Confirm Password') }}</label>

                                            <div class="input-group input-group-merge">
                                                <input type="password" class="js-toggle-password form-control"
                                                    name="confirmPassword" id="signupSrConfirmPassword"
                                                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                                    title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}"
                                                    placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                    aria-label="8+ characters required"
                                                    data-msg="Password does not match the confirm password."
                                                    data-hs-toggle-password-options='{
                                                    "target": [".js-toggle-password-target-1", ".js-toggle-password-target-2"],
                                                    "defaultClass": "tio-hidden-outlined",
                                                    "showClass": "tio-visible-outlined",
                                                    "classChangeTarget": ".js-toggle-passowrd-show-icon-2"
                                                    }'>
                                                <div class="js-toggle-password-target-2 input-group-append">
                                                    <a class="input-group-text" href="javascript:;">
                                                        <i class="js-toggle-passowrd-show-icon-2 tio-visible-outlined"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="invalid-feedback" class="pass invalid-feedback">
                                            {{ translate('messages.password_not_matched') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="btn--container justify-content-end mt-3">
                            <button type="reset" id="reset_btn"
                                class="btn btn--warning-light min-w-100px justify-content-center">{{ translate('messages.reset') }}</button>
                            <button type="button"
                                class="btn btn--primary min-w-100px justify-content-center show-business-plan-div" id="nextStep">{{ translate('messages.next') }}</button>
                        </div>
                    </div>
                </div>
            </div>
            <div id="businessPlan" class="d-none">
                <div class="custom-timeline d-flex flex-wrap gap-40px text-title mb-2">
                    <h4 class="single text-primary checked"><span class="count-checked">1</span>{{ translate('messages.Business Basic Setup') }}</h4>
                    <h4 class="single font-semibold"><span class="count btn-primary">2</span>{{ translate('messages.Business Plan Setup') }}</h4>
                </div>
                <div class="row g-2">
                    <div class="col-lg-12">
                        <div class="card mt-3">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.Choose Business Plan') }}
                                    </h5>
                                    <p class="fs-12 mb-0">
                                        {{ translate('messages.Pay per transaction or enjoy unlimited access with a subscription.') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="business-plan-card-wrapper">
                                            <input type="radio" name="business_plan" class="business-plan-radio" value="commission-base" checked/>
                                            <div class="business-plan-card">
                                                <h4 class="fs-16 title text-title mb-10px opacity-70">
                                                    {{ translate('messages.Commission Base') }}
                                                </h4>
                                                <p class="fs-14 text-title opacity-70 mb-0">
                                                    {{ translate('messages.You have to give a certain percentage of commission to admin for every Trip request.') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="business-plan-card-wrapper">
                                            <input type="radio" name="business_plan" class="business-plan-radio" value="subscription-base"/>
                                            <div class="business-plan-card">
                                                <h4 class="fs-16 title text-title mb-10px opacity-70">
                                                    {{ translate('messages.Subscription Base') }}
                                                </h4>
                                                <p class="fs-14 text-title opacity-70 mb-0">
                                                    {{ translate('messages.You have to pay certain amount in every month/year to admin as subscription fee.') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-lg-12 mt-20" id="subscription-plan">
                                        <div>
                                            <div class="text-center mb-20">
                                                <h3 class="modal-title fs-16 opacity-lg font-bold">
                                                    {{ translate('Choose Subscription Package') }}</h3>
                                            </div>
                                            <div class="plan-slider owl-theme owl-carousel owl-refresh">
                                                @forelse ($packages as $key=> $package)
                                                    <label class="__plan-item d-block hover {{ (count($packages) > 4 && $key == 2) || (count($packages) < 5 && $key == 1) ? 'active' : '' }}">
                                                        <input type="radio" name="package_id" id="package_id"
                                                               value="{{ $package->id }}" class="d-none" {{ (count($packages) > 4 && $key == 2) || (count($packages) < 5 && $key == 1) ? 'checked' : '' }}>
                                                        <div class="inner-div">
                                                            <div class="text-center">
                                                                <h3 class="title">{{ $package->package_name }}</h3>
                                                                <h2 class="price">{{ \App\CentralLogics\Helpers::format_currency($package->price) }}</h2>
                                                                <div class="day-count">{{ $package->validity }}
                                                                    {{ translate('messages.days') }}</div>
                                                            </div>
                                                            <ul class="info">

                                                                @if ($package->mobile_app)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.mobile_app') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->chat)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.chatting_options') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->review)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.review_section') }}</span>
                                                                    </li>
                                                                @endif

                                                                @if ($package->max_order == 'unlimited')
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.Unlimited_Trips') }}</span>
                                                                    </li>
                                                                @else
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ $package->max_order }} {{ translate('messages.Trips') }} </span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->max_product == 'unlimited')
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.Unlimited_uploads') }}</span>
                                                                    </li>
                                                                @else
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ $package->max_product }} {{ translate('messages.uploads') }}</span>
                                                                    </li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    </label>
                                                @empty
                                                    <div class="text-center">
                                                        {{translate('No Package Found')}}
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="btn--container justify-content-end mt-3">
                            <button type="button" class="btn btn--reset min-w-100px justify-content-center" id="backBusinessSetup">{{ translate('messages.back') }}</button>
                            <button type="submit" class="btn btn--primary min-w-100px justify-content-center" id="submit">{{ translate('messages.Submit') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
    @php($default_location = $default_location->value ? json_decode($default_location->value, true) : 0)

<div class="d-none" id="data-set"
    data-admin-zone-id="{{auth('admin')->user()->zone_id}}"

    data-lat="{{ $default_location ? $default_location['lat'] : '23.757989' }}"
    data-lng="{{ $default_location ? $default_location['lng'] : '90.360587' }}"
    data-logoImageViewer="{{ asset('public/assets/admin/img/upload.png') }}"

    data-get-all-modules-url="{{ route('restaurant.get-all-modules') }}"
    data-password-valid="{{ translate('Password is valid') }}"
    data-password-invalid="{{ translate('Password format is invalid') }}"
    data-password-matched="{{ translate('Passwords Matched') }}"
    data-password-not-matched="{{ translate('confirmPassword not match') }}"

    data-store-logo-required="{{ translate('Store_logo_&_cover_photos_are_required') }}"
    data-store-name-required="{{ translate('Store_name_is_required') }}"
    data-store-address-required="{{ translate('Store_address_is_required') }}"
    data-select-zone="{{ translate('You_must_select_a_zone') }}"
    data-map-latlong-required="{{ translate('Must_click_on_the_map_for_lat/long') }}"
    data-tax-required="{{ translate('tax_is_required') }}"
    data-pickup-zone-required="{{ translate('You_must_select_a_pickup_zone') }}"
    data-min-delivery-time-required="{{ translate('minimum_delivery_time_is_required') }}"
    data-max-delivery-time-required="{{ translate('max_delivery_time_is_required') }}"
    data-first-name-required="{{ translate('first_name_is_required') }}"
    data-last-name-required="{{ translate('last_name_is_required') }}"
    data-phone-required="{{ translate('valid_phone_number_is_required') }}"
    data-email-required="{{ translate('email_is_required') }}"
    data-password-required="{{ translate('password_is_required') }}"
    data-confirm-password-mismatch="{{ translate('confirm_password_does_not_match') }}"
    data-select_pickup_zone="{{ translate('select_pickup_zone') }}"

></div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=drawing,places&v=3.45.8">
    </script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-create.js')}}"></script>

@endpush
