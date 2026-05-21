@extends('layouts.landing.app')
@section('title', translate('messages.vendor_registration'))
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('public/assets/admin/vendor/icon-set/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/owl.min.css') }}"/>

    <style>
        .password-feedback {
            display: none;
            width: 100%;
            margin-top: .25rem;
            font-size: .875em;

        }

        .valid {
            color: green;
        }

        .invalid {
            color: red;
        }

        .pickup-zone-container {
            display: none;
        }

        .capcha-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
.capcha-spin:not(.active) {
    animation-play-state: paused;
    -webkit-animation-play-state: paused;
    -moz-animation-play-state: paused;
}

    </style>
@endpush
@section('content')
    <!-- Page Hero Banner -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.vendor') }} {{ translate('messages.registration') }}</h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">{{ translate('messages.home') }}</a> / {{ translate('messages.vendor') }} {{ translate('messages.registration') }}
            </div>
        </div>
    </section>

    <section class="reg-section">
        <div class="reg-container" style="max-width:1060px">
            @php($language = \App\CentralLogics\Helpers::get_business_settings('language'))

            <!-- Stepper -->
            <div class="stepper" style="display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:32px;flex-wrap:wrap">
                <div class="stepper-step active" id="show-step1">
                    <div class="stepper-circle">1</div>
                    <div class="stepper-label">{{ translate('General Info') }}</div>
                </div>
                <div class="stepper-connector"></div>
                <div class="stepper-step" id="show-step2">
                    <div class="stepper-circle">2</div>
                    <div class="stepper-label">{{ translate('Business Plan') }}</div>
                </div>
                <div class="stepper-connector"></div>
                <div class="stepper-step">
                    <div class="stepper-circle">3</div>
                    <div class="stepper-label">{{ translate('Complete') }}</div>
                </div>
            </div>


            <form enctype="multipart/form-data" id="form-id">
                <div id="reg-form-div">
                    <div class="card __card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">
                                {{ translate('messages.vendor_info') }}
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            @if ($language)
                                <div class="js-nav-scroller tabs-slide-wrap position-relative hs-nav-scroller-horizontal mb-4">
                                    <ul class="nav nav-tabs tabs-inner text-nowrap store-apply-navs">
                                        <li class="nav-item">
                                            <a class="nav-link lang_link active" href="#"
                                                id="default-link">{{ translate('Default') }}</a>
                                        </li>
                                        @foreach ($language as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link" href="#"
                                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="arrow-area">
                                        <div class="button-prev align-items-center">
                                            <button type="button"
                                                class="btn btn-click-prev mr-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                <i class="tio-chevron-left fs-24"></i>
                                            </button>
                                        </div>
                                        <div class="button-next align-items-center">
                                            <button type="button"
                                                class="btn btn-click-next ml-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                <i class="tio-chevron-right fs-24"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if ($language)
                                <div class="lang_form mb-4" id="default-form">
                                    <input type="hidden" name="lang[]" value="default">
                                    <div class="row g-3">
                                        <div class="col-lg-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                        for="default_name">{{ translate('messages.business_name') }}
                                                    ({{ translate('messages.Default') }})<span
                                                        class="text-danger">*</span>
                                                </label>
                                                <input type="text" name="name[]"
                                                        value="{{ old('name.0') }}" id="default_name"
                                                        class="form-control __form-control"
                                                        placeholder="{{ translate('messages.business_name') }}"
                                                        maxlength="250" required>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                        for="address">{{ translate('messages.business_address') }}
                                                    ({{ translate('messages.default') }})<span
                                                        class="text-danger">*</span></label>
                                                <textarea id="address" name="address[]"
                                                            placeholder="{{ translate('Ex: ABC Company') }}"
                                                            class="form-control __form-control">{{ old('address.0') }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @foreach ($language as $key => $lang)
                                    <div class="d-none lang_form mb-4" id="{{ $lang }}-form">
                                        <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <div class="form-group mb-0">
                                                    <label class="input-label"
                                                            for="{{ $lang }}_name">{{ translate('messages.business_name') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="name[]"
                                                            value="{{ old('name.' . $key + 1) }}"
                                                            id="{{ $lang }}_name"
                                                            class="form-control __form-control"
                                                            placeholder="{{ translate('messages.business_name') }}">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group mb-0">
                                                    <label class="input-label"
                                                            for="address{{ $lang }}">{{ translate('messages.business_address') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <textarea id="address{{ $lang }}"
                                                                name="address[]"
                                                                placeholder="{{ translate('Ex: ABC Company') }}"
                                                                class="form-control __form-control">{{ old('address.' . $key + 1) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                            @php($zones = \App\Models\Zone::active()->get(['id', 'name']))

                            {{-- Zone / Module / Delivery time + Map --}}
                            <div class="row g-4 mb-4">
                                <div class="col-lg-6">
                                    <div class="form-group mb-3">
                                        <label class="input-label d-flex align-items-center gap-1" for="choice_zones">
                                            <span>{{ translate('messages.business_zone') }}<span class="text-danger">*</span></span>
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" class="reg-info-icon"
                                                 data-toggle="tooltip" data-placement="right"
                                                 title="{{ translate('messages.Select the zone from where the business will be operated') }}" alt="">
                                        </label>
                                        <select name="zone_id" id="choice_zones" required
                                                class="form-control __form-control js-select2-custom js-example-basic-single"
                                                data-placeholder="{{ translate('messages.select_zone') }}">
                                            <option value="" selected disabled>{{ translate('messages.select_zone') }}</option>
                                            @foreach ($zones as $zone)
                                                @if (isset(auth('admin')->user()->zone_id))
                                                    @if (auth('admin')->user()->zone_id == $zone->id)
                                                        <option value="{{ $zone->id }}" selected>{{ $zone->name }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-3 overflow-hidden">
                                        <label for="module_id" class="input-label">
                                            {{ translate('messages.business_module') }}<span class="text-danger">*</span>
                                            <small class="text-danger">({{ translate('messages.Select_zone_first') }})</small>
                                        </label>
                                        <select name="module_id" required id="module_id"
                                                class="js-data-example-ajax form-control __form-control overflow-hidden"
                                                data-placeholder="{{ translate('messages.select_module') }}">
                                        </select>
                                    </div>
                                    <div class="form-group mb-3 pickup-zone-container pickup-zone-tag" id="pickup-zone-container">
                                        <label class="input-label d-flex align-items-center gap-1" for="choice_zones">
                                            <span>{{ translate('messages.pickup_zone') }}<span class="text-danger">*</span></span>
                                            <img src="{{ asset('/public/assets/admin/img/info-circle.svg') }}" class="reg-info-icon"
                                                 data-toggle="tooltip" data-placement="right"
                                                 title="{{ translate('messages.Select zones from where customer can choose their pickup locations for trip booking') }}" alt="">
                                        </label>
                                        <select name="pickup_zone_id[]" required class="form-control multiple-select2"
                                                data-placeholder="{{ translate('messages.select_zone') }}" multiple="multiple">
                                            <option value="" disabled>{{ translate('messages.select_zone') }}</option>
                                            @foreach ($zones as $zone)
                                                @if (isset(auth('admin')->user()->zone_id))
                                                    @if (auth('admin')->user()->zone_id == $zone->id)
                                                        <option value="{{ $zone->id }}" selected>{{ $zone->name }}</option>
                                                    @endif
                                                @else
                                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="input-label module-select-time d-block mb-1">
                                            {{ translate('messages.approx_delivery_time') }}<span class="text-danger">*</span>
                                        </label>
                                        <div class="delivery-time-group">
                                            <span class="delivery-time-label">{{ translate('messages.min') }}:</span>
                                            <input type="number" id="minimum_delivery_time" name="minimum_delivery_time"
                                                   class="delivery-time-input" placeholder="10"
                                                   value="{{ old('minimum_delivery_time') }}">
                                            <span class="delivery-time-divider"></span>
                                            <span class="delivery-time-label">{{ translate('messages.max') }}:</span>
                                            <input type="number" name="maximum_delivery_time" id="max_delivery_time"
                                                   class="delivery-time-input" placeholder="20"
                                                   value="{{ old('maximum_delivery_time') }}">
                                            <select name="delivery_time_type" class="delivery-time-unit" required>
                                                <option value="min">{{ translate('messages.minutes') }}</option>
                                                <option value="hours">{{ translate('messages.hours') }}</option>
                                                <option value="days">{{ translate('messages.days') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="rounded map_custom-controls position-relative">
                                        <input id="pac-input" class="controls rounded initial-8" type="text"
                                               title="{{ translate('messages.search_your_location_here') }}"
                                               placeholder="{{ translate('messages.search_here') }}"/>
                                        <div class="h-280" id="map"></div>
                                        <div class="d-flex bg-white align-items-center gap-1 laglng-controller">
                                            <div id="latlng" class="d-flex">
                                                <input type="text" class="border-0 outline-0" id="latitude" name="latitude"
                                                       placeholder="{{ translate('messages.Ex:_-94.22213') }}"
                                                       value="{{ old('latitude') }}" required readonly>
                                                <span class="text-gray1">|</span>
                                                <input type="text" class="border-0 outline-0" name="longitude" id="longitude"
                                                       placeholder="{{ translate('messages.Ex:_103.344322') }}"
                                                       value="{{ old('longitude') }}" required readonly>
                                            </div>
                                        </div>
                                        <div id="outOfZone" class="map-alert bg-dark d-flex align-items-center rounded-8 py-2 px-2 fs-12 text-white mb-2">
                                            <img src="{{ asset('public/assets/admin/img/icons/warning-cus.png') }}" alt="">
                                            {{ translate('messages.Please place the marker inside the available zone area.') }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Cover photo + Logo --}}
                            <div class="row g-4">
                                <div class="col-sm-8">
                                    <div class="form-group mb-0">
                                        <label class="input-label d-block mb-1">{{ translate('messages.business_cover') }} <span class="text-danger">*</span> <span style="font-weight:400;color:var(--text);font-size:.75rem">({{ translate('messages.ratio') }} 2:1)</span></label>
                                        <div class="upload-area" id="coverUploadArea" onclick="document.getElementById('coverImageUpload').click()">
                                            <img id="coverImageViewer" class="preview-img" src="" alt="" style="display:none">
                                            <div class="upload-placeholder">
                                                <div class="upload-icon">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                </div>
                                                <p><strong>{{ translate('Drop Here') }}</strong></p>
                                                <div class="upload-note">{{ translate('Drag & Drop or Click to upload') }} &middot; JPG, PNG ({{ translate('Max') }} 2MB)</div>
                                            </div>
                                            <div class="upload-change">{{ translate('Click to change image') }}</div>
                                            <input type="file" name="cover_photo" id="coverImageUpload" class="single_file_input" accept="{{ IMAGE_EXTENSION }}" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label d-block mb-1">{{ translate('messages.business_logo') }} <span class="text-danger">*</span> <span style="font-weight:400;color:var(--text);font-size:.75rem">({{ translate('messages.ratio') }} 1:1)</span></label>
                                        <div class="upload-area" id="logoUploadArea" onclick="document.getElementById('logoFileInput').click()">
                                            <img id="logoImageViewer" class="preview-img" src="" alt="" style="display:none">
                                            <div class="upload-placeholder">
                                                <div class="upload-icon">
                                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                </div>
                                                <p><strong>{{ translate('Drop Here') }}</strong></p>
                                                <div class="upload-note">{{ translate('Drag & Drop or Click to upload') }} &middot; JPG, PNG ({{ translate('Max') }} 2MB)</div>
                                            </div>
                                            <div class="upload-change">{{ translate('Click to change image') }}</div>
                                            <input type="file" name="logo" id="logoFileInput" class="single_file_input" accept="{{ IMAGE_EXTENSION }}" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card __card bg-F8F9FC mb-4">
                        <div class="card-header">
                                    <div>
                                        <h5 class="card-title">
                                            {{ translate('messages.owner_information') }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-4 col-lg-4 col-sm-12">
                                            <div class="form-group">
                                                <label class="input-label"
                                                       for="f_name">{{ translate('messages.first_name') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="f_name" name="f_name"
                                                       class="form-control __form-control"
                                                       placeholder="{{ translate('messages.first_name') }}"
                                                       value="{{ old('f_name') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-lg-4 col-sm-12">
                                            <div class="form-group">
                                                <label class="input-label"
                                                       for="l_name">{{ translate('messages.last_name') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="text" id="l_name" name="l_name"
                                                       class="form-control __form-control"
                                                       placeholder="{{ translate('messages.last_name') }}"
                                                       value="{{ old('l_name') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-lg-4 col-sm-12">
                                            <div class="form-group">
                                                <label class="input-label"
                                                       for="phone">{{ translate('messages.phone') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="tel" id="phone" name="phone"
                                                       class="form-control __form-control"
                                                       placeholder="{{ translate('messages.Ex:') }} 017********"
                                                       value="{{ old('phone') }}" required>
                                            </div>


                                        </div>
                                    </div>
                                </div>
                            </div>
                    <div class="card __card mb-3">
                        <div class="card-header">
                            <h5 class="card-title">{{ translate('Business TIN') }}</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4 align-items-start">
                                <div class="col-md-8">
                                    <div class="form-group mb-3">
                                        <label class="input-label" for="tin">{{ translate('Taxpayer Identification Number(TIN)') }}</label>
                                        <input type="text" name="tin" id="tin"
                                               placeholder="{{ translate('Type Your Taxpayer Identification Number(TIN)') }}"
                                               class="form-control __form-control">
                                    </div>
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="tin_expire_date">{{ translate('Expire Date') }}</label>
                                        <input type="date" name="tin_expire_date" id="tin_expire_date"
                                               class="form-control __form-control">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="input-label mb-1 d-block">{{ translate('TIN Certificate') }}</label>
                                    <div class="bg--secondary rounded single-document-uploaderwrap position-relative">
                                        <button type="button" id="doc_edit_btn"
                                            class="doc-action-btn doc-edit-btn" title="{{ translate('Change file') }}" style="display:none">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <button type="button" id="reset-btn"
                                            class="doc-action-btn doc-remove-btn" title="{{ translate('Remove file') }}" style="display:none">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                        <div id="file-assets"
                                             data-picture-icon="{{ asset('public/assets/admin/img/picture.svg') }}"
                                             data-document-icon="{{ asset('public/assets/admin/img/document.svg') }}"
                                             data-blank-thumbnail="{{ asset('public/assets/admin/img/picture.svg') }}">
                                        </div>
                                        <div class="d-flex justify-content-center" id="pdf-container">
                                            <div class="document-upload-wrapper" id="doc-upload-wrapper">
                                                <input type="file" name="tin_certificate_image"
                                                       class="document_input"
                                                       accept=".doc, .pdf, .jpg, .png, .jpeg">
                                                <div class="textbox">
                                                    <svg class="upload-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                        <polyline points="16 16 12 12 8 16"></polyline>
                                                        <line x1="12" y1="12" x2="12" y2="21"></line>
                                                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"></path>
                                                    </svg>
                                                    <p class="fs-12 mb-0">
                                                        {{ translate('messages.Select_a_file_or') }} <span class="font-semibold">{{ translate('messages.Drag & Drop') }}</span> {{ translate('messages.here') }}
                                                    </p>
                                                    <p class="fs-12 mb-0" style="color:var(--text);opacity:.6">{{ translate('pdf, doc, jpg. File size : max 2 MB') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                            <div class="card __card bg-F8F9FC mb-3">
                                <div class="card-header">
                                    <div>
                                        <h5 class="card-title">
                                            {{ translate('messages.account_information') }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-4 col-sm-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="input-label"
                                                       for="email">{{ translate('messages.email') }}<span
                                                        class="text-danger">*</span></label>
                                                <input type="email" id="email" name="email"
                                                       class="form-control __form-control"
                                                       placeholder="{{ translate('messages.Ex:') }} ex@example.com"
                                                       value="{{ old('email') }}" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="input-label" for="exampleInputPassword">{{ translate('messages.password') }}
                                                    <span class="text-danger">*</span>
                                                </label>
                                                <label class="position-relative m-0 d-block">
                                                    <input type="password" name="password"
                                                           placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                           class="form-control __form-control form-control __form-control-user"
                                                           minlength="6" id="exampleInputPassword" required
                                                           value="{{ old('password') }}">
                                                    <span class="show-password">
                                                        <span class="icon-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1.5"
                                                                 stroke="currentColor" class="size-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                            </svg>
                                                        </span>
                                                        <span class="icon-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1.5"
                                                                 stroke="currentColor" class="size-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                                            </svg>
                                                        </span>
                                                    </span>
                                                </label>
                                                <div id="password-rules" style="display:none;margin-top:6px">
                                                    <ul class="fs-12 d-flex flex-wrap gap-1 list-unstyled mb-0">
                                                        <li id="rule-length"><i class="text-danger">&#10060;</i> {{ translate('8+ characters') }}</li>
                                                        <li id="rule-lower"><i class="text-danger">&#10060;</i> {{ translate('Lowercase letter') }}</li>
                                                        <li id="rule-upper"><i class="text-danger">&#10060;</i> {{ translate('Uppercase letter') }}</li>
                                                        <li id="rule-number"><i class="text-danger">&#10060;</i> {{ translate('Number') }}</li>
                                                        <li id="rule-symbol"><i class="text-danger">&#10060;</i> {{ translate('Symbol') }}</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 col-sm-12 col-lg-4">
                                            <div class="form-group">
                                                <label class="input-label"
                                                       for="exampleRepeatPassword">{{ translate('messages.confirm_password') }}
                                                    <span
                                                        class="text-danger">*</span></label>
                                                <label class="position-relative m-0 d-block">
                                                    <input type="password" name="confirm-password"
                                                           class="form-control __form-control form-control __form-control-user"
                                                           minlength="6" id="exampleRepeatPassword"
                                                           placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}"
                                                           required value="{{ old('confirm-password') }}">
                                                    <span class="show-password">
                                                        <span class="icon-2">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1.5"
                                                                 stroke="currentColor" class="size-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                            </svg>
                                                        </span>
                                                        <span class="icon-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                 viewBox="0 0 24 24" stroke-width="1.5"
                                                                 stroke="currentColor" class="size-6">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                      d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                                            </svg>
                                                        </span>
                                                    </span>
                                                </label>
                                                <div id="confirm-pw-error" style="display:none;color:#e74c3c;font-size:.8rem;margin-top:4px">
                                                    {{ translate('messages.password_not_matched') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-5">
                                        <div class="col-md-6 col-lg-4">
                                            @include('admin-views.partials._recaptcha')
                                            {{-- @php($recaptcha = \App\CentralLogics\Helpers::get_business_settings('recaptcha'))
                                            @if (isset($recaptcha) && $recaptcha['status'] == 1)
                                                <input type="hidden" name="g-recaptcha-response"
                                                       id="g-recaptcha-response">
                                            @else
                                                <div class="row g-3">
                                                    <div class="col-6">
                                                        <input type="text" class="form-control"
                                                               name="custome_recaptcha" id="custome_recaptcha" required
                                                               placeholder="{{ translate('Enter recaptcha value') }}"
                                                               autocomplete="off"
                                                               value="{{ env('APP_DEBUG') ? session('six_captcha') : '' }}">
                                                    </div>
                                                    <div class="col-6 recap-img-div">
                                                        <img src="{!! $custome_recaptcha->inline() ?? '' !!}"
                                                             alt="image"
                                                             class="recap-img"/>
                                                    </div>
                                                </div>
                                            @endif --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end pt-4 d-flex flex-wrap justify-content-end gap-3">
                                <button type="reset" id='form-reset-btn'
                                        class="btn-reset">{{ translate('Reset') }}</button>
                                <button
                                    type="{{ \App\CentralLogics\Helpers::subscription_check() == 1 ? 'button' : 'submit' }}"
                                    id="show-business-plan-div"
                                    class="btn-next btn-disable">
                                    <span class="btn-text">{{ \App\CentralLogics\Helpers::subscription_check() == 1 ? translate('Next') : translate('messages.submit') }}</span>
                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                                </button>
                            </div>

                </div>

                @if (\App\CentralLogics\Helpers::subscription_check())
                    <div class="d-none" id="business-plan-div">
                        <div class="card __card mb-3">
                            <div class="card-header border-0">
                                <h5 class="card-title text-center">
                                    {{ translate('Choose Your Business Plan') }}
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row">
                                    @if (\App\CentralLogics\Helpers::commission_check())
                                        <div class="col-sm-6">
                                            <label class="plan-check-item pb-3 pb-sm-0">
                                                <input type="radio" name="business_plan" value="commission-base"
                                                       class="d-none" checked>
                                                <div class="plan-check-item-inner">
                                                    <div
                                                        class="d-flex gap-3 justify-content-between align-items-center mb-10">
                                                        <h5 class="mb-0">{{ translate('Commision_Base') }}</h5>
                                                        <span class="checkmark">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                                 height="16" fill="currentColor" class="bi bi-check2"
                                                                 viewBox="0 0 16 16">
                                                                <path
                                                                    d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <p>
                                                        {{ translate('vendor will pay') }} {{ $admin_commission }}%
                                                        {{ translate('commission to') }} {{ $business_name }}
                                                        {{ translate('from each order. You will get access of all the features and options  in vendor panel , app and interaction with user.') }}
                                                    </p>
                                                </div>
                                            </label>
                                        </div>
                                    @endif
                                    <div class="col-sm-6">
                                        <label class="plan-check-item">
                                            <input type="radio" name="business_plan" value="subscription-base"
                                                   class="d-none">
                                            <div class="plan-check-item-inner">
                                                <div
                                                    class="d-flex gap-3 justify-content-between align-items-center mb-10">
                                                    <h5 class="mb-0">{{ translate('Subscription_Base') }}</h5>
                                                    <span class="checkmark">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16"
                                                             height="16" fill="currentColor" class="bi bi-check2"
                                                             viewBox="0 0 16 16">
                                                            <path
                                                                d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <p>
                                                    {{ translate('Run vendor by puchasing subsciption packages. You will have access the features of in vendor panel , app and interaction with user according to the subscription packages.') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                <div id="subscription-plan" style="display: none">
                                    <br>
                                    <div class="card-header px-0 m-0 border-0 py-2">
                                        <h5 class="card-title text-center">
                                            {{ translate('Choose Subscription Package') }}
                                        </h5>
                                    </div>
                                    <div id='show_sub_packages'>
                                        @include('vendor-views.auth._package_data', [
                                            'packages' => $packages,
                                        ])
                                    </div>


                                </div>
                            </div>
                            <div class="terms-check mt-3 px-4">
                                <input type="checkbox" id="businessTerms" required />
                                <label for="businessTerms">{{ translate('messages.i_agree_to_the') }} <a href="{{ route('terms-and-conditions') }}" target="_blank">{{ translate('messages.terms_and_condition') }}</a> {{ translate('messages.and') }} <a href="{{ route('privacy-policy') }}" target="_blank">{{ translate('messages.privacy_policy') }}</a></label>
                            </div>
                            <div class="text-end pt-3 d-flex flex-wrap p-4 justify-content-end gap-3">
                                <button type="button" id="back-to-form"
                                        class="btn-back">{{ translate('Back') }}</button>
                                <button type="submit" id="generalSubmitBtn"
                                        class="btn-next btn-disable" disabled>
                                    <span class="btn-text">{{ translate('Next') }}</span>
                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </form>
        </div>


    </section>

<div class="d-none" id="default-text-data"
     data-default-filesize="{{ translate('File size must be less than') }}"
     data-default-allowedformat="{{ translate('Invalid file type. Allowed: PDF, DOC, JPG, PNG') }}">
</div>
@endsection
@push('script_2')

    @php($default_location = \App\Models\BusinessSetting::where('key', 'default_location')->first())
    @php($default_location = $default_location->value ? json_decode($default_location->value, true) : 0)

    <script>
         const getAllModules ="{{ route('restaurant.get-all-modules') }}";
         const getModuleType ="{{ route('restaurant.get-module-type') }}";
         const checkModuleTypeUrl ="{{ route('restaurant.check-module-type') }}";
        const estimatedPickupText =
        "{{ translate('messages.Estimated_pickup_time') }} <span class='text-danger'>*</span>";
        const approxDeliveryText =
        "{{ translate('messages.approx_delivery_time') }} <span class='text-danger'>*</span>";

        // Reset all button loaders
        function resetButtonLoaders() {
            $('.btn-text').removeClass('d-none');
            $('.btn-loader').addClass('d-none');
        }



        window.mapConfig = {
            mapApiKey: "{{ \App\CentralLogics\Helpers::get_business_settings('map_api_key') }}",
            defaultLocation: {!! json_encode($default_location) !!},
            oldLat: parseFloat("{{ old('latitude') }}"),
            oldLng: parseFloat("{{ old('longitude') }}"),
            oldZoneId: "{{ old('zone_id') }}",
            oldAddress: @json(old('address.0')),
            translations: {
                selectedLocation: "{{ translate('Selected Location') }}",
                clickMap: "{{ translate('Click_the_map_inside_the_red_marked_area_to_get_Lat/Lng!!!') }}",
                selectZone: "{{ translate('Select_Zone_From_The_Dropdown') }}",
                geolocationError: "{{ translate('Error:_Your_browser_doesnot_support_geolocation.') }}",
                outOfZone: "{{ translate('messages.out_of_coverage') }}",
            },
            urls: {
                zoneCoordinates: "{{ route('admin.zone.get-coordinates', ['id' => ':coordinatesZoneId']) }}",
                zoneGetZone: "{{ route('admin.zone.get-zone') }}",
            }
        };
    </script>

    <script src="{{ asset('public/assets/landing/js/owl.min.js') }}"></script>
    <script>
        // Initialize package slider now that owl.min.js is loaded
        if (typeof window._initPackageSlider === 'function') window._initPackageSlider();
    </script>
    <script src="{{ asset('public/assets/admin/js/file-preview/pdf.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/file-preview/pdf-worker.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/file-preview/store-join-us.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/view-pages/map-functionality.js') }}"></script>

    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\CentralLogics\Helpers::get_business_settings('map_api_key') }}&libraries=drawing,places,marker,geometry&v=3.61&language={{ str_replace('_', '-', app()->getLocale()) }}&callback=initMap"
        async defer>
    </script>


    @if (isset($recaptcha) && $recaptcha['status'] == 1)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha['site_key'] }}"></script>
    @endif


<script>
$("#form-id").on('submit', function(e) {
    e.preventDefault();

    @if (isset($recaptcha) && $recaptcha['status'] == 1)
    grecaptcha.ready(function() {
        grecaptcha.execute('{{ $recaptcha['site_key'] }}', {action: 'submit'}).then(function(token) {

            if ($("#g-recaptcha-response").length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'g-recaptcha-response',
                    name: 'g-recaptcha-response',
                    value: token
                }).appendTo('#form-id');
            } else {
                $("#g-recaptcha-response").val(token);
            }

            submitForm();
        });
    });
    @else
    submitForm();
    @endif
});

function submitForm() {

    @if (\App\CentralLogics\Helpers::subscription_check())
    const radios = document.querySelectorAll('input[name="business_plan"]');
    let selectedValue = null;
    for (const radio of radios) {
        if (radio.checked) {
            selectedValue = radio.value;
            break;
        }
    }

    if (!selectedValue) {
        toastr.error("{{ translate('messages.please_select_business_plan') }}");
        return;
    }

    if (selectedValue === 'subscription-base') {
        const package_radios = document.querySelectorAll('input[name="package_id"]');
        let selectedpValue = null;
        for (const pradio of package_radios) {
            if (pradio.checked) {
                selectedpValue = pradio.value;
                break;
            }
        }

        if (!selectedpValue) {
            toastr.error("{{ translate('You_must_select_a_package') }}");
            return;
        }
    }
    @endif

    $('.btn-disable').prop('disabled', true);

    let formData = new FormData(document.getElementById('form-id'));
    @if (!\App\CentralLogics\Helpers::subscription_check())
    formData.append('business_plan', 'commission-base');
    @endif
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        }
    });

    $.post({
        url: '{{ route('restaurant.store') }}',
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function () {
            $('#loading').show();
            $('#show-business-plan-div .btn-text').addClass('d-none');
            $('#show-business-plan-div .btn-loader').removeClass('d-none');
        },
        success: function (data) {
            $('#loading').hide();
            resetButtonLoaders();
            if (data.errors) {
                $('.btn-disable').prop('disabled', false);
                if (!$('#businessTerms').is(':checked')) $('#generalSubmitBtn').prop('disabled', true);
                for (let i = 0; i < data.errors.length; i++) {
                    toastr.error(data.errors[i].message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            } else {
                toastr.success("{{ translate('your_store_registration_is_successful') }}", {
                    CloseButton: true,
                    ProgressBar: true
                });
                setTimeout(function () {
                    location.href = data.redirect_url;
                }, 1000);
            }
        },
        error: function () {
            $('#loading').hide();
            $('.btn-disable').prop('disabled', false);
            resetButtonLoaders();
        }
    });
}
</script>



    <script>

        function updateRule(el, valid) {
            if (!el) return;
            const icon = el.querySelector('i');
            if (icon) { icon.className = valid ? 'text-success' : 'text-danger'; icon.innerHTML = valid ? '&#10004;' : '&#10060;'; }
        }

        $(document).on('input', '#exampleInputPassword', function () {
            const val = $(this).val();
            const rules = document.getElementById('password-rules');
            if (!rules) return;
            rules.style.display = val.length > 0 ? 'block' : 'none';
            updateRule(document.getElementById('rule-length'), val.length >= 8);
            updateRule(document.getElementById('rule-lower'),  /[a-z]/.test(val));
            updateRule(document.getElementById('rule-upper'),  /[A-Z]/.test(val));
            updateRule(document.getElementById('rule-number'), /[0-9]/.test(val));
            updateRule(document.getElementById('rule-symbol'), /[!@#$%^&*(),.?":{}|<>]/.test(val));
        }).on('blur', '#exampleInputPassword', function () {
            if (!$(this).val()) $('#password-rules').hide();
        });

        $(document).on('input', '#exampleRepeatPassword', function () {
            const val = $(this).val();
            const match = val === $('#exampleInputPassword').val();
            const $err = $('#confirm-pw-error');
            if (val.length > 0 && !match) {
                $err.show();
                $(this).css('border-color', '#e74c3c');
            } else {
                $err.hide();
                $(this).css('border-color', '');
            }
        });

        $(document).on('input', '#exampleInputPassword', function () {
            if ($('#exampleRepeatPassword').val()) {
                $('#exampleRepeatPassword').trigger('input');
            }
        });



        $('#show-business-plan-div').on('click', function (e) {
            const logo = $('input[name="logo"]')[0];
            const cover = $('input[name="cover_photo"]')[0];
            const tin_certificate_image = $('input[name="tin_certificate_image"]')[0];

            const maxFileSize = 2 * 1024 * 1024; // 2MB in bytes

            if (!$('#default_name').val()) {
                toastr.error("{{ translate('Vendor_name_is_required') }}");
                e.preventDefault();
            } else if (!$('#address').val()) {
                toastr.error("{{ translate('Vendor_address_is_required') }}");
                e.preventDefault();
            } else if (!logo.files.length) {
                toastr.error("{{ translate('Vendor_logo_required') }}");
                e.preventDefault();
            } else if (!cover.files.length) {
                toastr.error("{{ translate('Vendor_cover_photo_required') }}");
                e.preventDefault();
            } else if (logo.files[0].size > maxFileSize) {
                toastr.error("{{ translate('Vendor_logo_must_be_less_than_2MB') }}");
                e.preventDefault();
            } else if (tin_certificate_image.files.length && tin_certificate_image.files[0].size > maxFileSize) {
                toastr.error("{{ translate('Tin_certificate_must_be_less_than_2MB') }}");
                e.preventDefault();
            } else if (cover.files[0].size > maxFileSize) {
                toastr.error("{{ translate('Vendor_cover_photo_must_be_less_than_2MB') }}");
                e.preventDefault();
            } else if (!$('#choice_zones').val()) {
                toastr.error("{{ translate('You_must_select_a_zone') }}");
                e.preventDefault();
            } else if (!$('#module_id').val()) {
                toastr.error("{{ translate('You_must_select_a_module') }}");
                e.preventDefault();
            } else if (!$('#latitude').val() || !$('#longitude').val()) {
                toastr.error("{{ translate('Must_click_on_the_map_for_lat/long') }}");
                e.preventDefault();
            } else if (!$('#minimum_delivery_time').val()) {
                toastr.error("{{ translate('minimum_time_is_required') }}");
                e.preventDefault();
            } else if (!$('#max_delivery_time').val()) {
                toastr.error("{{ translate('max_time_is_required') }}");
                e.preventDefault();
            } else if (!$('#f_name').val()) {
                toastr.error("{{ translate('first_name_is_required') }}");
                e.preventDefault();
            } else if (!$('#l_name').val()) {
                toastr.error("{{ translate('last_name_is_required') }}");
                e.preventDefault();
            } else if ($('#phone').val().length < 5) {
                toastr.error("{{ translate('valid_phone_number_is_required') }}");
                e.preventDefault();
            } else if (!$('#email').val()) {
                toastr.error("{{ translate('email_is_required') }}");
                e.preventDefault();
            } else if (!$('#exampleInputPassword').val()) {
                toastr.error("{{ translate('password_is_required') }}");
                e.preventDefault();
            } else if ($('#exampleRepeatPassword').val() !== $('#exampleInputPassword').val()) {
                toastr.error("{{ translate('confirm_password_does_not_match') }}");
                e.preventDefault();
            } else if (!isPasswordStrong($('#exampleRepeatPassword').val()) && !isPasswordStrong($('#exampleInputPassword').val())) {
                toastr.error("{{ translate('Password format is invalid') }}");
                e.preventDefault();
            } else {
                e.preventDefault();
                $('.btn-disable').prop('disabled', true);
                $.get({
                    url: '{{ route('admin.zone.check-location') }}',
                    dataType: 'json',
                    data: {
                        zone_id: $('#choice_zones').val(),
                        latitude: $('#latitude').val(),
                        longitude: $('#longitude').val()
                    },
                    beforeSend: function () {
                        $('#loading').show();
                        $('.btn-disable').prop('disabled', true);
                        $('#show-business-plan-div .btn-text').addClass('d-none');
                        $('#show-business-plan-div .btn-loader').removeClass('d-none');
                    },
                    success: function (data) {
                        $('#loading').hide();
                        $('.btn-disable').prop('disabled', false);
                        if (!$('#businessTerms').is(':checked')) $('#generalSubmitBtn').prop('disabled', true);
                        resetButtonLoaders();
                        if (data.errors) {
                            for (let i = 0; i < data.errors.length; i++) {
                                toastr.error(data.errors[i].message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        } else {
                            @if (isset($recaptcha) && $recaptcha['status'] == 1)
                                if (typeof grecaptcha === 'undefined') {
                                    toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                                    return;
                                }
                                grecaptcha.ready(function () {
                                    grecaptcha.execute('{{$recaptcha['site_key']}}', {action: 'submit'}).then(function (token) {
                                        $('#g-recaptcha-response').val(token);

                                    });
                                });
                                window.onerror = function (message) {
                                    var errorMessage = 'An unexpected error occurred. Please check the recaptcha configuration';
                                    if (message.includes('Invalid site key')) {
                                        errorMessage = 'Invalid site key provided. Please check the recaptcha configuration.';
                                    } else if (message.includes('not loaded in api.js')) {
                                        errorMessage = 'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                                    }
                                    toastr.error(errorMessage)
                                    return true;
                                };
                            @endif


                            @if (\App\CentralLogics\Helpers::subscription_check())
                            $('#business-plan-div').removeClass('d-none');
                            $('#reg-form-div').addClass('d-none');
                            $('#show-step2').addClass('active');
                            $('#show-step1').removeClass('active');
                            $(window).scrollTop(0);
                            @else
                            $('#form-id').submit();
                            @endif
                        }
                    },
                    error: function () {
                        $('#loading').hide();
                        $('.btn-disable').prop('disabled', false);
                        if (!$('#businessTerms').is(':checked')) $('#generalSubmitBtn').prop('disabled', true);
                        resetButtonLoaders();
                    }
                });
            }
        });

        function isPasswordStrong(password) {
            const minLength = password.length >= 8;
            const hasLowerCase = /[a-z]/.test(password);
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            return minLength && hasLowerCase && hasUpperCase && hasNumber && hasSymbol;
        }


        $('#back-to-form').on('click', function () {
            $('#business-plan-div').addClass('d-none');
            $('#reg-form-div').removeClass('d-none');
            $('#show-step1').addClass('active');
            $('#show-step2').removeClass('active');
            $(window).scrollTop(0);
        })

        // Terms checkbox — enable/disable submit in business plan step
        $('#businessTerms').on('change', function () {
            $('#generalSubmitBtn').prop('disabled', !this.checked);
        });

        // Business plan toggle — show/hide subscription packages
        $(document).on('change', 'input[name="business_plan"]', function() {
            if ($(this).val() == 'subscription-base') {
                $('#subscription-plan').slideDown(300, function() {
                    if (typeof window._initPackageSlider === 'function') window._initPackageSlider();
                    if ($('.plan-slider').data('owl.carousel')) {
                        $('.plan-slider').trigger('refresh.owl.carousel');
                    }
                });
            } else {
                $('#subscription-plan').slideUp();
            }
        });

        // Package selection — delegated so it works after owl carousel reorganizes DOM
        $(document).on('change', 'input[name="package_id"]', function() {
            $('.__plan-item').removeClass('active');
            $(this).closest('.__plan-item').addClass('active');
        });

        // Handle tap on card — distinguish from swipe so carousel still works
        var _planTouchStart = null;
        $(document).on('touchstart', '.__plan-item', function(e) {
            _planTouchStart = { x: e.originalEvent.touches[0].pageX, y: e.originalEvent.touches[0].pageY };
        });
        $(document).on('touchend', '.__plan-item', function(e) {
            if (!_planTouchStart) return;
            var dx = Math.abs(e.originalEvent.changedTouches[0].pageX - _planTouchStart.x);
            var dy = Math.abs(e.originalEvent.changedTouches[0].pageY - _planTouchStart.y);
            _planTouchStart = null;
            if (dx > 10 || dy > 10) return; // was a swipe, not a tap
            var $radio = $(this).find('input[name="package_id"]');
            if ($radio.length && !$radio.is(':checked')) {
                $radio.prop('checked', true).trigger('change');
            }
        });
        // Desktop click fallback
        $(document).on('click', '.__plan-item', function(e) {
            if (e.originalEvent && e.originalEvent.pointerType === 'touch') return; // handled by touch events
            var $radio = $(this).find('input[name="package_id"]');
            if ($radio.length && !$radio.is(':checked')) {
                $radio.prop('checked', true).trigger('change');
            }
        });

        // Set initial subscription plan visibility
        $(function() {
            var checkedPlan = $('input[name="business_plan"]:checked').val();
            if (checkedPlan === 'subscription-base') {
                $('#subscription-plan').show();
                if (typeof window._initPackageSlider === 'function') window._initPackageSlider();
            } else {
                $('#subscription-plan').hide();
            }
        });

    </script>
    <script src="{{ asset('public/assets/landing/js/select2.min.js') }}"></script>

    <script>
        $(document).ready(function () {
            function handleImageUpload(inputSelector, imgViewerSelector, areaSelector) {
                const inputElement = $(inputSelector);
                const areaElement = $(areaSelector);

                inputElement.on('change', function () {
                    const file = this.files[0];
                    if (file) {
                        let acceptAttr = $(this).attr('accept') || '';
                        let validTypes = acceptAttr
                            ? acceptAttr.split(',').map(t => t.trim().toLowerCase())
                            : ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

                        const fileType = file.type.toLowerCase();
                        const fileExt = '.' + file.name.split('.').pop().toLowerCase();

                        const isValidType = validTypes.some(type => {
                            if (type.endsWith('/*')) return fileType.startsWith(type.replace('/*', ''));
                            if (type.includes('/')) return fileType === type;
                            return fileExt === type;
                        });

                        if (!isValidType) {
                            toastr.error("{{ translate('messages.Invalid file type. Please upload a supported image.') }}");
                            $(this).val('');
                            $(imgViewerSelector).attr('src', '').hide();
                            areaElement.removeClass('has-preview');
                            return;
                        }

                        if (file.size > 2 * 1024 * 1024) {
                            toastr.error("{{ translate('messages.Image size must be less than 2 MB') }}");
                            $(this).val('');
                            $(imgViewerSelector).attr('src', '').hide();
                            areaElement.removeClass('has-preview');
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function (e) {
                            $(imgViewerSelector).attr('src', e.target.result).show();
                            areaElement.addClass('has-preview');
                        };
                        reader.readAsDataURL(file);
                    } else {
                        $(imgViewerSelector).attr('src', '').hide();
                        areaElement.removeClass('has-preview');
                    }
                });

                // Drag-and-drop
                areaElement.on('dragover', function (e) {
                    e.preventDefault();
                    $(this).css('border-color', 'var(--green)');
                }).on('dragleave', function (e) {
                    e.preventDefault();
                    if (!$(this).hasClass('has-preview')) {
                        $(this).css('border-color', '');
                    }
                }).on('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const file = e.originalEvent.dataTransfer.files[0];
                    if (file) {
                        // Set the file to the input
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        inputElement[0].files = dt.files;
                        inputElement.trigger('change');
                    }
                });
            }

            handleImageUpload('#coverImageUpload', '#coverImageViewer', '#coverUploadArea');
            handleImageUpload('#logoFileInput', '#logoImageViewer', '#logoUploadArea');
        });
    </script>

    <script>
        $.fn.select2DynamicDisplay = function () {
            const limit = 10000;

            function updateDisplay($element) {
                var $rendered = $element
                    .siblings(".select2-container")
                    .find(".select2-selection--multiple")
                    .find(".select2-selection__rendered");
                var $container = $rendered.parent();
                var containerWidth = $container.width();
                var totalWidth = 0;
                var itemsToShow = [];
                var remainingCount = 0;

                // Get all selected items
                var selectedItems = $element.select2("data");

                var $tempContainer = $("<div>")
                    .css({
                        display: "inline-block",
                        padding: "0 15px",
                        "white-space": "nowrap",
                        visibility: "hidden",
                    })
                    .appendTo($container);

                selectedItems.forEach(function (item) {
                    var $tempItem = $("<span>")
                        .text(item.text)
                        .css({
                            display: "inline-block",
                            padding: "0 12px",
                            "white-space": "nowrap",
                        })
                        .appendTo($tempContainer);

                    var itemWidth = $tempItem.outerWidth(true);

                    if (totalWidth + itemWidth <= containerWidth - 40) {
                        totalWidth += itemWidth;
                        itemsToShow.push(item);
                    } else {
                        remainingCount = selectedItems.length - itemsToShow.length;
                        return false;
                    }
                });

                $tempContainer.remove();

                const $searchForm = $rendered.find(".select2-search");

                var html = "";
                itemsToShow.forEach(function (item) {
                    html += `<li class="name">
                                        <span>${item.text}</span>
                                        <span class="close-icon" data-id="${item.id}">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" class="bi bi-x-lg" viewBox="0 0 16 16">
                                                <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8z"/>
                                            </svg>
                                        </span>
                                        </li>`;
                });
                if (remainingCount > 0) {
                    html += `<li class="ms-auto">
                                        <div class="more">+${remainingCount}</div>
                                        </li>`;
                }

                if (selectedItems.length < limit) {
                    html += $searchForm.prop("outerHTML");
                }

                $rendered.html(html);

                function debounce(func, wait) {
                    let timeout;
                    return function (...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), wait);
                    };
                }

                $(".select2-search input").on(
                    "input",
                    debounce(function () {
                        const inputValue = $(this).val().toLowerCase();
                        const $listItems = $(".select2-results__options li");
                        let matches = 0;

                        $listItems.each(function () {
                            const itemText = $(this).text().toLowerCase();
                            const isMatch = itemText.includes(inputValue);
                            $(this).toggle(isMatch);
                            if (isMatch) matches++;
                        });

                        if (matches === 0) {
                            $(".select2-results__options").append(
                                '<li class="no-results">No results found</li>'
                            );
                        } else {
                            $(".no-results").remove();
                        }
                    }, 100)
                );

                $(".select2-search input").on("keydown", function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        const inputValue = $(this).val().toLowerCase();
                        const $listItems = $(".select2-results__options li:not(.no-results)");
                        const matchedItem = $listItems.filter(function () {
                            return $(this).text().toLowerCase() === inputValue;
                        });

                        if (matchedItem.length > 0) {
                            matchedItem.trigger("mouseup"); // Select the matched item
                        }

                        $(this).val("");
                    }
                });
            }

            return this.each(function () {
                var $this = $(this);

                $this.select2({
                    tags: true,
                    maximumSelectionLength: limit,
                });

                // Bind change event to update display
                $this.on("change", function () {
                    updateDisplay($this);
                });

                // Initial display update
                updateDisplay($this);

                $(window).on("resize", function () {
                    updateDisplay($this);
                });
                $(window).on("load", function () {
                    updateDisplay($this);
                });

                // Handle the click event for the remove icon
                $(document).on(
                    "click",
                    ".select2-selection__rendered .close-icon",
                    function (e) {
                        e.stopPropagation();
                        var $removeIcon = $(this);
                        var itemId = $removeIcon.data("id");
                        var $this2 = $removeIcon
                            .closest(".select2")
                            .siblings(".multiple-select2");
                        $this2.val(
                            $this2.val().filter(function (id) {
                                return id != itemId;
                            })
                        );
                        $this2.trigger("change");
                    }
                );
            });
        };
        $(".multiple-select2").select2DynamicDisplay();

        // Initialize Select2 for zone dropdown
        if (typeof $.fn.select2 !== 'undefined') {
            $('#choice_zones').select2({
                placeholder: "{{ translate('messages.select_zone') }}",
                allowClear: false
            });
        }
    </script>

    <script>
        const container = document.querySelector('.tabs-inner');
        const btnPrevWrap = document.querySelector('.button-prev');
        const btnNextWrap = document.querySelector('.button-next');
        const item = document.querySelector('.tabs-slide_items');

        document.querySelectorAll('.tabs-slide_items').forEach(el => {
            el.style.flex = '0 0 auto';
        });
        function updateArrows() {
            if (!container || !btnPrevWrap || !btnNextWrap) return;

            const hasOverflow = container.scrollWidth > container.clientWidth;
            if (!hasOverflow) {
                btnPrevWrap.style.display = 'none';
                btnNextWrap.style.display = 'none';
                return;
            }
            const scrollLeft = container.scrollLeft;
            const maxScroll = container.scrollWidth - container.clientWidth;

            if (scrollLeft > 2) {
                btnPrevWrap.style.display = 'flex';
            } else {
                btnPrevWrap.style.display = 'none';
            }

            if (scrollLeft < maxScroll - 2) {
                btnNextWrap.style.display = 'flex';
            } else {
                btnNextWrap.style.display = 'none';
            }
        }
        document.querySelector('.btn-click-prev')?.addEventListener('click', () => {
            const itemWidth = item?.offsetWidth || 100;
            container.scrollBy({ left: -itemWidth, behavior: 'smooth' });
        });
        document.querySelector('.btn-click-next')?.addEventListener('click', () => {
            const itemWidth = item?.offsetWidth || 100;
            container.scrollBy({ left: itemWidth, behavior: 'smooth' });
        });

        container.addEventListener('scroll', updateArrows);
        ['load', 'resize'].forEach(evt => window.addEventListener(evt, updateArrows));
        new MutationObserver(updateArrows).observe(container, { childList: true, subtree: true });
        new ResizeObserver(updateArrows).observe(container);

        // Initial update
        updateArrows();
    </script>

    <script>
        // TIN uploader: show/hide action buttons based on file selection
        (function () {
            const wrapper = document.getElementById('doc-upload-wrapper');
            const editBtn  = document.getElementById('doc_edit_btn');
            const removeBtn = document.getElementById('reset-btn');
            if (!wrapper || !editBtn || !removeBtn) return;

            function syncButtons() {
                const hasFile = wrapper.style.display === 'none';
                editBtn.style.display  = hasFile ? 'flex' : 'none';
                removeBtn.style.display = hasFile ? 'flex' : 'none';
            }

            new MutationObserver(syncButtons).observe(wrapper, { attributes: true, attributeFilter: ['style'] });
            syncButtons();
        })();
    </script>

    <script>
        // Password show/hide toggle
        $(document).on('click', '.show-password', function () {
            const $input = $(this).closest('label').find('input[type="password"], input[type="text"]');
            const isHidden = $input.attr('type') === 'password';
            $input.attr('type', isHidden ? 'text' : 'password');
            $(this).toggleClass('shown');
        });
    </script>

    <script>
        // Language tab switching
        $(document).on('click', '.lang_link', function(e) {
            e.preventDefault();
            $('.lang_link').removeClass('active');
            $('.lang_form').addClass('d-none');
            $(this).addClass('active');
            var form_id = this.id;
            var lang = form_id.substring(0, form_id.length - 5);
            $('#' + lang + '-form').removeClass('d-none');
        });
    </script>

    <script>
    $(document).on('click', '.reloadCaptcha', function () {
        $.ajax({
            url: "{{ route('reload-captcha') }}",
            type: "GET",
            dataType: 'json',
            beforeSend: function () {
                $('#loading').show()
                $('.capcha-spin').addClass('active')
            },
            success: function (data) {
                $('#reload-captcha').html(data.view);
            },
            complete: function () {
                $('#loading').hide()
                $('.capcha-spin').removeClass('active')
            }
        });
    });

    // Show loader on final form submit
    $('#form-id').on('submit', function() {
        var btn = $('#generalSubmitBtn');
        if (btn.length) {
            btn.prop('disabled', true);
            btn.find('.btn-text').addClass('d-none');
            btn.find('.btn-loader').removeClass('d-none');
        }
    });

</script>

@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha['site_key']}}"></script>
@endif
@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script>
        $(document).ready(function () {
            $('#signInBtn').click(function (e) {
                if ($('#set_default_captcha_value').val() == 1) {
                    $('#form-id').submit();
                    return true;
                }
                e.preventDefault();
                if (typeof grecaptcha === 'undefined') {
                    toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');

                    return;
                }
                grecaptcha.ready(function () {
                    grecaptcha.execute('{{$recaptcha['site_key']}}', { action: 'submit' }).then(function (token) {
                        $('#g-recaptcha-response').val(token);
                        $('#form-id').submit();
                    });
                });
                window.onerror = function (message) {
                    var errorMessage = 'An unexpected error occurred. Please check the recaptcha configuration';
                    if (message.includes('Invalid site key')) {
                        errorMessage = 'Invalid site key provided. Please check the recaptcha configuration.';
                    } else if (message.includes('not loaded in api.js')) {
                        errorMessage = 'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                    }
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');
                    toastr.error(errorMessage)
                    return true;
                };
            });
        });
    </script>
@endif
{{-- recaptcha scripts end --}}
@endpush
