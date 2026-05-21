@extends('layouts.admin.app')

@section('title', translate('messages.admin_landing_page'))

@section('content')
    <div class="content container-fluid">
        <div class="page-header pb-0">
            <div class="d-flex flex-wrap justify-content-between">
                <h1 class="page-header-title">
                    <span class="page-header-icon">
                        <img src="{{ asset('public/assets/admin/img/landing.png') }}" class="w--30" alt="">
                    </span>
                    <span>
                        {{ translate('messages.admin_landing_pages') }}
                    </span>
                </h1>
                <div class="text--primary-2 py-1 d-flex flex-wrap align-items-center" type="button" data-toggle="modal"
                    data-target="#how-it-works">
                    <strong class="mr-2">{{ translate('See_how_it_works!') }}</strong>
                    <div>
                        <i class="tio-info-outined"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-30 mt-2">
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                @include('admin-views.business-settings.landing-page-settings.top-menu-links.admin-landing-page-links')
            </div>
        </div>
        @php($earning_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'earning_title')->first())
        @php($earning_sub_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'earning_sub_title')->first())
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first()?->value ?? [])

        @if ($language)
            <ul class="nav nav-tabs mb-4 border-0">
                <li class="nav-item">
                    <a class="nav-link lang_link active" href="#"
                        id="default-link">{{ translate('messages.default') }}</a>
                </li>
                @foreach (json_decode($language) as $lang)
                    <li class="nav-item">
                        <a class="nav-link lang_link" href="#"
                            id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                    </li>
                @endforeach
            </ul>
        @endif
        <div class="tab-content">
            <div class="tab-pane fade show active">
                <form action="{{ route('admin.business-settings.admin-landing-page-settings-update', 'earning-title') }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf
                    <h5 class="card-title mb-3">
                        <span class="card-header-icon mr-2"><i class="tio-settings-outlined"></i></span>
                        <span>{{ translate('Download User App Section Content ') }}</span>
                    </h5>
                    <div class="card mb-3">
                        <div class="card-body">
                            @if ($language)
                                <div class="row g-3 lang_form" id="default-form">
                                    <div class="col-sm-6">
                                        <label for="earning_title" class="form-label">{{ translate('Title') }}
                                            ({{ translate('messages.default') }})<span class="form-label-secondary"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('Write_the_title_within_40_characters') }}">
                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                    alt="">
                                            </span>
                                            <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.Required.') }}"> *
                                            </span></label>
                                        <input required id="earning_title" type="text" maxlength="40"
                                            name="earning_title[]" class="form-control"
                                            value="{{ $earning_title?->getRawOriginal('value') }}"
                                            placeholder="{{ translate('messages.title_here...') }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="sub-text" class="form-label">{{ translate('Sub Title') }}
                                            ({{ translate('messages.default') }})<span class="form-label-secondary"
                                                data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                    alt="">
                                            </span>
                                            <span class="form-label-secondary text-danger" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('messages.Required.') }}"> *
                                            </span></label>
                                        <input required id="sub-text" type="text" maxlength="80"
                                            name="earning_sub_title[]" class="form-control"
                                            value="{{ $earning_sub_title?->getRawOriginal('value') }}"
                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                    </div>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                                @foreach (json_decode($language) as $lang)
                                    <?php
                                    if (isset($earning_title->translations) && count($earning_title->translations)) {
                                        $earning_title_translate = [];
                                        foreach ($earning_title->translations as $t) {
                                            if ($t->locale == $lang && $t->key == 'earning_title') {
                                                $earning_title_translate[$lang]['value'] = $t->value;
                                            }
                                        }
                                    }
                                    if (isset($earning_sub_title->translations) && count($earning_sub_title->translations)) {
                                        $earning_sub_title_translate = [];
                                        foreach ($earning_sub_title->translations as $t) {
                                            if ($t->locale == $lang && $t->key == 'earning_sub_title') {
                                                $earning_sub_title_translate[$lang]['value'] = $t->value;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="row g-3 d-none lang_form" id="{{ $lang }}-form">
                                        <div class="col-sm-6">
                                            <label for="earning_title" class="form-label">{{ translate('Title') }}
                                                ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('Write_the_title_within_40_characters') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="">
                                                </span></label>
                                            <input id="earning_title" type="text" maxlength="40" name="earning_title[]"
                                                class="form-control"
                                                value="{{ $earning_title_translate[$lang]['value'] ?? '' }}"
                                                placeholder="{{ translate('messages.title_here...') }}">
                                        </div>
                                        <div class="col-sm-6">
                                            <label for="sub-title" class="form-label">{{ translate('Sub Title') }}
                                                ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                        alt="">
                                                </span></label>
                                            <input id="sub-title" type="text" maxlength="80"
                                                name="earning_sub_title[]" class="form-control"
                                                value="{{ $earning_sub_title_translate[$lang]['value'] ?? '' }}"
                                                placeholder="{{ translate('messages.sub_title_here...') }}">
                                        </div>
                                    </div>
                                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                                @endforeach
                            @else
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label for="earning-title" class="form-label">{{ translate('Title') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('Write_the_title_within_40_characters') }}">
                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                    alt="">
                                            </span></label>
                                        <input id="earning-title" type="text" maxlength="40" name="earning_title[]"
                                            class="form-control" placeholder="{{ translate('messages.title_here...') }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label for="earning-sub-title"
                                            class="form-label">{{ translate('Sub Title') }}<span
                                                class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                    alt="">
                                            </span></label>
                                        <input id="earning-sub-title" type="text" maxlength="80"
                                            name="earning_sub_title[]" class="form-control"
                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                    </div>
                                </div>
                                <input type="hidden" name="lang[]" value="default">
                            @endif
                            <div class="btn--container justify-content-end mt-30">
                                <button type="reset" class="btn btn--reset mb-2">{{ translate('Reset') }}</button>
                                <button type="submit" class="btn btn--primary mb-2">{{ translate('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form
                    action="{{ route('admin.business-settings.admin-landing-page-settings-update', 'earning-seller-link') }}"
                    method="POST" enctype="multipart/form-data">
                    @php($seller_app_links = \App\Models\DataSetting::where(['key' => 'seller_app_earning_links', 'type' => 'admin_landing_page'])->first())
                    @php($seller_app_links = isset($seller_app_links->value) ? json_decode($seller_app_links->value, true) : null)
                    @php($seller_app_earning_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'seller_app_earning_title')->first())
                    @php($seller_app_earning_sub_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'seller_app_earning_sub_title')->first())
                    @php($seller_app_earning_image = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'seller_app_earning_image')->first())
                    @csrf
                    <h5 class="card-title mb-3">
                        <span class="card-header-icon mr-2"><i class="tio-settings-outlined"></i></span>
                        <span>{{ translate('Download_Store_App_Section') }}</span>
                    </h5>
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-6">
                                    @if ($language)
                                        <div class="col-md-12 lang_form default-form">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="seller_app_earning_title"
                                                        class="form-label">{{ translate('Title') }}
                                                        ({{ translate('messages.default') }})<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="seller_app_earning_title" type="text" maxlength="30"
                                                        name="seller_app_earning_title[]"
                                                        value="{{ $seller_app_earning_title?->getRawOriginal('value') }}"
                                                        class="form-control"
                                                        placeholder="{{ translate('messages.title_here...') }}">
                                                </div>
                                                <div class="col-12">
                                                    <label for="seller_app_earning_sub_title"
                                                        class="form-label">{{ translate('Sub Title') }}
                                                        ({{ translate('messages.default') }})<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="seller_app_earning_sub_title" type="text" maxlength="80"
                                                        name="seller_app_earning_sub_title[]"
                                                        value="{{ $seller_app_earning_sub_title?->getRawOriginal('value') }}"
                                                        class="form-control"
                                                        placeholder="{{ translate('messages.sub_title_here...') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                        @foreach (json_decode($language) as $lang)
                                            <?php
                                            if (isset($seller_app_earning_title->translations) && count($seller_app_earning_title->translations)) {
                                                $seller_app_earning_title_translate = [];
                                                foreach ($seller_app_earning_title->translations as $t) {
                                                    if ($t->locale == $lang && $t->key == 'seller_app_earning_title') {
                                                        $seller_app_earning_title_translate[$lang]['value'] = $t->value;
                                                    }
                                                }
                                            }
                                            if (isset($seller_app_earning_sub_title->translations) && count($seller_app_earning_sub_title->translations)) {
                                                $seller_app_earning_sub_title_translate = [];
                                                foreach ($seller_app_earning_sub_title->translations as $t) {
                                                    if ($t->locale == $lang && $t->key == 'seller_app_earning_sub_title') {
                                                        $seller_app_earning_sub_title_translate[$lang]['value'] = $t->value;
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="col-md-12 d-none lang_form" id="{{ $lang }}-form1">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="seller_app_earning_title{{ $lang }}"
                                                            class="form-label">{{ translate('Title') }}
                                                            ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                                data-toggle="tooltip" data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="seller_app_earning_title{{ $lang }}" type="text"
                                                            maxlength="30" name="seller_app_earning_title[]"
                                                            value="{{ $seller_app_earning_title_translate[$lang]['value'] ?? '' }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.title_here...') }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="seller_app_earning_sub_title{{ $lang }}"
                                                            class="form-label">{{ translate('Sub Title') }}
                                                            ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                                data-toggle="tooltip" data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="seller_app_earning_sub_title{{ $lang }}"
                                                            type="text" maxlength="80" name="seller_app_earning_sub_title[]"
                                                            value="{{ $seller_app_earning_sub_title_translate[$lang]['value'] ?? '' }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        @endforeach
                                    @else
                                        <div class="col-md-12">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="seller_app_earning_title"
                                                        class="form-label">{{ translate('Title') }}<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="seller_app_earning_title" type="text" maxlength="30"
                                                        name="seller_app_earning_title[]" class="form-control"
                                                        placeholder="{{ translate('messages.title_here...') }}">
                                                </div>
                                                <div class="col-12">
                                                    <label for="seller_app_earning_sub_title"
                                                        class="form-label">{{ translate('Sub Title') }}<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="seller_app_earning_sub_title" type="text" maxlength="80"
                                                        name="seller_app_earning_sub_title[]" class="form-control"
                                                        placeholder="{{ translate('messages.sub_title_here...') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label d-block mb-3">
                                            {{ translate('messages.Image') }} <span
                                                class="text--primary">{{ translate('(size: 1:1)') }}</span>
                                            <div class="fs-12 opacity-70">
                                                {{ translate(IMAGE_FORMAT . ' ' . 'Less Than 2MB') }}
                                            </div>
                                        </label>
                                        <label class="upload-img-3 m-0">
                                            <div class="position-relative">
                                                <div class="img">
                                                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('seller_app_earning_image', $seller_app_earning_image?->value ?? '', $seller_app_earning_image?->storage[0]?->value ?? 'public', 'aspect_1') }}"
                                                        data-onerror-image="{{ asset('/public/assets/admin/img/aspect-1.png') }}"
                                                        alt=""
                                                        class="img__aspect-1 min-w-187px max-w-187px onerror-image">
                                                </div>
                                                <input accept="{{ IMAGE_EXTENSION }}"
                                                    class="upload-file__input single_file_input" type="file"
                                                    name="image" hidden>
                                                @if (isset($seller_app_earning_image['value']))
                                                    <span id="seller_app_earning_image"
                                                        class="remove_image_button remove-image dynamic-checkbox"
                                                        data-id="seller_app_earning_image"
                                                        data-image-off="{{ asset('/public/assets/admin/img/delete-confirmation.png') }}"
                                                        data-title="{{ translate('Warning!') }}"
                                                        data-text="<p>{{ translate('Are_you_sure_you_want_to_remove_this_image_?') }}</p>">
                                                        <i class="tio-clear"></i></span>
                                                @endif
                                            </div>
                                        </label>
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">
                                        <img src="{{ asset('public/assets/admin/img/playstore.png') }}" class="mr-2"
                                            alt="">
                                        {{ translate('Playstore Button') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group mb-md-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="playstore_url" class="form-label text-capitalize m-0">
                                                    {{ translate('Download Link') }}
                                                    <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('When_disabled,_the_Play_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                        <i class="tio-info-outined"></i>
                                                    </span>
                                                </label>
                                                <label class="toggle-switch toggle-switch-sm m-0">
                                                    <input type="checkbox" name="playstore_url_status"
                                                        data-id="play-store-seller-status" data-type="toggle"
                                                        data-image-on='{{ asset('/public/assets/admin/img/modal') }}/play-store-on.png'
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal') }}/play-store-off.png"
                                                        data-title-on="{{ translate('Want_to_enable_the_Play_Store_button_for_Store_App?') }}"
                                                        data-title-off="{{ translate('Want_to_disable_the_Play_Store_button_for_Store_App?') }}"
                                                        data-text-on="<p>{{ translate('If_enabled,_the_Store_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                        id="play-store-seller-status"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1"
                                                        {{ isset($seller_app_links) && $seller_app_links['playstore_url_status'] ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text mb-0">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>


                                            @include(
                                                'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                [
                                                    'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                        'app_url_android_store'),
                                                ]
                                            )

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">
                                        <img src="{{ asset('public/assets/admin/img/ios.png') }}" class="mr-2"
                                            alt="">
                                        {{ translate('App Store Button') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group mb-md-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="apple_store_url" class="form-label text-capitalize m-0">
                                                    {{ translate('Download Link') }}
                                                    <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('When_disabled,_the_App_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                        <i class="tio-info-outined"></i>
                                                    </span>
                                                </label>
                                                <label class="toggle-switch toggle-switch-sm m-0">
                                                    <input type="checkbox" name="apple_store_url_status"
                                                        data-id="apple-seller-status" data-type="toggle"
                                                        data-image-on='{{ asset('/public/assets/admin/img/modal') }}/apple-on.png'
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal') }}/apple-off.png"
                                                        data-title-on="{{ translate('Want_to_enable_the_App_Store_button_for_Store_App?') }}"
                                                        data-title-off="{{ translate('Want_to_disable_the_App_Store_button_for_Store_App') }}"
                                                        data-text-on="<p>{{ translate('If_enabled,_the_Store_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                        id="apple-seller-status"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1"
                                                        {{ isset($seller_app_links) && $seller_app_links['apple_store_url_status'] ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text mb-0">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                            @include(
                                                'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                [
                                                    'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                        'app_url_ios_store'),
                                                ]
                                            )

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end mt-30">
                                <button type="reset" class="btn btn--reset mb-2">{{ translate('Reset') }}</button>
                                <button type="submit" class="btn btn--primary mb-2">{{ translate('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
                <form id="seller_app_earning_image_form" action="{{ route('admin.remove_image') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" value="{{ $seller_app_earning_image?->id }}">
                    <input type="hidden" name="model_name" value="DataSetting">
                    <input type="hidden" name="image_path" value="seller_app_earning_image">
                    <input type="hidden" name="field_name" value="value">
                </form>
                <form
                    action="{{ route('admin.business-settings.admin-landing-page-settings-update', 'earning-dm-link') }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf
                    @php($dm_app_links = \App\Models\DataSetting::where(['key' => 'dm_app_earning_links', 'type' => 'admin_landing_page'])->first())
                    @php($dm_app_links = isset($dm_app_links->value) ? json_decode($dm_app_links->value, true) : null)
                    @php($dm_app_earning_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'dm_app_earning_title')->first())
                    @php($dm_app_earning_sub_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'dm_app_earning_sub_title')->first())
                    @php($dm_app_earning_image = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'dm_app_earning_image')->first())
                    <h5 class="card-title mt-3 mb-3">
                        <span class="card-header-icon mr-2"><i class="tio-settings-outlined"></i></span>
                        <span>{{ translate('Download_Deliveryman_App_Section') }}</span>
                    </h5>
                    <div class="card">
                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-6">
                                    @if ($language)
                                        <div class="col-md-12 lang_form default-form">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="dm_app_earning_title"
                                                        class="form-label">{{ translate('Title') }}
                                                        ({{ translate('messages.default') }})<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="dm_app_earning_title" type="text" maxlength="30"
                                                        name="dm_app_earning_title[]"
                                                        value="{{ $dm_app_earning_title?->getRawOriginal('value') }}"
                                                        class="form-control"
                                                        placeholder="{{ translate('messages.title_here...') }}">
                                                </div>
                                                <div class="col-12">
                                                    <label for="dm_app_earning_sub_title"
                                                        class="form-label">{{ translate('Sub Title') }}
                                                        ({{ translate('messages.default') }})<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="dm_app_earning_sub_title" type="text" maxlength="80"
                                                        name="dm_app_earning_sub_title[]"
                                                        value="{{ $dm_app_earning_sub_title?->getRawOriginal('value') }}"
                                                        class="form-control"
                                                        placeholder="{{ translate('messages.sub_title_here...') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                        @foreach (json_decode($language) as $lang)
                                            <?php
                                            if (isset($dm_app_earning_title->translations) && count($dm_app_earning_title->translations)) {
                                                $dm_app_earning_title_translate = [];
                                                foreach ($dm_app_earning_title->translations as $t) {
                                                    if ($t->locale == $lang && $t->key == 'dm_app_earning_title') {
                                                        $dm_app_earning_title_translate[$lang]['value'] = $t->value;
                                                    }
                                                }
                                            }
                                            if (isset($dm_app_earning_sub_title->translations) && count($dm_app_earning_sub_title->translations)) {
                                                $dm_app_earning_sub_title_translate = [];
                                                foreach ($dm_app_earning_sub_title->translations as $t) {
                                                    if ($t->locale == $lang && $t->key == 'dm_app_earning_sub_title') {
                                                        $dm_app_earning_sub_title_translate[$lang]['value'] = $t->value;
                                                    }
                                                }
                                            }
                                            ?>
                                            <div class="col-md-12 d-none lang_form" id="{{ $lang }}-form2">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="dm_app_earning_title{{ $lang }}"
                                                            class="form-label">{{ translate('Title') }}
                                                            ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                                data-toggle="tooltip" data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="dm_app_earning_title{{ $lang }}" type="text"
                                                            maxlength="30" name="dm_app_earning_title[]"
                                                            value="{{ $dm_app_earning_title_translate[$lang]['value'] ?? '' }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.title_here...') }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="dm_app_earning_sub_title{{ $lang }}"
                                                            class="form-label">{{ translate('Sub Title') }}
                                                            ({{ strtoupper($lang) }})<span class="form-label-secondary"
                                                                data-toggle="tooltip" data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="dm_app_earning_sub_title{{ $lang }}" type="text"
                                                            maxlength="80" name="dm_app_earning_sub_title[]"
                                                            value="{{ $dm_app_earning_sub_title_translate[$lang]['value'] ?? '' }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        @endforeach
                                    @else
                                        <div class="col-md-12">
                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label for="dm_app_earning_title"
                                                        class="form-label">{{ translate('Title') }}<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="dm_app_earning_title" type="text" maxlength="30"
                                                        name="dm_app_earning_title[]" class="form-control"
                                                        placeholder="{{ translate('messages.title_here...') }}">
                                                </div>
                                                <div class="col-12">
                                                    <label for="dm_app_earning_sub_title"
                                                        class="form-label">{{ translate('Sub Title') }}<span
                                                            class="form-label-secondary" data-toggle="tooltip"
                                                            data-placement="right"
                                                            data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                            <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                alt="">
                                                        </span></label>
                                                    <input id="dm_app_earning_sub_title" type="text" maxlength="80"
                                                        name="dm_app_earning_sub_title[]" class="form-control"
                                                        placeholder="{{ translate('messages.sub_title_here...') }}">
                                                </div>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                    @endif
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label d-block mb-3">
                                            {{ translate('messages.Image') }} <span
                                                class="text--primary">{{ translate('(size: 1:1)') }}</span>
                                            <div class="fs-12 opacity-70">
                                                {{ translate(IMAGE_FORMAT . ' ' . 'Less Than 2MB') }}
                                            </div>
                                        </label>
                                        <label class="upload-img-3 m-0">
                                            <div class="position-relative">
                                                <div class="img">
                                                    <img src="{{ \App\CentralLogics\Helpers::get_full_url('dm_app_earning_image', $dm_app_earning_image?->value ?? '', $dm_app_earning_image?->storage[0]?->value ?? 'public', 'aspect_1') }}"
                                                        data-onerror-image="{{ asset('/public/assets/admin/img/aspect-1.png') }}"
                                                        alt=""
                                                        class="img__aspect-1 min-w-187px max-w-187px onerror-image">
                                                </div>
                                                <input accept="{{ IMAGE_EXTENSION }}"
                                                    class="upload-file__input single_file_input" type="file"
                                                    name="image" hidden>
                                                @if (isset($dm_app_earning_image['value']))
                                                    <span id="dm_app_earning_image"
                                                        class="remove_image_button remove-image dynamic-checkbox"
                                                        data-id="dm_app_earning_image"
                                                        data-image-off="{{ asset('/public/assets/admin/img/delete-confirmation.png') }}"
                                                        data-title="{{ translate('Warning!') }}"
                                                        data-text="<p>{{ translate('Are_you_sure_you_want_to_remove_this_image_?') }}</p>">
                                                        <i class="tio-clear"></i></span>
                                                @endif
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">
                                        <img src="{{ asset('public/assets/admin/img/playstore.png') }}" class="mr-2"
                                            alt="">
                                        {{ translate('Playstore Button') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group mb-md-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="playstore_url_dm" class="form-label text-capitalize m-0">
                                                    {{ translate('Download Link') }}
                                                    <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('When_disabled,_the_Play_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                        <i class="tio-info-outined"></i>
                                                    </span>
                                                </label>
                                                <label class="toggle-switch toggle-switch-sm m-0">
                                                    <input type="checkbox" name="playstore_url_status"
                                                        data-id="play-store-dm-status" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/play-store-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/play-store-off.png') }}"
                                                        data-title-on="{{ translate('Want_to_enable_the_Play_Store_button_for_Deliveryman_App?') }}"
                                                        data-title-off="{{ translate('Want_to_disable_the_Play_Store_button_for_Deliveryman_App?') }}"
                                                        data-text-on="<p>{{ translate('If_enabled,_the_Deliveryman_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                        id="play-store-dm-status"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1"
                                                        {{ isset($dm_app_links) && $dm_app_links['playstore_url_status'] ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text mb-0">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>

                                            @include(
                                                'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                [
                                                    'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                        'app_url_android_deliveryman'),
                                                ]
                                            )

                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5 class="card-title mb-2">
                                        <img src="{{ asset('public/assets/admin/img/ios.png') }}" class="mr-2"
                                            alt="">
                                        {{ translate('App Store Button') }}
                                    </h5>
                                    <div class="__bg-F8F9FC-card">
                                        <div class="form-group mb-md-0">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label for="apple_store_url_dm" class="form-label text-capitalize m-0">
                                                    {{ translate('Download Link') }}
                                                    <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                        data-placement="right"
                                                        data-original-title="{{ translate('When_disabled,_the_App_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                        <i class="tio-info-outined"></i>
                                                    </span>
                                                </label>
                                                <label class="toggle-switch toggle-switch-sm m-0">
                                                    <input type="checkbox" name="apple_store_url_status"
                                                        data-id="apple-dm-status" data-type="toggle"
                                                        data-image-on="{{ asset('/public/assets/admin/img/modal/apple-on.png') }}"
                                                        data-image-off="{{ asset('/public/assets/admin/img/modal/apple-off.png') }}"
                                                        data-title-on="{{ translate('Want_to_enable_the_App_Store_button_for_Deliveryman_App?') }}"
                                                        data-title-off="{{ translate('Want_to_disable_the_App_Store_button_for_Deliveryman_App?') }}"
                                                        data-text-on="<p>{{ translate('If_enabled,_the_Deliveryman_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                        data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                        id="apple-dm-status"
                                                        class="status toggle-switch-input dynamic-checkbox-toggle"
                                                        value="1"
                                                        {{ isset($dm_app_links) && $dm_app_links['apple_store_url_status'] ? 'checked' : '' }}>
                                                    <span class="toggle-switch-label text mb-0">
                                                        <span class="toggle-switch-indicator"></span>
                                                    </span>
                                                </label>
                                            </div>
                                            @include(
                                                'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                [
                                                    'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                        'app_url_ios_deliveryman'),
                                                ]
                                            )
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="btn--container justify-content-end mt-30">
                                <button type="reset" class="btn btn--reset mb-2">{{ translate('Reset') }}</button>
                                <button type="submit" class="btn btn--primary mb-2">{{ translate('Save') }}</button>
                            </div>
                        </div>
                    </div>
                </form>

                <form id="dm_app_earning_image_form" action="{{ route('admin.remove_image') }}" method="post">
                    @csrf
                    <input type="hidden" name="id" value="{{ $dm_app_earning_image?->id }}">
                    <input type="hidden" name="model_name" value="DataSetting">
                    <input type="hidden" name="image_path" value="dm_app_earning_image">
                    <input type="hidden" name="field_name" value="value">
                </form>

                @if (addon_published_status('RideShare'))
                    <form
                        action="{{ route('admin.business-settings.admin-landing-page-settings-update', 'earning-rider-link') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @php($rider_app_links = \App\Models\DataSetting::where(['key' => 'rider_app_earning_links', 'type' => 'admin_landing_page'])->first())
                        @php($rider_app_links = isset($rider_app_links->value) ? json_decode($rider_app_links->value, true) : null)
                        @php($rider_app_earning_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'rider_app_earning_title')->first())
                        @php($rider_app_earning_sub_title = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'rider_app_earning_sub_title')->first())
                        @php($rider_app_earning_image = \App\Models\DataSetting::withoutGlobalScope('translate')->where('type', 'admin_landing_page')->where('key', 'rider_app_earning_image')->first())
                        <h5 class="card-title mt-3 mb-3">
                            <span class="card-header-icon mr-2"><i class="tio-settings-outlined"></i></span>
                            <span>{{ translate('Download_Rider_App_Section') }}</span>
                        </h5>
                        <div class="card">
                            <div class="card-body">

                                <div class="row g-3">
                                    <div class="col-6">
                                        @if ($language)
                                            <div class="col-md-12 lang_form default-form">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="rider_app_earning_title"
                                                            class="form-label">{{ translate('Title') }}
                                                            ({{ translate('messages.default') }})<span
                                                                class="form-label-secondary" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="rider_app_earning_title" type="text" maxlength="30"
                                                            name="rider_app_earning_title[]"
                                                            value="{{ $rider_app_earning_title?->getRawOriginal('value') }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.title_here...') }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="rider_app_earning_sub_title"
                                                            class="form-label">{{ translate('Sub Title') }}
                                                            ({{ translate('messages.default') }})<span
                                                                class="form-label-secondary" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="rider_app_earning_sub_title" type="text" maxlength="80"
                                                            name="rider_app_earning_sub_title[]"
                                                            value="{{ $rider_app_earning_sub_title?->getRawOriginal('value') }}"
                                                            class="form-control"
                                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                            @foreach (json_decode($language) as $lang)
                                                <?php
                                                if (isset($rider_app_earning_title->translations) && count($rider_app_earning_title->translations)) {
                                                    $rider_app_earning_title_translate = [];
                                                    foreach ($rider_app_earning_title->translations as $t) {
                                                        if ($t->locale == $lang && $t->key == 'rider_app_earning_title') {
                                                            $rider_app_earning_title_translate[$lang]['value'] = $t->value;
                                                        }
                                                    }
                                                }
                                                if (isset($rider_app_earning_sub_title->translations) && count($rider_app_earning_sub_title->translations)) {
                                                    $rider_app_earning_sub_title_translate = [];
                                                    foreach ($rider_app_earning_sub_title->translations as $t) {
                                                        if ($t->locale == $lang && $t->key == 'rider_app_earning_sub_title') {
                                                            $rider_app_earning_sub_title_translate[$lang]['value'] = $t->value;
                                                        }
                                                    }
                                                }
                                                ?>
                                                <div class="col-md-12 d-none lang_form" id="{{ $lang }}-form3">
                                                    <div class="row g-3">
                                                        <div class="col-12">
                                                            <label for="rider_app_earning_title{{ $lang }}"
                                                                class="form-label">{{ translate('Title') }}
                                                                ({{ strtoupper($lang) }})<span
                                                                    class="form-label-secondary" data-toggle="tooltip"
                                                                    data-placement="right"
                                                                    data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                        alt="">
                                                                </span></label>
                                                            <input id="rider_app_earning_title{{ $lang }}" type="text"
                                                                maxlength="30" name="rider_app_earning_title[]"
                                                                value="{{ $rider_app_earning_title_translate[$lang]['value'] ?? '' }}"
                                                                class="form-control"
                                                                placeholder="{{ translate('messages.title_here...') }}">
                                                        </div>
                                                        <div class="col-12">
                                                            <label for="rider_app_earning_sub_title{{ $lang }}"
                                                                class="form-label">{{ translate('Sub Title') }}
                                                                ({{ strtoupper($lang) }})<span
                                                                    class="form-label-secondary" data-toggle="tooltip"
                                                                    data-placement="right"
                                                                    data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                                    <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                        alt="">
                                                                </span></label>
                                                            <input id="rider_app_earning_sub_title{{ $lang }}"
                                                                type="text" maxlength="80" name="rider_app_earning_sub_title[]"
                                                                value="{{ $rider_app_earning_sub_title_translate[$lang]['value'] ?? '' }}"
                                                                class="form-control"
                                                                placeholder="{{ translate('messages.sub_title_here...') }}">
                                                        </div>
                                                    </div>
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                            @endforeach
                                        @else
                                            <div class="col-md-12">
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label for="rider_app_earning_title"
                                                            class="form-label">{{ translate('Title') }}<span
                                                                class="form-label-secondary" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_30_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="rider_app_earning_title" type="text" maxlength="30"
                                                            name="rider_app_earning_title[]" class="form-control"
                                                            placeholder="{{ translate('messages.title_here...') }}">
                                                    </div>
                                                    <div class="col-12">
                                                        <label for="rider_app_earning_sub_title"
                                                            class="form-label">{{ translate('Sub Title') }}<span
                                                                class="form-label-secondary" data-toggle="tooltip"
                                                                data-placement="right"
                                                                data-original-title="{{ translate('Write_the_title_within_80_characters') }}">
                                                                <img src="{{ asset('public/assets/admin/img/info-circle.svg') }}"
                                                                    alt="">
                                                            </span></label>
                                                        <input id="rider_app_earning_sub_title" type="text" maxlength="80"
                                                            name="rider_app_earning_sub_title[]" class="form-control"
                                                            placeholder="{{ translate('messages.sub_title_here...') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                        @endif
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <label class="form-label d-block mb-3">
                                                {{ translate('messages.Image') }} <span
                                                    class="text--primary">{{ translate('(size: 1:1)') }}</span>
                                                <div class="fs-12 opacity-70">
                                                    {{ translate(IMAGE_FORMAT . ' ' . 'Less Than 2MB') }}
                                                </div>
                                            </label>
                                            <label class="upload-img-3 m-0">
                                                <div class="position-relative">
                                                    <div class="img">
                                                        <img src="{{ \App\CentralLogics\Helpers::get_full_url('rider_app_earning_image', $rider_app_earning_image?->value ?? '', $rider_app_earning_image?->storage[0]?->value ?? 'public', 'aspect_1') }}"
                                                            data-onerror-image="{{ asset('/public/assets/admin/img/aspect-1.png') }}"
                                                            alt=""
                                                            class="img__aspect-1 min-w-187px max-w-187px onerror-image">
                                                    </div>
                                                    <input accept="{{ IMAGE_EXTENSION }}"
                                                        class="upload-file__input single_file_input" type="file"
                                                        name="image" hidden>
                                                    @if (isset($rider_app_earning_image['value']))
                                                        <span id="rider_app_earning_image"
                                                            class="remove_image_button remove-image dynamic-checkbox"
                                                            data-id="rider_app_earning_image"
                                                            data-image-off="{{ asset('/public/assets/admin/img/delete-confirmation.png') }}"
                                                            data-title="{{ translate('Warning!') }}"
                                                            data-text="<p>{{ translate('Are_you_sure_you_want_to_remove_this_image_?') }}</p>">
                                                            <i class="tio-clear"></i></span>
                                                    @endif
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-2">
                                            <img src="{{ asset('public/assets/admin/img/playstore.png') }}"
                                                class="mr-2" alt="">
                                            {{ translate('Playstore Button') }}
                                        </h5>
                                        <div class="__bg-F8F9FC-card">
                                            <div class="form-group mb-md-0">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="playstore_url_dm" class="form-label text-capitalize m-0">
                                                        {{ translate('Download Link') }}
                                                        <span class="input-label-secondary text--title"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('When_disabled,_the_Play_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                    <label class="toggle-switch toggle-switch-sm m-0">
                                                        <input type="checkbox" name="playstore_url_status"
                                                            data-id="play-store-rider-status" data-type="toggle"
                                                            data-image-on="{{ asset('/public/assets/admin/img/modal/play-store-on.png') }}"
                                                            data-image-off="{{ asset('/public/assets/admin/img/modal/play-store-off.png') }}"
                                                            data-title-on="{{ translate('Want_to_enable_the_Play_Store_button_for_Rider_App?') }}"
                                                            data-title-off="{{ translate('Want_to_disable_the_Play_Store_button_for_Rider_App?') }}"
                                                            data-text-on="<p>{{ translate('If_enabled,_the_Rider_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                            data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                            id="play-store-rider-status"
                                                            class="status toggle-switch-input dynamic-checkbox-toggle"
                                                            value="1"
                                                            {{ data_get($rider_app_links, 'playstore_url_status') ? 'checked' : '' }}>
                                                        <span class="toggle-switch-label text mb-0">
                                                            <span class="toggle-switch-indicator"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                                @include(
                                                    'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                    [
                                                        'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                            'app_url_android_rider'),
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-2">
                                            <img src="{{ asset('public/assets/admin/img/ios.png') }}" class="mr-2"
                                                alt="">
                                            {{ translate('App Store Button') }}
                                        </h5>
                                        <div class="__bg-F8F9FC-card">
                                            <div class="form-group mb-md-0">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label for="apple_store_url_dm"
                                                        class="form-label text-capitalize m-0">
                                                        {{ translate('Download Link') }}
                                                        <span class="input-label-secondary text--title"
                                                            data-toggle="tooltip" data-placement="right"
                                                            data-original-title="{{ translate('When_disabled,_the_App_Store_download_button_will_be_hidden_from_the_landing_page') }}">
                                                            <i class="tio-info-outined"></i>
                                                        </span>
                                                    </label>
                                                    <label class="toggle-switch toggle-switch-sm m-0">
                                                        <input type="checkbox" name="apple_store_url_status"
                                                            data-id="apple-rider-status" data-type="toggle"
                                                            data-image-on="{{ asset('/public/assets/admin/img/modal/apple-on.png') }}"
                                                            data-image-off="{{ asset('/public/assets/admin/img/modal/apple-off.png') }}"
                                                            data-title-on="{{ translate('Want_to_enable_the_App_Store_button_for_Rider_App?') }}"
                                                            data-title-off="{{ translate('Want_to_disable_the_App_Store_button_for_Rider_App?') }}"
                                                            data-text-on="<p>{{ translate('If_enabled,_the_Rider_app_download_button_will_be_visible_on_the_Landing_page.') }}</p>"
                                                            data-text-off="<p>{{ translate('If_disabled,_this_button_will_be_hidden_from_the_landing_page.') }}</p>"
                                                            id="apple-rider-status"
                                                            class="status toggle-switch-input dynamic-checkbox-toggle"
                                                            value="1"
                                                            {{ data_get($rider_app_links, 'apple_store_url_status') ? 'checked' : '' }}>
                                                        <span class="toggle-switch-label text mb-0">
                                                            <span class="toggle-switch-indicator"></span>
                                                        </span>
                                                    </label>
                                                </div>
                                                @include(
                                                    'admin-views.business-settings.landing-page-settings.partials._app-download-link-status',
                                                    [
                                                        'isConfigured' => \App\CentralLogics\Helpers::get_business_settings(
                                                            'app_url_ios_rider'),
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="btn--container justify-content-end mt-30">
                                    <button type="reset" class="btn btn--reset mb-2">{{ translate('Reset') }}</button>
                                    <button type="submit"
                                        class="btn btn--primary mb-2">{{ translate('Save') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    <form id="rider_app_earning_image_form" action="{{ route('admin.remove_image') }}" method="post">
                        @csrf
                        <input type="hidden" name="id" value="{{ $rider_app_earning_image?->id }}">
                        <input type="hidden" name="model_name" value="DataSetting">
                        <input type="hidden" name="image_path" value="rider_app_earning_image">
                        <input type="hidden" name="field_name" value="value">
                    </form>

                @endif

            </div>
        </div>
    </div>
    <!-- How it Works -->
    @include('admin-views.business-settings.landing-page-settings.partial.how-it-work')
@endsection
