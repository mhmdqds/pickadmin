@extends('layouts.admin.app')

@section('title', translate('messages.Update Provider'))

@push('css_or_js')
  <link rel="stylesheet" href="{{asset('Modules/Rental/public/assets/css/admin/provider-edit.css')}}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-20">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/rental/add_business_man_plus.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ $store->name}}
                    </h1></span>
                    </h1>
                </div>
            </div>
        </div>
        @php
            $delivery_time_start = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode('-', $store->delivery_time)[0]
                : 10;
            $delivery_time_end = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode(' ', explode('-', $store->delivery_time)[1])[0]
                : 30;
            $delivery_time_type = preg_match('([0-9]+[\-][0-9]+\s[min|hours|days])', $store->delivery_time ?? '')
                ? explode(' ', explode('-', $store->delivery_time)[1])[1]
                : 'min';
        @endphp
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)
        @php($defaultLang = 'en')
        <!-- End Page Header -->

        <form action="" method="post" enctype="multipart/form-data" id="providerFormSubmit">
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
                                        {{ translate('messages.Update the basic information of the provider ') }}
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
                                                        <div class="lang_form"
                                                             id="default-form">
                                                            <div class="form-group">
                                                                <label class="input-label"
                                                                       for="default_name">{{ translate('messages.name') }}
                                                                    ({{ translate('messages.Default') }})
                                                                </label>
                                                                <input type="text" name="name[]" id="default_name"
                                                                       class="form-control" placeholder="{{ translate('messages.provider_name') }}" value="{{$store->getRawOriginal('name')}}"
                                                                       required
                                                                >
                                                            </div>
                                                            <input type="hidden" name="lang[]" value="default">
                                                            <div class="form-group mb-0">
                                                                <label class="input-label"
                                                                       for="exampleFormControlInput1">{{ translate('messages.address') }} ({{ translate('messages.default') }})</label>
                                                                <textarea type="text" name="address[]" placeholder="{{translate('messages.provider address')}}" class="form-control min-h-90px ckeditor">{{$store->getRawOriginal('address')}}</textarea>
                                                            </div>
                                                        </div>
                                                        @foreach (json_decode($language) as $lang)
                                                                <?php
                                                                if(count($store['translations'])){
                                                                    $translate = [];
                                                                    foreach($store['translations'] as $t)
                                                                    {
                                                                        if($t->locale == $lang && $t->key=="name"){
                                                                            $translate[$lang]['name'] = $t->value;
                                                                        }
                                                                        if($t->locale == $lang && $t->key=="address"){
                                                                            $translate[$lang]['address'] = $t->value;
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                            <div class="d-none lang_form"
                                                                 id="{{ $lang }}-form">
                                                                <div class="form-group">
                                                                    <label class="input-label"
                                                                           for="{{ $lang }}_name">{{ translate('messages.name') }}
                                                                        ({{ strtoupper($lang) }})
                                                                    </label>
                                                                    <input type="text" name="name[]" id="{{ $lang }}_name"
                                                                           class="form-control" value="{{ $translate[$lang]['name']??'' }}" placeholder="{{ translate('messages.provider_name') }}"
                                                                    >
                                                                </div>
                                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                                                <div class="form-group mb-0">
                                                                    <label class="input-label"
                                                                           for="exampleFormControlInput1">{{ translate('messages.address') }} ({{ strtoupper($lang) }})</label>
                                                                    <textarea type="text" name="address[]" placeholder="{{translate('messages.provider address')}}" class="form-control min-h-90px ckeditor">{{ $translate[$lang]['address']??'' }}</textarea>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @else
                                                        <div id="default-form">
                                                            <div class="form-group">
                                                                <label class="input-label"
                                                                       for="exampleFormControlInput1">{{ translate('messages.name') }} ({{ translate('messages.default') }})</label>
                                                                <input type="text" name="name[]" class="form-control"
                                                                       placeholder="{{ translate('messages.provider_name') }}" required>
                                                            </div>
                                                            <input type="hidden" name="lang[]" value="default">
                                                            <div class="form-group mb-0">
                                                                <label class="input-label"
                                                                       for="exampleFormControlInput1">{{ translate('messages.address') }}
                                                                </label>
                                                                <textarea type="text" name="address[]" placeholder="{{translate('messages.provider address')}}" class="form-control min-h-90px ckeditor"></textarea>
                                                            </div>
                                                        </div>
                                                    @endif
                                            </div>
                                        </div>

                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-flex flex-column flex-sm-row gap-4">
                                            <div class="__custom-upload-img mr-lg-5">
                                                @php($logo = \App\Models\BusinessSetting::where('key', 'logo')->first())
                                                @php($logo = $logo->value ?? '')
                                                <label class="form-label mb-1">
                                                    {{ translate('logo') }}
                                                </label>
                                                <div class="mb-20">
                                                    <p class="fs-12 max-width-170px">{{ translate('JPG, JPEG, PNG Less Than 2MB') }} <strong
                                                            class="font-semibold">({{ translate('Ratio 1:1') }})</strong></p>
                                                </div>
                                                <label class="text-center position-relative">
                                                    <img class="img--110 min-height-170px min-width-170px onerror-image image--border" id="logoImageViewer"
                                                         data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                         src="{{ $store->logo_full_url ?? asset('public/assets/admin/img/upload-img.png') }}"
                                                         alt="logo image" />
                                                    <div class="icon-file-group">
                                                        <div class="icon-file">
                                                            <i class="tio-edit"></i>
                                                            <input type="file" name="logo" id="customFileEg1" class="custom-file-input"
                                                                   accept=".webp, .jpg, .png, .jpeg|image/*">
                                                        </div>
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
                                                <label class="text-center position-relative">
                                                    <img class="img--vertical min-height-170px min-width-170px onerror-image image--border" id="coverImageViewer"
                                                         data-onerror-image="{{ asset('public/assets/admin/img/upload-img.png') }}"
                                                         src="{{ $store->cover_photo_full_url ?? asset('public/assets/admin/img/upload-img.png') }}"
                                                         alt="Fav icon" />
                                                    <div class="icon-file-group">
                                                        <div class="icon-file">
                                                            <i class="tio-edit"></i>
                                                            <input type="file" name="cover_photo" id="coverImageUpload"  class="custom-file-input"
                                                                   accept=".webp, .jpg, .png, .jpeg|image/*">
                                                        </div>
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
                                        {{ translate('messages.Update the necessary information to operate the business') }}
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
                                            <select name="zone_id" id="choice_zones" data-zone-coordinates-url="{{ route('admin.zone.get-coordinates', ['id' => 'PLACEHOLDER_ID']) }}" required
                                                    class="form-control js-select2-custom"
                                                    data-placeholder="{{ translate('messages.select_zone') }}">
                                                <option value="" selected disabled>{{ translate('messages.select_zone') }}</option>
                                                @foreach(\App\Models\Zone::active()->get() as $zone)
                                                    @if(isset(auth('admin')->user()->zone_id))
                                                        @if(auth('admin')->user()->zone_id == $zone->id)
                                                            <option value="{{$zone->id}}" {{$store->zone_id == $zone->id? 'selected': ''}}>{{$zone->name}}</option>
                                                        @endif
                                                    @else
                                                        <option value="{{$zone->id}}" {{$store->zone_id == $zone->id? 'selected': ''}}>{{$zone->name}}</option>
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
                                            <select name="pickup_zones[]" id="pickup_zones" class="form-control basic-multiple-select2" multiple="multiple">
                                                @foreach ($zones as $zone)
                                                    <?php
                                                        $pickupZoneIds = json_decode($store->pickup_zone_id) ?? [];
                                                    ?>

                                                    @if (in_array($zone->id, $pickupZoneIds))
                                                        <option value="{{ $zone->id }}" selected>{{ $zone->name }}</option>
                                                    @else
                                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="input-label" for="latitude">{{translate('messages.latitude')}}
                                                <span
                                                    class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{translate('messages.provider_lat_lng_warning')}}">
                                                <img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.provider_lat_lng_warning')}}">
                                            </span>
                                            </label>
                                            <input type="text" id="latitude"
                                                   name="latitude" class="form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} -94.22213" value="{{$store->latitude}}" required readonly>
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="input-label" for="longitude">{{translate('messages.longitude')}}
                                                <span
                                                    class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{translate('messages.provider_lat_lng_warning')}}">
                                                <img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.provider_lat_lng_warning')}}">
                                            </span>
                                            </label>
                                            <input type="text"
                                                   name="longitude" class="form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} 103.344322" id="longitude" value="{{$store->longitude}}" required readonly>
                                        </div>
                                        <div class="form-group mb-5">
                                            <label class="input-label font-semibold"
                                                   for="tax">{{ translate('messages.Vat/Tax') }}
                                                (%)</label>
                                            <input type="number" name="tax" class="form-control"
                                                   placeholder="{{ translate('messages.vat/tax') }}" min="0"
                                                   step=".01" required value="{{$store->tax}}">
                                        </div>
                                        <div class="position-relative">
                                            <label class="input-label font-semibold"
                                                   for="tax">{{ translate('Approx. Pickup Time') }}</label>
                                            <div class="custom-group-btn">
                                                <div class="item flex-sm-grow-1">
                                                    <label class="floating-label"
                                                           for="min">{{ translate('Min') }}:</label>
                                                    <input id="min" type="number" name="minimum_delivery_time"
                                                           value="{{ $delivery_time_start }}"
                                                           class="form-control h--45px border-0"
                                                           placeholder="{{ translate('messages.Ex :') }} 20"
                                                           pattern="^[0-9]{2}$" required>
                                                </div>
                                                <div class="separator"></div>
                                                <div class="item flex-sm-grow-1">
                                                    <label class="floating-label"
                                                           for="max">{{ translate('Max') }}:</label>
                                                    <input id="max" type="number" name="maximum_delivery_time"
                                                           value="{{ $delivery_time_end }}"
                                                           class="form-control h--45px border-0"
                                                           placeholder="{{ translate('messages.Ex :') }} 30" pattern="[0-9]{2}$"
                                                           required>
                                                </div>
                                                <div class="separator"></div>
                                                <div class="item flex-shrink-0">
                                                    <select name="delivery_time_type" id="delivery_time_type"
                                                            class="custom-select border-0">
                                                        <option value="min"
                                                            {{ $delivery_time_type == 'min' ? 'selected' : '' }}>
                                                            {{ translate('messages.minutes') }}
                                                        </option>
                                                        <option value="hours"
                                                            {{ $delivery_time_type == 'hours' ? 'selected' : '' }}>
                                                            {{ translate('messages.hours') }}
                                                        </option>
                                                        <option value="days"
                                                            {{ $delivery_time_type == 'days' ? 'selected' : '' }}>
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
                                        {{ translate('messages.Update the information of the Owner who operate the business') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="f_name">{{translate('messages.first_name')}}</label>
                                            <input type="text" name="f_name" class="form-control" placeholder="{{translate('messages.first_name')}}"
                                                   value="{{$store->vendor->f_name}}"  required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="l_name">{{translate('messages.last_name')}}</label>
                                            <input type="text" name="l_name" class="form-control" placeholder="{{translate('messages.last_name')}}"
                                                   value="{{$store->vendor->l_name}}"  required>
                                        </div>
                                    </div>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="phone">{{translate('messages.phone')}}</label>
                                            <input type="tel" id="phone" name="phone" class="form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} 017********" value="{{$store->vendor->phone}}"
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
                                        {{ translate('messages.Update the necessary information to account information') }}
                                    </p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4 col-sm-6">
                                        <div class="form-group mb-0">
                                            <label class="input-label"
                                                   for="exampleFormControlInput1">{{ translate('messages.email') }}</label>
                                            <input type="email" name="email" class="form-control"
                                                   placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                                   value="{{$store->email}}" required>
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
                            <button type="submit"
                                    class="btn btn--primary min-w-100px justify-content-center">{{ translate('messages.update & next') }}</button>
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
            data-store-lat="{{ $store->latitude }}"
            data-store-lng="{{ $store->longitude }}"
            data-store-name="{{ $store->name }}"
            data-store-logo="{{ $store->logo_full_url }}"
            data-store-cover-photo="{{ $store->cover_photo_full_url }}"
            data-store-zone-id="{{ $store->zone_id }}"

            data-password-valid="{{ translate('Password is valid') }}"
            data-password-invalid="{{ translate('Password format is invalid') }}"
            data-password-matched="{{ translate('Passwords Matched') }}"
            data-confirm-password-mismatch="{{ translate('confirm_password_does_not_match') }}"

    ></div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=drawing,places&v=3.45.8">
    </script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-edit.js')}}"></script>

@endpush
