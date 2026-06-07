@extends('layouts.vendor.app')

@section('title', translate('messages.vehicle_details'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/vendor/simplebar/dist/simplebar.min.css') }}">
    <link rel="stylesheet" href="{{ asset('/public/assets/admin/vendor/drift-zoom/dist/drift-basic.min.css') }}">
    <link rel="stylesheet" href="{{ asset('Modules/Rental/public/assets/css/provider/vehicle.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/car-logo.png') }}" alt="">
                        </span>
                        <span>{{ $vehicle->name }}
                    </h1>
                </div>
                <div class="d-flex align-items-start flex-wrap gap-2">
                    <a class="btn btn--cancel h--45px d-flex gap-2 align-items-center form-alert" href="javascript:"
                       data-id="vehicle-{{$vehicle['id']}}" data-message="{{ translate('Want to delete this vehicle') }}" title="{{translate('messages.delete_vehicle')}}">
                        <i class="tio-delete"></i>
                        {{ translate('messages.delete') }}
                    </a>

                    <form action="{{route('vendor.vehicle.delete',[$vehicle['id']])}}?vehicle_list={{request()->vehicle_list}}" method="post" id="vehicle-{{$vehicle->id}}">
                        @csrf @method('delete')
                    </form>
                    <a href="javascript:" class="btn btn--reset d-flex justify-content-between align-items-center gap-4 lh--1 h--45px">
                        {{ translate('messages.new_tag') }}
                        <label class="toggle-switch toggle-switch-sm" for="stocksCheckboxNew{{$vehicle->id}}">
                            <input type="checkbox" data-url="{{route('vendor.vehicle.new-tag',[$vehicle['id'],$vehicle->new_tag?0:1])}}"
                                   class="toggle-switch-input redirect-url" id="stocksCheckboxNew{{$vehicle->id}}" {{$vehicle->new_tag?'checked':''}}>
                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                        </label>
                    </a>
                    <a href="javascript:" class="btn btn--reset d-flex justify-content-between align-items-center gap-4 lh--1 h--45px">
                        {{ translate('messages.status') }}
                        <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$vehicle->id}}">
                            <input type="checkbox" data-url="{{route('vendor.vehicle.status',[$vehicle['id'],$vehicle->status?0:1])}}"
                                   class="toggle-switch-input redirect-url" id="stocksCheckbox{{$vehicle->id}}" {{$vehicle->status?'checked':''}}>
                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                        </label>
                    </a>
                    <a href="{{ route('vendor.vehicle.edit', $vehicle->id)}}" class="btn btn--primary h--45px d-flex gap-2 align-items-center">
                        <i class="tio-edit"></i>
                        {{ translate('messages.Edit_Vechicle') }}
                    </a>
                </div>
            </div>
        </div>
        <div class="card mb-20">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="cz-product-gallery mb-20 mb-lg-0">
                            <div class="cz-preview">
                                <div id="sync1" class="owl-carousel owl-theme product-thumbnail-slider">
                                    <div class="owl-item active">
                                        <div class="product-preview-item d-flex align-items-center justify-content-center active"
                                             id="000">
                                            <img class="cz-image-zoom img-responsive w-100"
                                                 src="{{ $vehicle['thumbnailFullUrl'] }}"
                                                 data-zoom="{{ $vehicle['thumbnailFullUrl'] }}"
                                                 alt="Product" width="">
                                            <div class="cz-image-zoom-pane"></div>
                                        </div>
                                    </div>
                                    @foreach($vehicle['imagesFullUrl'] as $key => $img)
                                        <div class="owl-item ">
                                            <div class="product-preview-item d-flex align-items-center justify-content-center active"
                                                 id="image{{$key}}">
                                                <img class="cz-image-zoom img-responsive w-100"
                                                     src="{{ $img }}"
                                                     data-zoom="{{ $img }}"
                                                     alt="Product" width="">
                                                <div class="cz-image-zoom-pane"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="cz">
                                <div class="table-responsive" data-simplebar>
                                    <div class="d-flex">
                                        <div id="sync2" class="owl-carousel owl-theme product-thumb-slider">

                                            <div class="">
                                                <a class="product-preview-thumb color-variants-preview-box-CD5C5C active d-flex align-items-center justify-content-center"
                                                   id="preview-imgCD5C5C" href="#000">
                                                    <img alt="Product"
                                                         src="{{ $vehicle['thumbnailFullUrl'] }}">
                                                </a>
                                            </div>
                                            @foreach($vehicle['imagesFullUrl'] as $key => $img)
                                                <div class="">
                                                    <a class="product-preview-thumb color-variants-preview-box-CD5C5C active d-flex align-items-center justify-content-center"
                                                       id="preview-imgCD5C5C" href="#{{$key}}1">
                                                        <img alt="Product"
                                                             src="{{ $img }}">
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <div>
                            <div class="d-flex flex-column-reverse flex-lg-row gap-20px gap-lg-40px">
                                @if ($language)
                                    <ul class="nav nav-tabs border-0 mb-4 flex-grow-1 flex-nowrap">
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
                                @endif
                                <div class="floating-review-wrapper">
                                    <div class="rating--review border rounded">
                                        <h5 class="title border-line font-medium d-flex align-items-center lh--1 mb-0">
                                                <span class="fs-14">
                                                    <span class="font-bold">{{ $avgRating }}</span>
                                                    <span class="color-758590">/5</span>
                                                </span>
                                            <div class="info text--title fs-14">{{ $totalReviews }} {{ translate('Reviews') }}</div>
                                        </h5>
                                    </div>
                                    <ul class="list-unstyled list-unstyled-py-2 mb-0 rating--review-right review-color-progress">
                                        <!-- Review Ratings -->
                                        <li class="d-flex align-items-center font-size-sm">
                                            <span class="progress-name mr-3">{{ translate('Excellent') }}</span>
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $totalRating > 0 ? ($excellentCount / $totalRating) * 100 : 0 }}%;"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="ml-3">{{ $excellentCount }}</span>
                                        </li>
                                        <!-- End Review Ratings -->

                                        <!-- Review Ratings -->
                                        <li class="d-flex align-items-center font-size-sm">
                                            <span class="progress-name mr-3">{{ translate('Good') }}</span>
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $totalRating > 0 ? ($goodCount / $totalRating) * 100 : 0 }}%;"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="ml-3">{{ $goodCount }}</span>
                                        </li>
                                        <!-- End Review Ratings -->

                                        <!-- Review Ratings -->
                                        <li class="d-flex align-items-center font-size-sm">
                                            <span class="progress-name mr-3">{{translate('Average')}}</span>
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $totalRating > 0 ? ($averageCount / $totalRating) * 100 : 0 }}%;"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="ml-3">{{ $averageCount }}</span>
                                        </li>
                                        <!-- End Review Ratings -->

                                        <!-- Review Ratings -->
                                        <li class="d-flex align-items-center font-size-sm">
                                            <span class="progress-name mr-3">{{translate('Below average')}}</span>
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $totalRating > 0 ? ($belowAverageCount / $totalRating) * 100 : 0 }}%;"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="ml-3">{{ $belowAverageCount }}</span>
                                        </li>
                                        <!-- End Review Ratings -->

                                        <!-- Review Ratings -->
                                        <li class="d-flex align-items-center font-size-sm">
                                            <span class="progress-name mr-3">{{translate('Poor')}}</span>
                                            <div class="progress flex-grow-1">
                                                <div class="progress-bar" role="progressbar"
                                                     style="width: {{ $totalRating > 0 ? ($poorCount / $totalRating) * 100 : 0 }}%;"
                                                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="ml-3">{{ $poorCount }}</span>
                                        </li>
                                        <!-- End Review Ratings -->
                                    </ul>
                                </div>
                            </div>
                            @if ($language)
                                <div class="lang_form text--title" id="default-form">
                                    <h3 class="text--title fs-20 ont-bold mb-10px">{{$vehicle?->getRawOriginal('name')}}</h3>
                                    <h5 class="text--title font-semibold opacity-lg mb-10px">Description:</h5>
                                    <div class="fs-12 opacity-lg description-text">
                                        <span class="short-description">{{ Str::limit($vehicle?->getRawOriginal('description'), 2100) }}</span>
                                        <span class="full-description display-none">{{$vehicle?->getRawOriginal('description')}}</span>
                                        <a href="#" class="text--info font-medium see-more">See more</a>
                                    </div>
                                </div>

                                @foreach ($language as $lang)
                                    @php
                                        if(count($vehicle['translations'])){
                                            $translate = [];
                                            foreach($vehicle['translations'] as $t)
                                            {
                                                if($t->locale == $lang && $t->key=="name"){
                                                    $translate[$lang]['name'] = $t->value;
                                                }
                                            }
                                        }
                                    @endphp
                                    <div class="lang_form d-none text--title" id="{{ $lang }}-form">
                                        <h3 class="text--title fs-20 ont-bold mb-10px">{{$translate[$lang]['name']??''}}</h3>
                                        <h5 class="text--title font-semibold opacity-lg mb-10px">Description:</h5>
                                        <div class="fs-12 opacity-lg description-text">
                                            <span class="short-description">{{ Str::limit($translate[$lang]['description'] ?? '', 2100) }}</span>
                                            <span class="full-description display-none">{{$translate[$lang]['description'] ?? ''}}</span>
                                            <a href="#" class="text--info font-medium see-more">See more</a>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-20">
            <div class="col-lg-3 mb-20 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column justify-content-center">
                        <a class="resturant--information-single" href="{{ url('vendor-panel/store/view')}}">
                            <img class="img--65 rounded mx-auto mb-3 onerror-image" data-onerror-image=""
                                 src="{{ $vehicle?->provider['logoFullUrl'] }}" alt="Image Description">
                            <div class="text-center text--title">
                                <h5 class="text-capitalize font-semibold text-hover-primary d-block mb-1">
                                    {{ $vehicle?->provider?->name }}
                                </h5>
                                <span class="opacity-lg">
                                    {{ $vehicle?->provider?->address }}
                                </span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="card h-100">
                    <!-- Table -->
                    <div class="table-responsive">
                        <table id="" class="table table-borderless table-thead-bordered table-nowrap card-table">
                            <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.General_Info') }}</th>
                                <th class="border-0">{{ translate('messages.Fare_&_Discounts') }}</th>
                                <th class="border-0">{{ translate('messages.Other_Features') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            <tr>
                                <td>
                                    <div>
                                        <div class="d-flex"> <span class="min-w-110px">{{ translate('Brand') }}</span><span
                                                class="font-semibold">: {{ $vehicle?->brand?->name }}</span></div>
                                        <div class="d-flex"><span class="min-w-110px">{{ translate('Category') }}</span><span
                                                class="font-semibold">: {{ $vehicle?->category?->name }}</span></div>
                                        <div class="d-flex"><span class="min-w-110px">{{ translate('Type') }}</span><span
                                                class="font-semibold">: {{ $vehicle?->type }}</span></div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        @if($vehicle->trip_hourly)
                                            <div class="d-flex"> <span class="min-w-110px">{{translate('Hourly')}}</span>
                                                <span class="font-semibold">: {{\App\CentralLogics\Helpers::format_currency($vehicle['hourly_price'])}}</span>
                                            </div>
                                        @endif
                                        @if($vehicle->trip_distance)
                                            <div class="d-flex"><span class="min-w-110px">{{ translate('Distance Wise')}}</span>
                                                <span class="font-semibold">:{{\App\CentralLogics\Helpers::format_currency($vehicle['distance_price'])}}</span>
                                            </div>
                                        @endif
                                        <div class="d-flex"><span class="min-w-110px">{{translate('Discount')}}</span><span
                                                class="font-semibold">: {{ $vehicle->discount_price }} {{ $vehicle->discount_type == 'percent' ? '%' : '$' }}</span></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between gap-20px">
                                        <div>
                                            <div class="d-flex"> <span class="min-w-110px">{{translate('Air Condition')}}</span><span
                                                    class="font-semibold">: {{ $vehicle->air_condition ? 'Yes' : 'No' }}</span></div>
                                            <div class="d-flex"><span class="min-w-110px">{{translate('Transmission')}}</span><span
                                                    class="font-semibold">:
                                                        {{ ucwords($vehicle->transmission_type) }}</span></div>
                                            <div class="d-flex"><span class="min-w-110px">{{translate('Fuel Type')}}</span><span
                                                    class="font-semibold">: {{ $vehicle->fuel_type }}</span></div>
                                        </div>
                                        <div>
                                            <div class="d-flex"> <span class="min-w-110px">{{translate('Engine Capacity')}}</span><span
                                                    class="font-semibold">: {{ $vehicle->engine_capacity }}</span></div>
                                            <div class="d-flex"><span class="min-w-110px">{{translate('Seating Capacity')}}</span><span
                                                    class="font-semibold">:
                                                        {{ $vehicle->seating_capacity }}</span></div>
                                            <div class="d-flex"><span class="min-w-110px">{{translate('Engine Power')}}</span><span
                                                    class="font-semibold">: {{ $vehicle->engine_power }}</span></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                    <!-- End Table -->
                </div>
            </div>
        </div>

        <div class="card mb-20">
            <!-- Table -->
            <div class="table-responsive">
                <table id="" class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{ translate('messages.Identity_Info') }}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    <tr>
                        <td>
                            <div class="row">
                                @foreach($vehicle->vehicleIdentities as $multi)
                                <div class="col-lg-4 col-md-6">
                                    <div class="font-semibold text--title">
                                        <div class="opacity-70 mb-2">{{translate('Vehicle')}} {{ $loop->iteration }}</div>
                                        <div class="border rounded p-3 d-flex gap-4 justify-content-between">
                                            <div>
                                                <div class="fs-12 opacity-60">{{translate('VIN Number')}}</div>
                                                <div>{{ $multi->vin_number }}</div>
                                            </div>
                                            <div class="pr-4">
                                                <div class="fs-12 opacity-60">{{translate('Registration No.')}}</div>
                                                <div>{{ $multi->license_plate_number }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>

            </div>
            <!-- End Table -->
        </div>
        <div class="card mb-20">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{ translate('messages.Additional_Documents') }}
                    </h5>
                    <p class="fs-12">
                        {{ translate('messages.Here you can see all images & document for the provider') }}
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 flex-wrap">
                    @foreach($vehicle['documentsFullUrl'] as $doc)
                        <div class="pdf-single" data-pdf-url="{{ $doc }}">
                            <div class="pdf-frame">
                                <canvas class="pdf-preview display-none"></canvas>
                                <img class="pdf-thumbnail" src="{{ $doc }}" alt="{{translate('File Thumbnail')}}">
                            </div>
                            <div class="overlay">
                                <a href="javascript:void(0);" class="download-btn">
                                    <i class="tio-download-to"></i>
                                </a>
                                <div class="pdf-info d-flex gap-10px align-items-center">
                                    <img src="{{ asset('public/assets/admin/img/document.svg') }}" width="34" alt="{{translate('Document Logo')}}">
                                    <div class="fs-13 text--title d-flex flex-column">
                                        <span class="file-name"></span>
                                        <span class="opacity-50">{{translate('Click to view the file')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title text--title">
                        {{ translate('messages.Reviews') }}
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $vehicleReview->total() }}</span>
                    </h5>
                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40 font-semibold"
                           href="javascript:;"
                           data-hs-unfold-options='{
                            "target": "#usersExportDropdown",
                            "type": "css-animation"
                        }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                               href="{{ route('vendor.vehicle.review.export', ['vehicle_id' => request()->id, 'type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="{{translate('Image Description')}}">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                               href="{{ route('vendor.vehicle.review.export', ['vehicle_id' => request()->id, 'type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                     alt="{{translate('Image Description')}}">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->
            @php($store_review_reply = App\Models\BusinessSetting::where('key' , 'store_review_reply')->first()->value ?? 0)
            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                       class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging": false
                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('messages.#')}}</th>
                        <th class="border-0">{{translate('messages.Review_Id')}}</th>
                        <th class="border-0">{{translate('messages.Vehicle')}}</th>
                        <th class="border-0">{{translate('messages.reviewer')}}</th>
                        <th class="border-0">{{translate('messages.review')}}</th>
                        <th class="border-0">{{translate('messages.date')}}</th>
                        <th class="border-0">{{translate('messages.Reply_date')}}</th>
                        @if($store_review_reply == '1')
                            <th class="text-center">{{translate('messages.action')}}</th>
                        @endif
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($vehicleReview as $key=>$review)
                        <tr>
                            <td>{{$key+$vehicleReview->firstItem()}}</td>
                            <td>{{$review->review_id}}</td>
                            <td>
                                @if ($review->vehicle)
                                    <div class="position-relative media align-items-center">
                                        <a class=" text-hover-primary absolute--link" href="{{route('vendor.vehicle.details',$review->vehicle_id)}}">
                                            <img class="avatar avatar-lg mr-3  onerror-image"  data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                                 src="{{ $review->vehicle->thumbnail_full_url }}" alt="{{$review?->vehicle?->name}} {{translate('image')}}">
                                        </a>
                                        <div class="media-body">
                                            <h5 class="text-hover-primary important--link mb-0">{{Str::limit($review?->vehicle?->name,10)}}</h5>
                                            <!-- Static -->
                                            <a href="{{route('vendor.trip.details',$review->trip_id)}}"  class="fz--12 text-body important--link">{{ translate('Trip ID') }} #{{$review->trip_id}}</a>
                                            <!-- Static -->
                                        </div>
                                    </div>
                                @else
                                    {{translate('messages.Food_deleted!')}}
                                @endif
                            </td>
                            <td>
                                @if($review->customer)
                                    <div>
                                        <h5 class="d-block text-hover-primary mb-1">{{Str::limit($review->customer['f_name']." ".$review->customer['l_name'])}} <i
                                                class="tio-verified text-primary" data-toggle="tooltip" data-placement="top"
                                                title="{{translate('Verified Customer')}}"></i></h5>
                                        <span class="d-block font-size-sm text-body">{{Str::limit($review->customer->phone)}}</span>
                                    </div>
                                @else
                                    {{translate('messages.customer_not_found')}}
                                @endif
                            </td>
                            <td>
                                <div class="text-wrap w-18rem">
                                    <label class="rating">
                                        <i class="tio-star"></i>
                                        <span>{{$review->rating}}</span>
                                    </label>
                                    <p data-toggle="tooltip" data-placement="bottom"
                                       data-original-title="{{ $review?->comment }}" >
                                        {{Str::limit($review['comment'], 80)}}
                                    </p>
                                </div>
                            </td>
                            <td>
                                <span class="d-block">
                                    {{ \App\CentralLogics\Helpers::date_format($review->created_at)  }}
                                </span>
                                <span class="d-block"> {{ \App\CentralLogics\Helpers::time_format($review->created_at)  }}</span>
                            </td>
                            <td>
                                <p class="text-wrap" data-toggle="tooltip" data-placement="top"
                                   data-original-title="{{ $review?->reply }}">{!! $review->reply?Str::limit($review->reply, 50, '...'): translate('messages.Not_replied_Yet') !!}</p>
                            </td>
                            @if($store_review_reply == '1')
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a  class="btn btn-sm btn--primary {{ $review->reply ? 'btn-outline-primary' : ''}}" data-toggle="modal" data-target="#reply-{{$review->id}}" title="{{translate('View Details')}}">
                                            {{ $review->reply ? translate('view_reply') : translate('give_reply')}}
                                        </a>
                                    </div>
                                </td>
                            @endif
                            <div class="modal fade" id="reply-{{$review->id}}">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header pb-4">
                                            <button type="button" class="payment-modal-close btn-close border-0 outline-0 bg-transparent" data-dismiss="modal">
                                                <i class="tio-clear"></i>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="position-relative media align-items-center">
                                                <a class="absolute--link" href="{{route('vendor.vehicle.details',$review->vehicle_id)}}">
                                                </a>
                                                <img class="avatar avatar-lg mr-3  onerror-image"  data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                                     src="{{ $review?->vehicle?->thumbnail_full_url }}" alt="{{$review?->vehicle?->name}} {{translate('image')}}">
                                                <div>
                                                    <h5 class="text-hover-primary mb-0">{{ $review?->vehicle?->name }}</h5>
                                                    @if ($review?->vehicle?->avg_rating == 5)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 5 && $review?->vehicle?->avg_rating >= 4.5)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-half"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 4.5 && $review?->vehicle?->avg_rating >= 4)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 4 && $review?->vehicle?->avg_rating >= 3.5)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-half"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 3.5 && $review?->vehicle?->avg_rating >= 3)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 3 && $review?->vehicle?->avg_rating >= 2.5)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-half"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 2.5 && $review?->vehicle?->avg_rating > 2)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 2 && $review?->vehicle?->avg_rating >= 1.5)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-half"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 1.5 && $review?->vehicle?->avg_rating > 1)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating < 1 && $review?->vehicle?->avg_rating > 0)
                                                        <div class="rating">
                                                            <span><i class="tio-star-half"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating == 1)
                                                        <div class="rating">
                                                            <span><i class="tio-star"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @elseif ($review?->vehicle?->avg_rating == 0)
                                                        <div class="rating">
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                            <span><i class="tio-star-outlined"></i></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="mt-2">
                                                @if($review->customer)
                                                    <div>
                                                        <h5 class="d-block text-hover-primary mb-1">{{Str::limit($review?->customer?->fullName)}} <i
                                                                class="tio-verified text-primary" data-toggle="tooltip" data-placement="top"
                                                                title="{{translate('Verified Customer')}}"></i></h5>
                                                        <span class="d-block font-size-sm text-body">{{$review->comment}}</span>
                                                    </div>
                                                @else
                                                    {{translate('messages.customer_not_found')}}
                                                @endif
                                            </div>
                                            <div class="mt-3">
                                                <form action="{{route('vendor.rental.review.reply', $review->id)}}" method="POST">
                                                    @csrf
                                                    <textarea id="reply" name="reply" required class="form-control" cols="30" rows="3" placeholder="{{ translate('Write_your_reply_here') }}">{{ $review->reply ?? '' }}</textarea>
                                                    <div class="mt-3 btn--container justify-content-end">
                                                        <button class="btn btn-primary">{{ $review->reply ? translate('update_reply') : translate('send_reply')}}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
            @if(count($vehicleReview) !== 0)
                <hr>
            @endif
            <div class="page-area mt-3">
                {!! $vehicleReview->appends($_GET)->links() !!}
            </div>
            @if(count($vehicleReview) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
            @endif
            <!-- End Table -->
        </div>
    </div>
    <!-- End Modal -->
@endsection

@push('script_2')
    <script src="{{ asset('/public/assets/admin/vendor/simplebar/dist/simplebar.min.js') }}"></script>
    <script src="{{ asset('/public/assets/admin/vendor/drift-zoom/dist/Drift.min.js') }}"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/pdf.min.js') }}"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/vehicle-details.js') }}"></script>
@endpush
