@extends('layouts.admin.app')

@section('title',$store->name)

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">
    <link href="{{asset('Modules/Rental/public/assets/css/admin/provider-overview.css')}}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">

        @include('rental::admin.provider.details.partials._header',['store'=>$store])

        <!-- Page Heading -->
        @if($store->vendor->status)
            <div class="row g-3 text-capitalize">
                <!-- Earnings (Monthly) Card Example -->
                <div class="col-md-4">
                    <div class="card h-100 card--bg-1">
                        <div class="card-body text-center d-flex flex-column justify-content-center align-items-center">
                            <h5 class="cash--subtitle text-white">
                                {{translate('messages.collected_cash_by_provider')}}
                            </h5>
                            <div class="d-flex align-items-center justify-content-center mt-3">
                                <div class="cash-icon mr-3">
                                    <img src="{{asset('public/assets/admin/img/cash.png')}}" alt="img">
                                </div>
                                <h2 class="cash--title text-white">{{\App\CentralLogics\Helpers::format_currency($wallet->collected_cash)}}</h2>
                            </div>
                        </div>
                        <div class="card-footer pt-0 bg-transparent border-0">
                            <button class="btn text-white text-capitalize bg--title h--45px w-100" id="collect_cash"
                                    type="button" data-toggle="modal" data-target="#collect-cash"
                                    title="Collect Cash">{{ translate('messages.collect_cash_from_provider') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <!-- Panding Withdraw Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-2">
                                <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->pending_withdraw)}}</h4>
                                <div class="subtitle">{{translate('messages.pending_withdraw')}}</div>
                                <img class="resturant-icon w--30"
                                     src="{{asset('public/assets/admin/img/transactions/pending.png')}}"
                                     alt="transaction">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-3">
                                <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_withdrawn)}}</h4>
                                <div class="subtitle">{{translate('messages.total_withdrawal_amount')}}</div>
                                <img class="resturant-icon w--30"
                                     src="{{asset('public/assets/admin/img/transactions/withdraw-amount.png')}}"
                                     alt="transaction">
                            </div>
                        </div>

                        <!-- Collected Cash Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-4">
                                <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->balance>0?$wallet->balance:0)}}</h4>
                                <div class="subtitle">{{translate('messages.withdraw_able_balance')}}</div>
                                <img class="resturant-icon w--30"
                                     src="{{asset('public/assets/admin/img/transactions/withdraw-balance.png')}}"
                                     alt="transaction">
                            </div>
                        </div>

                        <!-- Pending Requests Card Example -->
                        <div class="col-sm-6">
                            <div class="resturant-card card--bg-1">
                                <h4 class="title">{{\App\CentralLogics\Helpers::format_currency($wallet->total_earning)}}</h4>
                                <div class="subtitle">{{translate('messages.total_earning')}}</div>
                                <img class="resturant-icon w--30"
                                     src="{{asset('public/assets/admin/img/transactions/earning.png')}}"
                                     alt="transaction">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endif
        @php
            $all = $store?->trips?->count();
            $completed = $store?->trips?->where('trip_status', 'completed')?->count();
            $canceled = $store?->trips?->where('trip_status', 'canceled')?->count();
        @endphp
        <div class="card mt-4 p-4">
            <div class="row g-2" id="order_stats">
                <div class="col-lg-3 col-sm-6">
                    <!-- Card -->
                    <a class="order--card h-100" href="#">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                {{translate('All')}}
                            </h6>
                            <span class="card-title text--info">
                                {{ $all }}
                            </span>
                        </div>
                    </a>
                    <!-- End Card -->
                </div>
                <div class="col-lg-3 col-sm-6">
                    <!-- Card -->
                    <a class="order--card h-100" href="#">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                {{translate('Completed')}}
                            </h6>
                            <span class="card-title text--success">
                                {{ $completed }}
                            </span>
                        </div>
                    </a>
                    <!-- End Card -->
                </div>
                <div class="col-lg-3 col-sm-6">
                    <!-- Card -->
                    <a class="order--card h-100" href="#">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                {{translate('Canceled')}}
                            </h6>
                            <span class="card-title text--danger">
                                {{ $canceled }}
                            </span>
                        </div>
                    </a>
                    <!-- End Card -->
                </div>
                <div class="col-lg-3 col-sm-6">
                    <!-- Card -->
                    <a class="order--card h-100" href="#">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                                {{translate('Cancellation rate')}}
                            </h6>
                            <span class="card-title text--warning">
                                {{ number_format($canceled > 0 ? ($canceled / $all) * 100 : 0) }}%
                            </span>
                        </div>
                    </a>
                    <!-- End Card -->
                </div>
            </div>
        </div>
        <div class="taxi-banner radius-10 mt-4 mb-20"
             style="background-image: url('{{ $store->cover_photo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}'); background-repeat: no-repeat; background-position: center; background-size: cover;">
            <div class="taxi-info-wrapper d-flex flex-wrap flex-sm-nowrap gap-30px">
                <div class="logo">
                    <img data-onerror-image="{{asset('public/assets/admin/img/100x100/1.png')}}"
                         src="{{ $store->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}" width="150" class="rounded-8"
                         alt="">
                </div>
                <div class="taxi-info">
                    <h3 class="fs-20 fw-bold text--title mb-20"> {{ $store->name }}</h3>
                    <div class="details d-flex flex-wrap flex-column flex-sm-row gap-40px">
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/zone.png') }}" width="36" height="36"
                                 class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Business_zone') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{$store?->zone?->name}}</span>
                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/job-type.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Business_Plan') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{ ucwords($store->store_business_model) }}</span>
                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/rental/det-icon.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Approx. Pickup Time') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{ $store->delivery_time }}</span>
                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/vehicle-type.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.VAT / TAX') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{ $store->tax }} %</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{ translate('messages.Provider_Information') }}
                    </h5>
                    <p class="fs-12">
                        {{ translate('messages.Here you can see all the information that provider submit during registration') }}
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.General_Information') }}
                                </h5>
                                <div class="div">
                                    @if ($language)
                                        <ul class="nav nav-tabs mb-4">
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
                                    @if ($language)
                                        <div class="lang_form text--title" id="default-form">
                                            <div class="fs-12 opacity-lg description-text">
                                                <ul class="address-info address-info-2 p-0 text-dark">
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Vendor Name') }}</span>
                                                        <span>: {{$store?->getRawOriginal('name')}}</span>
                                                    </li>
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Business Address') }}</span>
                                                        <div>
                                                            <div class="short-description">
                                                                <span>: {{ Str::limit($store->getRawOriginal('address'), 500) }} </span>
                                                            </div>
                                                            <div class="full-description display-none" >
                                                                <span>: {{ $store->getRawOriginal('address') }} </span>
                                                            </div>
                                                            <a href="#" class="text--info font-medium see-more display-none" >
                                                                {{ translate('See more') }}
                                                            </a>
                                                        </div>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                            @foreach ($language as $lang)
                                                    <?php
                                                    $translate = [];
                                                    if (isset($store['translations']) && count($store['translations'])) {
                                                        foreach ($store['translations'] as $t) {
                                                            if ($t->locale == $lang && $t->key == "name") {
                                                                $translate[$lang]['name'] = $t->value;
                                                            }
                                                            if ($t->locale == $lang && $t->key == "address") {
                                                                $translate[$lang]['address'] = $t->value;
                                                            }
                                                        }
                                                    }
                                                    ?>

                                                <div class="lang_form d-none text--title" id="{{ $lang }}-form">
                                                    <div class="fs-12 opacity-lg description-text">
                                                        <ul class="address-info address-info-2 p-0 text-dark">
                                                            <li class="d-flex align-items-start">
                                                                <span class="label min-w-sm-auto">{{ translate('messages.Vendor Name') }}</span>
                                                                <span>: {{ $translate[$lang]['name'] ?? '' }}</span>
                                                            </li>
                                                            <li class="d-flex align-items-start">
                                                                <span class="label min-w-sm-auto">{{ translate('messages.Business Address') }}</span>
                                                                <div>
                                                                    <div class="short-description">
                                                                        <span>: {{ isset($translate[$lang]['address']) ? Str::limit($translate[$lang]['address'], 500) : '' }}</span>
                                                                    </div>
                                                                    <div class="full-description display-none" >
                                                                        <span>: {{ $translate[$lang]['address'] ?? '' }}</span>
                                                                    </div>
                                                                    <a href="#" class="text--info font-medium see-more pl-1 display-none" >
                                                                        {{ translate('See more') }}
                                                                    </a>
                                                                </div>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif
                                        <div class="d-flex align-items-start">
                                            <button
                                                class="btn order--details-btn-sm btn--varify btn-outline-varify btn--sm font-regular d-flex align-items-center __gap-5px"
                                                data-toggle="modal" data-target="#locationModal"><i
                                                    class="tio-poi"></i>
                                                {{ translate('messages.map_view') }}</button>
                                        </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.Owner_Information') }}
                                </h5>
                                <div class="resturant--info-address">
                                    <ul class="address-info address-info-2 p-0 text-dark">
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.First Name') }}</span>
                                            <span>: {{$store->vendor->f_name}} </span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Last Name') }}</span>
                                            <span>: {{$store->vendor->l_name}}</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Phone') }}</span>
                                            <span>: {{$store->vendor->phone}}</span>
                                        </li>
                                    </ul>

                                </div>

                                <div class="resturant--info-address">
                                    <ul class="address-info address-info-2 p-0 text-dark">
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Email') }}</span>
                                            <span>: {{ $store->vendor->email }}</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Password') }}</span>
                                            <span>: {{ translate('*************') }}</span>
                                        </li>
                                    </ul>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.Pickup_Zone') }}
                                </h5>
                                <div class="d-flex gap-2 gap-sm-3 flex-wrap">
                                    @foreach($store->getPickupZones() as $pickupZone)
                                        <label class="badge badge-soft-dark rounded-20 p-2 m-0 font-medium">
                                            {{ $pickupZone->name ?? 'Unknown Zone' }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="locationModal" tabindex="-1" role="dialog" aria-labelledby="locationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title text-title font-bold" id="locationModalLabel">
                        {{ translate($store->name) }}</h3>
                    <button type="button" class="close fs-24 m-0 p-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex gap-4 mb-20">
                        <div>
                            <span class="text-title font-medium">{{ translate('messages.Business Zone') }}</span>
                            <div class="mt-10px">
                                <button class="btn btn--primary font-medium zone-btn business-zone-btn"
                                        id="businessZoneButton"
                                        data-zone="business">
                                    {{ $store?->zone?->name }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <span class="text-title font-medium">{{ translate('messages.Pickup Zone') }}</span>
                            <div class="d-flex flex-wrap gap-10px mt-10px">
                                @foreach($store->getPickupZones() as $pickupZone)
                                    <button class="btn btn--reset font-medium zone-btn pickup-zone-btn"
                                            id="pickupZoneButton{{$pickupZone->id}}"
                                            data-zone-id="{{$pickupZone->id}}"
                                            data-zone-type="pickup">
                                        {{ $pickupZone->name ?? 'Unknown Zone' }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal_body_map">
                        <div class="location-map" id="location-map">
                            <div id="map" class="initial--25"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="collect-cash" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{translate('messages.collect_cash_from_store')}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{route('admin.transactions.account-transaction.store')}}" method='post'
                          id="add_transaction">
                        @csrf
                        <input type="hidden" name="type" value="store">
                        <input type="hidden"  name="store_id" value="{{ $store->id }}">
                        <div class="form-group">
                            <label class="input-label">{{translate('messages.payment_method')}} <span
                                    class="input-label-secondary text-danger">*</span></label>
                            <input class="form-control" type="text" name="method" id="method" required maxlength="191"
                                   placeholder="{{translate('messages.Ex_:_Card')}}">
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{translate('messages.reference')}}</label>
                            <input class="form-control" type="text" name="ref" id="ref" maxlength="191">
                        </div>
                        <div class="form-group">
                            <label class="input-label">{{translate('messages.amount')}} <span
                                    class="input-label-secondary text-danger">*</span></label>
                            <input class="form-control" type="number" min=".01" step="0.01" name="amount" id="amount"
                                   max="999999999999.99" placeholder="{{translate('messages.Ex_:_1000')}}">
                        </div>
                        <div class="btn--container justify-content-end">
                            <button type="submit" id="submit_new_customer"
                                    class="btn btn--primary">{{translate('submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="d-none" id="data-set"
        data-translate-are-you-sure="{{ translate('Are_you_sure?') }}"
        data-translate-no="{{ translate('no') }}"
        data-translate-yes="{{ translate('yes') }}"
        data-store-transaction-url="{{ route('admin.transactions.account-transaction.store') }}"
        data-translate-transaction-saved="{{ translate('messages.transaction_saved') }}"

    ></div>

    <div id="mapContainer"
    data-business-coordinates='@json($coordinates ?? [])'
    data-business-center='{
        "lat": {{$store->latitude}},
        "lng": {{$store->longitude}}
    }'
    data-marker-icon="{{ asset('public/assets/admin/img/zone-status-on.png') }}"
    data-pickup-zones='@json($store->getPickupZones()->map(function($zone) {
        return [
            "id" => $zone->id,
            "coordinates" => json_decode($zone->coordinates[0]->toJson(), true)["coordinates"]
        ];
    }))'
></div>
@endsection

@push('script_2')
    <!-- Page level plugins -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{\App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value}}&callback=initMap&v=3.45.8"></script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-overview.js')}}"></script>

@endpush
