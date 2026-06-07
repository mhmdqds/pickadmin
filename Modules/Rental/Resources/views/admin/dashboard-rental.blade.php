@extends('layouts.admin.app')

@section('title', translate('messages.Car Rental Module Dashboard'))


@section('dashboard')

show active
@endsection



@section('content')
@php($mod = \App\Models\Module::find(Config::get('module.current_module_id')))
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-center">
                        <img class="onerror-image" data-onerror-image="{{ asset('/public/assets/admin/img/grocery.svg') }}"
                             src="{{$mod->icon_full_url }}" width="38" alt="img">
                        <div class="w-0 flex-grow pl-2">
                            <h1 class="page-header-title text-title mb-0">

                                {{translate($mod->module_name)}} {{translate('messages.Dashboard')}}
                            </h1>
                            <p class="page-header-text text-title fs-12 m-0">{{ translate('messages.Monitor_your') }}
                                <strong class="font-bold"> {{translate($mod->module_name)}} {{ translate('messages.business') }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-auto min--280">
                    <select data-src-url="{{ route('admin.rental.dashboard') }}" name="zone_id" class="form-control js-select2-custom  fetch_data_zone_wise" >
                        <option value="all">{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get(['name','id']) as $zone)
                            <option
                                value="{{$zone['id']}}" {{request()->zone_id == $zone['id']?'selected':''}}>
                                {{$zone['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body pt-0">
                <div class="d-flex flex-wrap align-items-center justify-content-between statistics--title-area">
                    <div class="statistics--title pr-sm-3" id="stat_zone">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="page-header-title text-title fs-18 mb-0">
                                {{ translate('messages.Delivery_Statistics') }}</h3>
                            <label class="badge badge-soft-primary m-0">
                                {{ translate('messages.zone') }} : <span id="zoneName">{{ $zoneName }}</span>
                            </label>
                        </div>
                    </div>
                    <div class="statistics--select">
                        <select class="custom-select border-0 trip_stats_update" name="statistics_type">
                            <option value="all" {{ request()->statistics_type ? '' : 'selected' }}>
                                {{ translate('messages.All_Time') }}
                            </option>
                            <option value="this_year" {{ request()->statistics_type == 'this_year' ? 'selected' : '' }}>{{ translate('messages.this_year') }}</option>
                            <option value="this_month" {{ request()->statistics_type == 'this_month' ? 'selected' : '' }}>{{ translate('messages.this_month') }}</option>
                            <option value="this_week" {{ request()->statistics_type == 'this_week' ? 'selected' : '' }}>{{ translate('messages.this_week') }}</option>
                        </select>
                    </div>
                </div>
                <div id="deliveryStatistics">
                    @include('rental::admin.partials.delivery-statistics')
                </div>
            </div>
        </div>

        <!-- End Stats -->
        <div class="row g-2">
            <div class="col-md-12">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center __gap-12px">
                            <div class="__gross-amount" id="gross_earning">
                                <h6 class="gross-earning">{{ \App\CentralLogics\Helpers::format_currency(collect($total_sell)->sum()) }}</h6>
                                <span>{{ translate('messages.Gross_Earnings') }}</span>
                            </div>
                            <div class="chart--label __chart-label p-0 move-left-100 ml-auto">
                                <span class="indicator chart-bg-2"></span>
                                <span class="info">
                                    {{ translate('Earnings') }} ({{ date('Y') }})
                                </span>
                            </div>
                            <select data-src-url="{{ route('admin.rental.dashboard-stats.commission_overview') }}" id="commission_overview_stats_update"
                                class="custom-select border-0 text-center w-auto ml-auto commission_overview_stats_update"
                                name="commission_overview">
                                <option value="all">
                                    {{ translate('All Time') }}
                                </option>
                                <option value="this_year" {{ request()->commission_overview == 'this_year' ? 'selected' : '' }}>
                                    {{ translate('this_year') }}
                                </option>
                                <option value="this_month" {{ request()->commission_overview == 'this_month' ? 'selected' : '' }}>
                                    {{ translate('this_month') }}
                                </option>
                                <option value="this_week" {{ request()->commission_overview == 'this_week' ? 'selected' : '' }}>
                                    {{ translate('this_week') }}
                                </option>
                            </select>
                        </div>
                        <div id="commission-overview-board">

                            <div id="grow-sale-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Card -->
                <div class="card h-100">
                    <!-- Header -->
                    <div class="card-header border-0">
                        <h5 class="card-header-title">
                            {{ translate('Trips by Trip Type') }}
                        </h5>
                        <select data-src-url="{{ route('admin.rental.dashboard-stats.trip_by_trip_type') }}" id="trip_by_trip_type_stats_update" class="custom-select border-0 text-center w-auto user_overview_stats_update"
                                name="trip_overview">
                            <option value="all">
                                {{ translate('All Time') }}
                            </option>
                            <option value="this_year" {{ request()->trip_overview == 'this_year' ? 'selected' : '' }}>
                                {{ translate('This year') }}
                            </option>
                            <option value="this_month" {{ request()->trip_overview == 'this_month' ? 'selected' : '' }}>
                                {{ translate('This month') }}
                            </option>
                            <option value="this_week" {{ request()->trip_overview == 'this_week' ? 'selected' : '' }}>
                                {{ translate('This week') }}
                            </option>
                        </select>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body" id="trip-overview-board">
                        @include('rental::admin.partials.by-trip-type')
                    </div>
                    <!-- End Body -->
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-customer-view">
                    <div class="card-header border-0 order-header-shadow">
                        <h5 class="card-header-title font-bold d-flex justify-content-between">
                            <span>{{ translate('messages.top_customers') }}</span>
                        </h5>
                        <a href="{{ route('admin.users.customer.list') }}" class="fz-12px font-semibold text-006AE5">{{ translate('view_all') }}</a>
                    </div>
                    <div class="card-body">

                        <div class="top--selling" id="topCustomers">
                            @include('rental::admin.partials.top-customers')
                        </div>

                    </div>
                </div>
                <!-- End Card -->
            </div>
            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card h-100" id="top-provider-view">
                    <div class="card-header border-0 order-header-shadow">
                        <h5 class="card-header-title font-bold d-flex justify-content-between">
                            <span>{{ translate('messages.top_providers') }}</span>
                        </h5>
                        <a href="{{ route('admin.rental.provider.list')}}" class="fz-12px font-semibold text-006AE5">{{ translate('view_all') }}</a>
                    </div>
                    <div class="card-body">

                        <div class="top--selling" id="topProviders">
                            @include('rental::admin.partials.top-providers')
                        </div>

                    </div>
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

    <div class="d-none" id="current_url" data-src-url="{{ url()->current() }}"> </div>
    <div class="d-none" id="current_currency" data-currency="{{ \App\CentralLogics\Helpers::currency_symbol() }}"></div>
@endsection

@push('script')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js.extensions/chartjs-extensions.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chartjs-plugin-datalabels/dist/chartjs-plugin-datalabels.min.js">
    </script>
    <!-- Apex Charts -->
    <script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
    <!-- Apex Charts -->
@endpush

@push('script_2')
    <script>
        "use strict";

        const hourlyCount = {{ $hourlyCount }};
        const distanceWiseCount = {{ $distanceWiseCount }};

        document.addEventListener('DOMContentLoaded', function() {
            initializeDonutChart(hourlyCount, distanceWiseCount);
            const initialTotalSell = [{{ implode(",", array_map(fn($val) => number_format($val, 2, '.', ''), $total_sell)) }}];
            const initialCommission = [{{ implode(",", array_map(fn($val) => number_format($val, 2, '.', ''), $commission)) }}];
            const initialTotalSubs = [{{ implode(",", array_map(fn($val) => number_format($val, 2, '.', ''), $total_subs)) }}];
            const initialLabels = [{!! implode(",", $label) !!}];

            initializeAreaChart(initialTotalSell, initialCommission, initialTotalSubs, initialLabels);
        });

    </script>

<script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/dashboard.js')}}"></script>

@endpush
