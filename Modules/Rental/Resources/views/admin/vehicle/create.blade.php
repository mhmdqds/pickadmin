@extends('layouts.admin.app')

@section('title', translate('messages.Add New Vehicle'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-20">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/car-logo.png') }}" alt="">
                        </span>
                        <span>{{ translate('messages.Add New Vehicle') }}
                    </h1>
                </div>
            </div>
        </div>
        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
        @php($language = $language->value ?? null)

        <form action="" method="post" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="card mt-4">
                        <div class="card-header">
                            <div>
                                <h5 class="text-title mb-1">
                                    {{ translate('messages.General_Information') }}
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Insert the basic information of the vehicle') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="card __bg-FAFAFA border-0">
                                        <div class="card-body">
                                            @if ($language)
                                                <ul class="nav nav-tabs border-0 mb-4">
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link active" href="#"
                                                           id="default-link">{{ translate('Default') }}</a>
                                                    </li>
                                                    @foreach (json_decode($language) as $lang)
                                                        <li class="nav-item">
                                                            <a class="nav-link lang_link" href="#"
                                                               id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                            @if ($language)
                                                <div class="lang_form" id="default-form">
                                                    <div class="form-group mb-20">
                                                        <label class="input-label font-semibold"
                                                               for="default_name">{{ translate('messages.vehicle_name') }}
                                                            ({{ translate('messages.Default') }})<span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" name="name[]" id="default_name"
                                                               class="form-control"
                                                               value="{{ old('name.0') }}"
                                                               placeholder="{{ translate('messages.type_vehicle_name') }}"
                                                               required>
                                                    </div>
                                                    <input type="hidden" name="lang[]" value="default">
                                                    <div class="form-group mb-0">
                                                        <label class="input-label font-semibold"
                                                               for="exampleFormControlInput1">{{ translate('messages.short_description') }}
                                                            ({{ translate('messages.default') }})</label>
                                                        <textarea type="text" name="description[]" placeholder="{{ translate('messages.type_short_description') }}"
                                                                  class="form-control min-h-90px ckeditor">{{ old('description.0') }}</textarea>
                                                    </div>
                                                </div>
                                                @foreach (json_decode($language) as $key => $lang)
                                                    <div class="d-none lang_form" id="{{ $lang }}-form">
                                                        <div class="form-group mb-20">
                                                            <label class="input-label font-semibold"
                                                                   for="{{ $lang }}_name">{{ translate('messages.vehicle_name') }}
                                                                ({{ strtoupper($lang) }})
                                                            </label>
                                                            <input type="text" name="name[]"
                                                                   id="{{ $lang }}_name" class="form-control" value="{{ old('name.'.$key+1) }}"
                                                                   placeholder="{{ translate('messages.vehicle_name') }}">
                                                        </div>
                                                        <input type="hidden" name="lang[]" value="{{ $lang }}">
                                                        <div class="form-group mb-0">
                                                            <label class="input-label font-semibold"
                                                                   for="exampleFormControlInput1">{{ translate('messages.short_description') }}
                                                                ({{ strtoupper($lang) }})</label>
                                                            <textarea type="text" name="description[]" placeholder="{{ translate('messages.vehicle_description') }}"
                                                                      class="form-control min-h-90px ckeditor">{{ old('description.'.$key+1) }}</textarea>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div id="default-form">
                                                    <div class="form-group mb-0">
                                                        <label class="input-label font-semibold"
                                                               for="exampleFormControlInput1">{{ translate('messages.vehicle_name') }}
                                                            ({{ translate('messages.default') }})</label><span class="text-danger">*</span>
                                                        <input type="text" name="name[]" class="form-control"
                                                               placeholder="{{ translate('messages.vehicle_name') }}" required>
                                                    </div>
                                                    <input type="hidden" name="lang[]" value="default">
                                                    <div class="form-group mb-0">
                                                        <label class="input-label font-semibold"
                                                               for="exampleFormControlInput1">{{ translate('messages.short_description') }}
                                                        </label>
                                                        <textarea type="text" name="description[]" placeholder="{{ translate('messages.vehicle_description') }}"
                                                                  class="form-control min-h-90px ckeditor"></textarea>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                </div>
                                <div class="col-lg-6">
                                    <div class="text-center">
                                        <label class="text--title fs-16 font-semibold mb-1">
                                            {{ translate('Vehicle_Thumbnail') }}<span class="text-danger">*</span>
                                        </label>
                                        <div class="mb-20">
                                            <p class="fs-12">
                                                {{ translate('JPG, JPEG, PNG Less Than 1MB') }} <strong class="font-semibold">({{ translate('Ratio 2:1') }})</strong>
                                            </p>
                                        </div>
                                        <div class="upload-file image-general d-inline-block w-auto">
                                            <a href="javascript:void(0);" class="remove-btn opacity-0 z-index-99">
                                                <i class="tio-clear"></i>
                                            </a>
                                            <input type="file" name="thumbnail" class="upload-file__input single_file_input"
                                                accept=".webp, .jpg, .jpeg, .png"  value="" required>
                                            <label
                                                class="upload-file-wrapper height-150px max-w-300px aspect-2-1">
                                                <div class="upload-file-textbox text-center w-100">
                                                    <img width="34" height="34" src="{{ asset('public/assets/admin/img/document-upload.svg') }}" alt="">
                                                    <h6 class="mt-2 font-semibold text-center">
                                                        <span>{{ translate('Click to upload') }}</span>
                                                        <br>
                                                        {{ translate('or drag and drop') }}
                                                    </h6>
                                                </div>
                                                <img class="upload-file-img d-none ratio-2" width="300" height="150" loading="lazy"  src="" alt="">
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
                                    {{ translate('messages.Images') }}<span class="text-danger">*</span>
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.JPG, JPEG, PNG Less Than 1MB') }}
                                    <span class="font-semibold"> {{ translate('(Ratio 2:1)') }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="card-body py-1">

                            <div class="d-flex pt-20 pb-2 overflow-x-auto">
                               <div class="d-flex gap-3 flex-shrink-0" id="image_container">
                                   <div class="upload-file text-wrapper h--100px w--200px flex-shrink-0"
                                        id="image_upload_wrapper">
                                       <input type="file" name="images[]"
                                              class="upload-file__input multiple_image_input" accept=".webp, .jpg,.jpeg,.png" multiple required>
                                       <div
                                           class="upload-file__img d-flex gap-0 justify-content-center align-items-center h-100 max-w-300px p-0">
                                           <div class="upload-file__textbox">
                                               <img width="34" height="34"
                                                    src="{{ asset('public/assets/admin/img/document-upload.png') }}"
                                                    alt="" class="svg">
                                               <h6 class="mt-2 font-semibold">
                                                   <span class="text-info">{{ translate('Click to upload') }}</span><br>
                                                   {{ translate('or drag and drop') }}
                                               </h6>
                                           </div>
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
                                    {{ translate('messages.Vehicle_Information') }}
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Insert_The_Vehicle\'s_General_Informations') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_provider">{{ translate('messages.provider') }}<span class="text-danger">*</span></label>
                                        <select name="provider_id" id="choice_provider" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_vehicle_provider') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_provider') }}</option>
                                            @foreach($providers as $provider)
                                                <option value="{{ $provider->id }}"
                                                    {{ (old('provider_id') == $provider->id || request()->provider_id == $provider->id) ? 'selected' : '' }}>
                                                {{ $provider->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_brand">{{ translate('messages.brand') }}<span class="text-danger">*</span></label>
                                        <select name="brand_id" id="choice_brand" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_vehicle_brand') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_brand') }}</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                                    {{ $brand->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Model') }}<span class="text-danger">*</span></label>
                                        <input type="text" name="model" class="form-control" placeholder="Model Name" value="{{ old('model') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_category">{{ translate('messages.category') }}<span class="text-danger">*</span></label>
                                        <select name="category_id" id="choice_category" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_vehicle_category') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_category') }}</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_type">{{ translate('messages.type') }}<span class="text-danger">*</span></label>
                                        <select name="type" id="choice_type" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_vehicle_type') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_type') }}</option>
                                            <option value="family" {{ old('type') == 'family' ? 'selected' : '' }}>{{ translate('messages.family') }}</option>
                                            <option value="luxury" {{ old('type') == 'luxury' ? 'selected' : '' }}>{{ translate('messages.Luxury') }}</option>
                                            <option value="affordable" {{ old('type') == 'affordable' ? 'selected' : '' }}>{{ translate('messages.Affordable') }}</option>
                                            <option value="executives" {{ old('type') == 'executives' ? 'selected' : '' }}>{{ translate('messages.Executives') }}</option>
                                            <option value="compact" {{ old('type') == 'compact' ? 'selected' : '' }}>{{ translate('messages.Compact') }}</option>
                                            <option value="full_size" {{ old('type') == 'full_size' ? 'selected' : '' }}>{{ translate('messages.Full-Size') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Engine Capacity (cc)') }}<span class="text-danger">*</span></label>
                                        <input type="number" name="engine_capacity" class="form-control" placeholder="Ex: 450" value="{{ old('engine_capacity') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Engine Power (hp)') }}<span class="text-danger">*</span></label>
                                        <input type="number" name="engine_power" class="form-control" placeholder="Ex: 100" value="{{ old('engine_power') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Seating Capacity') }}<span class="text-danger">*</span></label>
                                        <input type="number" name="seating_capacity" class="form-control" placeholder="Input how many person can seat" value="{{ old('seating_capacity') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Air Condition') }}<span class="text-danger">*</span></label>
                                        <div class="resturant-type-group border">
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input" type="radio" value="1" name="air_condition" {{ old('air_condition') == '1' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ translate('messages.yes') }}</span>
                                            </label>
                                            <label class="form-check form--check mr-2 mr-md-4">
                                                <input class="form-check-input" type="radio" value="0" name="air_condition" {{ old('air_condition') == '0' ? 'checked' : '' }}>
                                                <span class="form-check-label">{{ translate('messages.no') }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_fuel_type">{{ translate('messages.fuel_type') }}<span class="text-danger">*</span></label>
                                        <select name="fuel_type" id="choice_fuel_type" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_fuel_type') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_fuel_type') }}</option>
                                            <option value="octan" {{ old('fuel_type') == 'octan' ? 'selected' : '' }}>{{ translate('messages.Octan') }}</option>
                                            <option value="diesel" {{ old('fuel_type') == 'diesel' ? 'selected' : '' }}>{{ translate('messages.diesel') }}</option>
                                            <option value="CNG" {{ old('fuel_type') == 'CNG' ? 'selected' : '' }}>{{ translate('messages.CNG') }}</option>
                                            <option value="petrol" {{ old('fuel_type') == 'petrol' ? 'selected' : '' }}>{{ translate('messages.Petrol') }}</option>
                                            <option value="electric" {{ old('fuel_type') == 'electric' ? 'selected' : '' }}>{{ translate('messages.Electric') }}</option>
                                            <option value="jet_fuel" {{ old('fuel_type') == 'jet_fuel' ? 'selected' : '' }}>{{ translate('messages.Jet Fuel') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="choice_transmission_type">{{ translate('messages.transmission_type') }}<span class="text-danger">*</span></label>
                                        <select name="transmission_type" id="choice_transmission_type" class="form-control js-select2-custom"
                                                data-placeholder="{{ translate('messages.select_vehicle_transmission') }}" required>
                                            <option value="" selected disabled>{{ translate('messages.select_vehicle_transmission') }}</option>
                                            <option value="automatic" {{ old('transmission_type') == 'automatic' ? 'selected' : '' }}>{{ translate('Automatic') }}</option>
                                            <option value="manual" {{ old('transmission_type') == 'manual' ? 'selected' : '' }}>{{ translate('Manual') }}</option>
                                            <option value="continuously_variable" {{ old('transmission_type') == 'continuously_variable' ? 'selected' : '' }}>{{ translate('Continuously Variable') }}</option>
                                            <option value="dual_clutch" {{ old('transmission_type') == 'dual_clutch' ? 'selected' : '' }}>{{ translate('Dual-Clutch') }}</option>
                                            <option value="semi_automatic" {{ old('transmission_type') == 'semi_automatic' ? 'selected' : '' }}>{{ translate('Semi-Automatic') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header flex-wrap gap-3">
                            <div class="flex-grow-1">
                                <h5 class="text-title mb-1">
                                    {{ translate('messages.Vehicle Identity') }}
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Insert_The_Vehicle\'s_Unique_Informations') }}
                                </p>
                            </div>
                            <label class="d-flex align-items-center gap-2">
                                <span class="text--title">
                                    {{ translate('messages.Same Model Multiple Vehicles') }}
                                </span>
                                <input class="form-check-input single-select position-relative m-0" type="checkbox" name="multiple_vehicles">
                            </label>
                        </div>
                        <div class="card-body d-flex flex-column gap-20px">
                            <div class="d-flex gap-20px flex-column flex-md-row equal-width" id="input-container">
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                           for="">{{ translate('messages.VIN Number') }}<span class="text-danger">*</span></label>
                                    <input type="text" name="vehicle[vin_number][]" class="form-control"
                                           placeholder="Type your vin number" value="" required>
                                </div>
                                <div class="form-group mb-0">
                                    <label class="input-label"
                                           for="">{{ translate('messages.License Plate Number') }}<span class="text-danger">*</span></label>
                                    <input type="text" name="vehicle[license_plate_number][]" class="form-control"
                                           placeholder="Type your license plate number" value="" required>
                                </div>
                                <button type="button"
                                     data-vin="{{ translate("messages.VIN Number") }}"
                                        data-license="{{ translate("messages.License Plate Number") }}"
                                        class="btn plus-btn shadow-none text--primary p-0 fs-32 lh--1 text-left mt-md-4 add-btn">
                                    <i class="tio-add-circle-outlined"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h5 class="text-title mb-1">
                                    {{ translate('messages.Pricing & Discounts') }}
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Insert_The_Pricing & Discount Informations') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Distance Wise Price ($)') }}<span class="text-danger">*</span></label>
                                        <div class="border resturant-type-group">
                                            <label class="align-items-center d-flex form-check item">
                                                <input class="form-check-input single-select" type="checkbox" name="trip_hourly"
                                                       value="hourly" {{ old('trip_hourly') == 'hourly' ? 'checked' : '' }}>
                                                <span class="form-check-label ml-2 mt-1">
                                                    {{ translate('messages.hourly') }}
                                                </span>
                                            </label>
                                            <label class="align-items-center d-flex form-check item">
                                                <input class="form-check-input single-select" type="checkbox" name="trip_distance"
                                                       value="distance_wise" {{ old('trip_distance') == 'distance_wise' ? 'checked' : '' }}>
                                                <span class="form-check-label ml-2 mt-1">
                                                    {{ translate('messages.Distance Wise') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Hourly Wise Price ($/per hour)') }}<span class="text-danger">*</span></label>
                                        <input type="number" name="hourly_price" class="form-control"
                                               placeholder="Ex: 35.25" min="0.01" step="0.01" value="{{ old('hourly_price') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label" for="">{{ translate('messages.Distance Wise Price ($/per km)') }}<span class="text-danger">*</span></label>
                                        <input type="number" name="distance_price" class="form-control"
                                               placeholder="Ex: 35.25" min="0.01" step="0.01" value="{{ old('distance_price') }}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group mb-0">
                                        <label class="input-label font-semibold" for="">{{ translate('messages.Discount') }}
                                            <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                  data-original-title="{{ translate('messages.The discount value should not be higher than the hourly price or distance-wise price') }}">
                                                <i class="tio-info text--title opacity-60"></i>
                                            </span>
                                        </label>
                                        <div class="custom-group-btn border">
                                            <div class="flex-sm-grow-1">
                                                <input id="discount_input" type="number" name="discount_price" class="form-control h--45px border-0 pl-unset"
                                                       placeholder="Ex: 10" min="0" step="0.001" value="{{ old('discount_price') }}">
                                            </div>
                                            <div class="flex-shrink-0">
                                                <select name="discount_type" id="discount_type" class="custom-select ltr border-0">
                                                    <option value="percent" {{ old('discount_type') == 'percent' ? 'selected' : '' }}>%</option>
                                                    <option value="amount" {{ old('discount_type') == 'amount' ? 'selected' : '' }}>
                                                        {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                                    </option>
                                                </select>
                                            </div>
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
                                    {{ translate('messages.Search_Tags') }}
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Insert_The_Tags_For_Appear_In_User\'s_Search_List') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-0 pickup-zone-tag">
                                <select name="tag[]" id="pickup_zones12"
                                        class="form-control js-select2-custom select2-hidden-accessible" multiple="multiple">
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h5 class="text-title mb-1">
                                    {{ translate('messages.Vehicle_Documents') }}<span class="text-danger">*</span>
                                </h5>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.Upload_Vehicle\'s_Important_Documents') }}
                                </p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex py-3 overflow-x-auto">
                                <div class="d-flex gap-3 flex-shrink-0" id="pdf-container">
                                    <div class="upload-file text-wrapper document-wrapper" id="upload-wrapper">
                                        <input type="file" name="documents[]"
                                            class="upload-file__input multiple_document_input" accept="*"
                                            multiple required>
                                        <div
                                            class="upload-file__img d-flex justify-content-center align-items-center h-100 max-w-300px p-0">
                                            <div class="upload-file__textbox pdf">
                                                <img width="34" height="34"
                                                    src="{{ asset('public/assets/admin/img/document-upload.png') }}"
                                                    alt="" class="svg">
                                                <h6 class="font-semibold">
                                                    <span class="text-info">{{ translate('Click to upload') }}</span><br>
                                                    {{ translate('or drag and drop') }}
                                                </h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="btn--container justify-content-end mt-3">
                        <button type="reset" id="reset_btn"
                                class="btn btn--reset min-w-120px">{{ translate('messages.reset') }}</button>
                        <button type="submit"
                                class="btn btn--primary min-w-120px">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

<input type="hidden" id="file_size_error_text" value="{{ translate('file_size_too_big') }}">
<input type="hidden" id="file_type_error_text" value="{{ translate('please_only_input_png_or_jpg_type_file') }}">
<input type="hidden" id="max_file_upload_limit_error_text" value="{{ translate('maximum_file_upload_limit_is_') }}">



<div id="file-assets"
    data-picture-icon="{{ asset('public/assets/admin/img/picture.svg') }}"
    data-document-icon="{{ asset('public/assets/admin/img/document.svg') }}"
    data-blank-thumbnail="{{ asset('public/assets/admin/img/blank2.png') }}">
</div>

@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/pdf.min.js') }}"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/admin/view-pages/vehicle-create.js') }}"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/multiple-upload.js') }}"></script>

@endpush
