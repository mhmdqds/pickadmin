@extends('layouts.vendor.app')

@section('title', translate('messages.dashboard'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">

        @if (auth('vendor')->check())
            <div class="page-header">
                <div class="row align-items-center py-2">
                    <div class="col-sm mb-2 mb-sm-0">
                        <div class="d-flex align-items-center">
                            <img class="onerror-image"
                                src="{{ asset('/public/assets/admin/img/rental/image_car.png') }}" width="38" alt="img">
                            <div class="w-0 flex-grow pl-2">
                                <h1 class="page-header-title text-title mb-0">
                                    {{ translate('messages.Dashboard') }}</h1>
                                <p class="page-header-text text-title fs-12 m-0">{{ translate('messages.Monitor_your') }}
                                    <strong class="font-bold"> {{ translate('messages.business') }}</strong>
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body pt-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-between statistics--title-area">
                        <div class="statistics--title pr-sm-3" id="">
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="page-header-title text-title fs-18 mb-0">
                                    {{ translate('messages.Delivery_Statistics') }}</h3>

                            </div>
                        </div>
                        <div class="statistics--select">
                            <select class="custom-select border-0 trip_stats_update" name="statistics_type" data-route="{{ route('vendor.deliveryStatistics') }}">
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
                        @include('rental::provider.dashboard._delivery-statistics')
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
                                    <h6 class="gross-earning">
                                        {{ \App\CentralLogics\Helpers::format_currency(collect($commission)->sum()) }}</h6>
                                    <span>{{ translate('messages.Gross_Earnings') }}</span>
                                </div>
                                <div class="chart--label __chart-label p-0 move-left-100 ml-auto">
                                    <span class="indicator chart-bg-2"></span>
                                    <span class="info">
                                        {{ translate('Earnings') }} ({{ date('Y') }})
                                    </span>
                                </div>
                                <select
                                        id="commission_overview_stats_update"
                                    class="custom-select border-0 text-center w-auto ml-auto commission_overview_stats_update"
                                    data-route="{{ route('vendor.commissionOverview') }}"
                                    name="commission_overview">
                                    <option value="all">
                                        {{ translate('All Time') }}
                                    </option>
                                    <option value="this_year"
                                        {{ request()->commission_overview == 'this_year' ? 'selected' : '' }}>
                                        {{ translate('this_year') }}
                                    </option>
                                    <option value="this_month"
                                        {{ request()->commission_overview == 'this_month' ? 'selected' : '' }}>
                                        {{ translate('this_month') }}
                                    </option>
                                    <option value="this_week"
                                        {{ request()->commission_overview == 'this_week' ? 'selected' : '' }}>
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
            </div>
        @else
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm mb-2 mb-sm-0">
                        <h1 class="page-header-title">{{ translate('messages.welcome') }},
                            {{ auth('vendor_employee')->user()->f_name }}.</h1>
                        <p class="page-header-text">{{ translate('messages.employee_welcome_message') }}</p>
                    </div>
                </div>
            </div>
            <!-- End Page Header -->
        @endif
        <div id="currency" data-currency="{{ \App\CentralLogics\Helpers::currency_symbol() }}"></div>
        <div class="d-none" id="current_url" data-src-url="{{ url()->current() }} "> </div>

    </div>
@endsection

@push('script')
    <script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
@endpush

@push('script_2')
    <script>
        "use strict";

        document.addEventListener('DOMContentLoaded', function() {
            const initialCommission = [{{ implode(",", array_map(fn($val) => number_format($val, 2, '.', ''), $commission)) }}];
            const initialLabels = [{!! implode(",", $label) !!}];

            initializeAreaChart(initialCommission, initialLabels);
        });
    </script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/dashboard.js') }}"></script>
@endpush
