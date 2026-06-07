@extends('layouts.vendor.app')

@section('title',translate('messages.settings'))

@push('css_or_js')
<link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">
@endpush

@section('content')

    <div class="content container-fluid config-inline-remove-class">
        <!-- Page Heading -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span>
                    {{translate('messages.Provider_Setup')}}
                </span>
            </h1>
        </div>
        <!-- Page Heading -->
        <div class="card mb-3">
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-lg-6">
                        <div>

                            <p>{{ translate('To view a list of all active zones on your') }} <a target="_blank" href="{{ route('home') }}" class="text-underline text--info">{{ translate('Admin Landing') }}</a> {{ translate('Page, Enable the') }} <span class="font-semibold">'{{ translate('Available Zones') }}'</span> {{ translate('feature') }}</p>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="">
                            <label class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control" for="restaurant-open-status">
                                <span class="pr-2">{{translate('messages.Provider_temporarily_closed_title')}}</span>
                                <label class="switch toggle-switch-lg m-0">
                                    <input id="restaurant-open-status" type="checkbox" class="toggle-switch-input restaurant-open-status"
                                           data-title="{{translate('messages.are_you_sure')}}"
                                           data-text="{{$store->active ? translate('messages.you_want_to_temporarily_close_this_').($store->module->module_type == 'rental' ? translate('provider') : translate('store')) : translate('messages.you_want_to_open_this_').($store->module->module_type == 'rental' ? translate('provider') : translate('store')) }}"
                                           data-route="{{route('vendor.business-settings.update-active-status')}}"
                                           data-no="{{translate('messages.no')}}"
                                           data-yes="{{translate('messages.yes')}}"
                                        {{$store->active ?'':'checked'}}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{ translate('Basic Settings') }}
                    </h5>
                    <p class="fs-12 mb-0">
                        {{ translate('Vendor Settings') }}
                    </p>
                </div>
            </div>
            <form action="{{route('vendor.business-settings.update-setup',[$store['id']])}}" method="post"
                    enctype="multipart/form-data">
                    @csrf
            <div class="card-body">
                <div class="row g-4 align-items-end">
                    <div class="col-lg-4 col-sm-6">
                        <div class="form-group mb-0">
                            <label class="input-label font-semibold" for="schedule_order">
                                {{ translate('Scheduled Trip') }}
                            </label>
                            <label class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control" for="schedule_order">
                                <span class="pr-2">{{translate('messages.scheduled_order')}}<span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('When_enabled,_store_owner_can_take_scheduled_orders_from_customers.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.scheduled_order_hint')}}"></span></span>
                                <input type="checkbox" value="1" class="toggle-switch-input " name="schedule_order" id="schedule_order" {{$store->schedule_order?'checked':''}}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="">
                            <label class="d-flex justify-content-between switch toggle-switch-sm text-dark" for="gst_status">
                                <label class="input-label font-semibold mb-0">{{translate('messages.GST')}} <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                data-original-title="{{translate('messages.If GST is enable, GST number will show in invoice')}}"><i class="tio-info text--title opacity-60"></i></span></label>
                                <input type="checkbox" class="toggle-switch-input" name="gst_status" id="gst_status" value="1" {{$store->gst_status?'checked':''}}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                            <input type="text" id="gst" name="gst" class="form-control" value="{{$store->gst_code}}" {{isset($store->gst_status)?'':'readonly'}}>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="position-relative">
                            <label class="input-label font-semibold"
                                for="tax">{{ translate('Approx. Pickup Time') }}</label>
                            <div class="custom-group-btn">
                                <div class="item flex-sm-grow-1">
                                    <input id="min" type="number" name="minimum_delivery_time"
                                        value="{{explode('-',$store->delivery_time)[0]}}"
                                        class="form-control h--45px border-0"
                                        placeholder="{{ translate('messages.Ex :') }} 20"
                                        pattern="^[0-9]{2}$" required>
                                </div>
                                <div class="separator"></div>
                                <div class="item flex-sm-grow-1">
                                    <input id="max" type="number" name="maximum_delivery_time"
                                        value="{{explode(' ',explode('-',$store->delivery_time)[1])[0]}}"
                                        class="form-control h--45px border-0"
                                        placeholder="{{ translate('messages.Ex :') }} 30" pattern="[0-9]{2}$"
                                        required>
                                </div>
                                <div class="separator"></div>
                                <div class="item flex-shrink-0">
                                    <select name="delivery_time_type" class="custom-select border-0"  required>
                                        <option value="min" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='min'?'selected':''}}>{{translate('messages.minutes')}}</option>
                                        <option value="hours" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='hours'?'selected':''}}>{{translate('messages.hours')}}</option>
                                        <option value="days" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='days'?'selected':''}}>{{translate('messages.days')}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group mb-0 pickup-zone-tag">
                            <label class="input-label font-semibold"
                                for="pickup_zones">{{ translate('messages.pickup_zone') }}<span
                                    class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('messages.Select zones from where customer can choose their pickup locations for trip booking') }}">
                                    <i class="tio-info text--title opacity-60"></i>
                                </span></label>
                            <select name="pickup_zones[]" id="pickup_zones"
                                class="form-control  multiple-select2" multiple="multiple">


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
                    </div>
                    <div class="col-12">
                        <div class="btn--container mt-3 justify-content-end">
                            <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                        </div>
                    </div>
                </div>
            </div>

            </form>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{translate('messages.Provider_Meta_Data')}}
                    </h5>
                    <p class="fs-12 mb-0">
                        {{ translate('Provider_Meta_Data_&_Image') }}
                    </p>
                </div>
            </div>
            @php($language=\App\Models\BusinessSetting::where('key','language')->first())
            @php($language = $language->value ?? null)
            @php($defaultLang = 'en')
            <div class="card-body">
                <form action="{{route('vendor.business-settings.update-meta-data',[$store['id']])}}" method="post"
                enctype="multipart/form-data" class="col-12">
                @csrf
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="__bg-FAFAFA radius-10 p-20px">
                                <div class="card-body">
                                    @if($language)
                                    <ul class="nav nav-tabs mb-4">
                                        <li class="nav-item">
                                            <a class="nav-link lang_link active"
                                            href="#"
                                            id="default-link">{{ translate('Default') }}</a>
                                        </li>
                                        @foreach (json_decode($language) as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link"
                                                    href="#"
                                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    @endif
                                    @if ($language)
                                    <div class="lang_form"
                                    id="default-form">
                                        <div class="mb-20px">
                                            <label class="input-label font-semibold"
                                                for="default_title">{{ translate('messages.meta_title') }}
                                                ({{ translate('messages.Default') }})
                                            </label>
                                            <input type="text" name="meta_title[]" id="default_title"
                                                class="form-control" placeholder="{{ translate('messages.meta_title') }}" value="{{$store->getRawOriginal('meta_title')}}" >
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                        <div class="">
                                            <label class="input-label font-semibold"
                                                for="meta_description">{{ translate('messages.meta_description') }} ({{ translate('messages.default') }})</label>
                                            <textarea type="text" id="meta_description" name="meta_description[]" placeholder="{{translate('messages.meta_description')}}" class="form-control min-h-90px ckeditor">{{$store->getRawOriginal('meta_description')}}</textarea>
                                        </div>
                                    </div>
                                        @foreach (json_decode($language) as $lang)
                                        <?php
                                            if(count($store['translations'])){
                                                $translate = [];
                                                foreach($store['translations'] as $t)
                                                {
                                                    if($t->locale == $lang && $t->key=="meta_title"){
                                                        $translate[$lang]['meta_title'] = $t->value;
                                                    }
                                                    if($t->locale == $lang && $t->key=="meta_description"){
                                                        $translate[$lang]['meta_description'] = $t->value;
                                                    }
                                                }
                                            }
                                        ?>
                                            <div class="d-none lang_form"
                                                id="{{ $lang }}-form">
                                                <div class=" ">
                                                    <label class="input-label"
                                                        for="{{ $lang }}_title">{{ translate('messages.meta_title') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="meta_title[]" id="{{ $lang }}_title"
                                                        class="form-control" value="{{ $translate[$lang]['meta_title']??'' }}" placeholder="{{ translate('messages.meta_title') }}">
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                                <div class="">
                                                    <label class="input-label"
                                                        for="meta_description{{$lang}}">{{ translate('messages.meta_description') }} ({{ strtoupper($lang) }})</label>
                                                    <textarea id="meta_description{{$lang}}" type="text" name="meta_description[]" placeholder="{{translate('messages.meta_description')}}" class="form-control min-h-90px ckeditor">{{ $translate[$lang]['meta_description']??'' }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div id="default-form">
                                            <div class=" ">
                                                <label class="input-label"
                                                    for="meta_title">{{ translate('messages.meta_title') }} ({{ translate('messages.default') }})</label>
                                                <input type="text" id="meta_title" name="meta_title[]" class="form-control"
                                                    placeholder="{{ translate('messages.meta_title') }}" >
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                            <div class="">
                                                <label class="input-label"
                                                    for="meta_description">{{ translate('messages.meta_description') }}
                                                </label>
                                                <textarea type="text" id="meta_description" name="meta_description[]" placeholder="{{translate('messages.meta_description')}}" class="form-control min-h-90px ckeditor"></textarea>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="">
                                <label class="__custom-upload-img">
                                    <label class="input-label font-semibold fs-16 mb-1">
                                        {{ translate('meta_image') }}
                                    </label>
                                    <div class="mb-20">
                                        <p class="fs-12">{{ translate('JPG, JPEG, PNG Less Than 1MB') }}
                                            <strong class="font-semibold">({{ translate('Ratio 1:1') }})</strong>
                                        </p>
                                    </div>
                                    <img class="img--110 min-height-170px min-width-170px onerror-image" id="viewer"
                                             data-onerror-image="{{ asset('public/assets/admin/img/upload.png') }}"
                                             src="{{ $store->meta_image_full_url }}"
                                             alt="{{ translate('meta_image') }}" />
                                    <input type="file" name="meta_image" id="customFileEg1" class="custom-file-input"
                                        accept=".webp, .jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="justify-content-end btn--container mt-4">
                                <button type="submit" class="btn btn--primary">{{translate('save_changes')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if (!config('module.'.$store->module->module_type)['always_open'])
        <div class="card mt-3">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{translate('messages.Provder_Active_Time')}}
                    </h5>
                    <p class="fs-12 mb-0">
                        {{ translate('Set the time when Provder is active to show in app and website') }}
                    </p>
                </div>
            </div>
            <div class="card-body" id="schedule">
                @include('vendor-views.business-settings.partials._schedule', $store)
            </div>
        </div>
        @endif
    </div>

    <!-- Create schedule modal -->

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-title="{{ translate('messages.Create Schedule For ') }} ">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">{{translate('messages.Create Schedule For ')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="javascript:" method="post" id="add-schedule" data-route="{{route('vendor.business-settings.add-schedule')}}">
                        @csrf
                        <input type="hidden" name="day" id="day_id_input">
                        <div class=" ">
                            <label for="recipient-name" class="col-form-label">{{translate('messages.Start time')}}:</label>
                            <input type="time"  id="recipient-name" class="form-control" name="start_time" required>
                        </div>
                        <div class=" ">
                            <label for="message-text" class="col-form-label">{{translate('messages.End time')}}:</label>
                            <input type="time" id="message-text" class="form-control" name="end_time" required>
                        </div>
                        <div class="btn--container mt-4 justify-content-end">
                            <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{translate('messages.Submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="button-title" data-title="{{translate('Want_to_delete_this_schedule?')}}"></div>
    <div id="button-text" data-text="{{translate('If_you_select_Yes,_the_time_schedule_will_be_deleted.')}}"></div>
    <div id="button-cancel" data-no="{{ translate('no') }}"></div>
    <div id="button-accept" data-yes="{{ translate('yes') }}"></div>
    <div id="button-success" data-success="{{translate('messages.Schedule removed successfully')}}"></div>
    <div id="button-error" data-error="{{translate('messages.Schedule removed successfully')}}"></div>
    <div id="button-added" data-error="{{translate('messages.Schedule added successfully')}}"></div>
@endsection

@push('script_2')
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/setting.js') }}"></script>
@endpush
