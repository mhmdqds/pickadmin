@extends('layouts.admin.app')
@section('title', translate('messages.Admin_Earning_Report'))

@section('admin_earning_report')
    active
@endsection

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/earning-report.css') }}">
@endpush

@section('admin_earning_report')
    active
@endsection

@section('content')
    @php
        $activeTab = request()->tab ?: 'all';
        $parcelOrderTypes = $activeTab == 'parcel' ? ['order_types' => ['parcel']] : [];
        $reportOverviewTitle = match ($activeTab) {
            'parcel' => 'Comprehensive Financial Overview and Analytics for Parcel',
            'rental' => 'Comprehensive Financial Overview and Analytics for Rental',
            'ride-share' => 'Comprehensive Financial Overview and Analytics for Rides',
            default => 'Comprehensive Financial Overview and Analytics for Orders',
        };

        match ($activeTab) {
            'parcel' => $moduleTypes = ['parcel'],
            'rental' => $moduleTypes = ['rental'],
            'ride-share' => $moduleTypes = ['ride-share'],
            default => $moduleTypes = ['grocery', 'food', 'pharmacy', 'ecommerce']
        };
        $resetReportUrl = route('admin.transactions.report.admin-earning-report', array_merge(
            ['tab' => $activeTab],
            $parcelOrderTypes
        ));
        $formActionUrl = $resetReportUrl;
    @endphp



    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-0">
            <div>
                <h1 class="page-header-title text-capitalize">
                    {{translate('messages.Admin_Earning_Report') }}
                </h1>
                <p>
                    {{ $reportOverviewTitle }}
                </p>
            </div>
        </div>
        <!-- End Page Header -->

        @include('admin-views.report.partials._report_module_tabs')



        @if (request()->tab === 'rental' && addon_published_status('Rental'))
            @include('rental::admin.report.earning-report.content')
        @else
        <div class="card card-body mb-20">
            <h3 class="mb-20">{{ translate('messages.Filter_Data') }}</h3>
            <form method="GET">
                <input type="hidden" name="tab" value="{{ request()->tab }}">
                <div class="__bg-F8F9FC-card">
                    <div class="row g-3 date-filter-wrapper">
                        <div class="col-lg-3 col-sm-6">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Module') }}
                            </label>
                             <select name="module_id" id="module_id" class="form-control js-select2-custom"
                                title="{{ translate('messages.select_modules') }}">
                                <option value="" {{ !request('module_id') ? 'selected' : '' }}>
                                    {{ translate('messages.all_modules') }}</option>
                                @foreach (\App\Models\Module::whereIn('module_type', $moduleTypes)->get(['id', 'module_name']) as $module)
                                    <option value="{{ $module->id }}"
                                        {{ request('module_id') == $module->id ? 'selected' : '' }}>
                                        {{ $module['module_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Date_Range') }}
                            </label>
                            <select name="filter" id="filter" class="form-control custom-select date-type-select">
                                <option value="all" selected>{{ translate('messages.All_Time') }}</option>
                                <option {{ request()->filter == 'this_week' ? 'selected' : '' }} value="this_week">{{
                                    translate('messages.This_Week') }}</option>
                                <option {{ request()->filter == 'this_month' ? 'selected' : '' }} value="this_month">
                                    {{ translate('messages.This_Month') }}</option>
                                <option {{ request()->filter == 'this_year' ? 'selected' : '' }} value="this_year">
                                    {{ translate('messages.This_Year') }}</option>
                                <option {{ request()->filter == 'previous_year' ? 'selected' : '' }} value="previous_year">
                                    {{ translate('messages.Previous_Year') }}</option>
                                <option {{ request()->filter == 'custom' ? 'selected' : '' }} value="custom">
                                    {{ translate('messages.Custom_Range') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-sm-6 custom-date-div d--none">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Start_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="start_date" name="from" value="{{ request()->from }}"
                                class="form-control">
                        </div>
                        <div class="col-lg-3 col-sm-6 custom-date-div d--none">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.End_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" id="end_date" name="to" value="{{ request()->to }}"
                                class="form-control">
                        </div>
                    </div>
                </div>

                <div class="btn--container mt-4 justify-content-end">
                    <button id="resetbtn" type="reset" data-url="{{ $resetReportUrl }}"
                        class="btn btn--reset {{ request()->has('filter') ? 'redirect-url' : ''}} ">{{ translate('messages.reset') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('messages.filter') }}</button>
                </div>
            </form>
        </div>

        <div class="card card-body mb-20">
            <div class="mb-3">
                <h3 class="mb-1">{{ translate('messages.Earnings_Summary') }}</h3>
                <p class="fs-12 mb-0">{{ translate('messages.Breakdown of Revenue Sources and Performance') }}</p>
            </div>

            <div id="admin_earning_symmary"> </div>


            <h4 class="mb-3">{{ translate('messages.Earnings_Breakdown') }}</h4>
            <div id="admin_earning_breakdown"> </div>

            <h4 class="mb-3">{{ translate('messages.Expenses_Breakdown') }}</h4>
            <div id="admin_expense_breakdown"></div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings Trend') }}</h3>
                    </div>
                    <div class="card-body px-3 px-sm-4 pt-2 pb-3">
                        <div class="report-chart-frame">
                            <div class="report-chart-y-axis">{{ translate('messages.Earning_Amount') }}</div>
                            <div class="report-chart-body">
                                <div id="earning-trend-chart" class="w-100"></div>
                                <div class="report-chart-x-axis">{{ translate('messages.Time_Period') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings vs Expenses') }}</h3>
                    </div>
                    <div class="card-body px-3 px-sm-4 pt-2 pb-3">
                        <div class="report-chart-frame">
                            <div class="report-chart-y-axis">{{ translate('messages.Amount') }}</div>
                            <div class="report-chart-body">
                                <div id="monthly-earning-expense-graph" class="w-100"></div>
                                <div class="report-chart-x-axis">{{ translate('messages.Time_Period') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings by Source') }}</h3>
                    </div>
                    <div class="card-body px-3 px-sm-4 pt-2 pb-3">
                        <div id="earnings-pie-chart" class="chartjs-custom mx-auto" style="max-width:400px;"></div>
                    </div>
                </div>

            </div>
            <div class="col-lg-6">
                <div id="admin_top_earning_stores"> </div>
            </div>

            <div class="col-lg-{{ request()?->tab !== 'parcel' ? '6' : '12' }}">
                <div id="admin_zone_wise_earnings"> </div>
            </div>

            <div class="col-12">
                <div class="card card-body recent-transactions-card">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap border-0 recent-transaction-header">
                        <div>
                            <h3 class="mb-20">{{ translate('messages.Recent_Transactions') }}</h3>
                            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                                <!-- Nav -->
                                <ul class="nav nav-tabs border-0 nav--tabs nav--pills transaction-nav-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active transaction-tab" data-type="order" href="#"
                                            aria-disabled="true">{{ translate('messages.Earnings') }}</a>
                                    </li>
                                    @if (request()->tab != 'parcel')
                                    <li class="nav-item">
                                        <a class="nav-link transaction-tab" data-type="subscription" href="#"
                                            aria-disabled="true">{{ translate('messages.Subscription_Earnings') }}</a>
                                    </li>

                                    @endif
                                    <li class="nav-item">
                                        <a class="nav-link transaction-tab" data-type="expense" href="#"
                                            aria-disabled="true">{{ translate('messages.Expenses') }}</a>
                                    </li>
                                </ul>
                                <!-- End Nav -->
                            </div>
                        </div>
                        <div class="search--button-wrapper justify-content-end">
                            <form id="transaction-search-form" class="">
                                <!-- Search -->
                                <div class="input--group input-group input-group-merge input-group-flush">
                                    <input id="datatableSearch_" type="search" name="report_search" class="form-control" value=""
                                        placeholder="{{ translate('Search By Order ID') }}" aria-label="Search"
                                        required>
                                    <button type="submit" class="btn btn--secondary">
                                        <i class="tio-search"></i>
                                    </button>
                                </div>
                                <!-- End Search -->
                            </form>
                            <div
                                class="d-flex flex-wrap gpa-3 justify-content-sm-end align-items-sm-center ml-0 mr-0 flex-grow-0">
                                <!-- Unfold -->
                                <div class="hs-unfold ml-3">
                                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn font--sm"
                                        href="javascript:;"
                                        data-hs-unfold-options='{
                                                "target": "#usersExportDropdown",
                                                "type": "css-animation",
                                                "boundary": "viewport"
                                            }'
                                        data-hs-unfold-target="#usersExportDropdown" data-hs-unfold-invoker="">
                                        <i class="tio-download-to mr-1"></i> {{ translate('export') }}
                                    </a>

                                    <div id="usersExportDropdown"
                                        class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                        <span class="dropdown-header">{{ translate('download_options') }}</span>
                                        <a id="export-excel" class="dropdown-item" href="javascript:;">
                                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                                src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                                alt="Image Description">
                                            {{ translate('messages.excel') }}
                                        </a>
                                        <a id="export-csv" class="dropdown-item" href="javascript:;">
                                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                                src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                                alt="Image Description">
                                            .{{ translate('messages.csv') }}
                                        </a>
                                    </div>
                                </div>
                                <!-- End Unfold -->
                            </div>
                        </div>
                        <!-- End Row -->
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom mt-4 z-index-2" id="transaction_table_container">

                    </div>
                    <!-- End Table -->
                    <!-- End Footer -->

                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@if (!(request()->tab === 'rental' && addon_published_status('Rental')))
@push('script')
    <script src="{{ asset('public/assets/admin/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/view-pages/apex-charts.js') }}"></script>
    <script>
        let earningTrendChart;
    </script>
@endpush

@push('script_2')

    <script>
        "use strict";

        let earningsChart = null;
        const chartAxisLabels = {
            timePeriod: "{{ translate('messages.Time_Period') }}",
            earningAmount: "{{ translate('messages.Earning_Amount') }}",
            amount: "{{ translate('messages.Amount') }}"
        };
        const reportCurrencySymbol = @json(\App\CentralLogics\Helpers::currency_symbol());
        const reportCurrencyPosition = @json(\App\CentralLogics\Helpers::get_business_settings('currency_symbol_position') ?? 'left');
        const reportCurrencyDecimals = {{ (int) config('round_up_to_digit') }};

        function formatGraphValue(value) {
            const absValue = Math.abs(Number(value) || 0);

            if (absValue >= 1000000000) {
                return (value / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
            }

            if (absValue >= 1000000) {
                return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            }

            if (absValue >= 1000) {
                return (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
            }

            return Math.round(value).toString();
        }

        function formatReportCurrency(value) {
            const formattedNumber = Number(value || 0).toLocaleString(undefined, {
                minimumFractionDigits: reportCurrencyDecimals,
                maximumFractionDigits: reportCurrencyDecimals
            });

            return reportCurrencyPosition === 'right'
                ? formattedNumber + ' ' + reportCurrencySymbol
                : reportCurrencySymbol + ' ' + formattedNumber;
        }

        function loadEarningsPieChart(earnings = {}) {
            const labels = [];
            const data = [];
            const colors = [];

            if (!earnings.is_parcel) {
                labels.push('{{ translate('Order Commission') }}');
                data.push(earnings.order_commission || 0);
                colors.push('#04BB7B');
            }

            if (!earnings.is_parcel) {
                labels.push('{{ translate('Subscription Packages') }}');
                data.push(earnings.subscription_earning || 0);
                colors.push('#8B5CF6');
            }

            labels.push('{{ translate('Additional Fees') }}');
            data.push(earnings.additional_charge || 0);
            colors.push('#EC4899');

            labels.push('{{ translate('Delivery Fee Commission') }}');
            data.push(earnings.delivery_fee_comission || 0);
            colors.push('#F59E0B');

            const options = {
                chart: {
                    type: 'donut',
                    height: 350
                },
                series: data,
                labels: labels,
                colors: colors,
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(0) + '%';
                    }
                },
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: function (val, opts) {
                            const label = opts?.w?.globals?.labels?.[opts.seriesIndex] || '';
                            return label + ": " + formatReportCurrency(val);
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    fontSize: '12px',
                                    label: 'Total Earning',
                                    formatter: function (w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return formatReportCurrency(total);
                                    }
                                }
                            }
                        }
                    }
                }
            };

            const chartEl = document.querySelector("#earnings-pie-chart");

            if (chartEl) {

                if (earningsChart) {
                    earningsChart.updateOptions({ labels, colors });
                    earningsChart.updateSeries(data);
                } else {
                    earningsChart = new ApexCharts(chartEl, options);
                    earningsChart.render();
                }
            }
        }

        $(document).ready(function () {

            function toggleCustomDate(wrapper) {
                let selectValue = wrapper.find('.date-type-select').val();

                if (selectValue === 'custom') {
                    wrapper.find('.custom-date-div').slideDown(200);
                } else {
                    wrapper.find('.custom-date-div').slideUp(200);
                }
            }

            $(document).on('change', '.date-type-select', function () {
                let wrapper = $(this).closest('.date-filter-wrapper');
                toggleCustomDate(wrapper);
            });

            $('.date-filter-wrapper').each(function () {
                toggleCustomDate($(this));
            });

            $('#start_date').on('change', function () {
                $('#end_date').attr('min', $(this).val());
            });

            $('#end_date').on('change', function () {
                $('#start_date').attr('max', $(this).val());
            });

            // Initialize min/max on page load
            let initialStartDate = $('#start_date').val();
            let initialEndDate = $('#end_date').val();
            if (initialStartDate) {
                $('#end_date').attr('min', initialStartDate);
            }
            if (initialEndDate) {
                $('#start_date').attr('max', initialEndDate);
            }
        });


        const parcelOrderTypesValue = @json(request()->tab == 'parcel' ? 'parcel' : '');

        function appendParcelOrderTypes(url) {
            if (!parcelOrderTypesValue) {
                return url;
            }

            return url + (url.includes('?') ? '&' : '?') + 'order_types[]=' + encodeURIComponent(parcelOrderTypesValue);
        }

        fetch_data('admin_earning_symmary', appendParcelOrderTypes('{{ route('admin.transactions.report.admin-earning-summary') }}'));
        fetch_data('admin_earning_breakdown', appendParcelOrderTypes('{{ route('admin.transactions.report.admin-earning-breakdown') }}'));
        fetch_data('admin_expense_breakdown', appendParcelOrderTypes('{{ route('admin.transactions.report.admin-expense-breakdown') }}'));

        fetch_data('admin_zone_wise_earnings', appendParcelOrderTypes('{{ route('admin.transactions.report.admin-zone-wise-earnings') }}'));
        @if (request()->tab != 'parcel')
            fetch_data('admin_top_earning_stores', appendParcelOrderTypes('{{ route('admin.transactions.report.admin-top-earning-stores') }}'));
        @endif
        function fetch_data(id, url) {
            $.ajax({
                url: url,
                type: "get",
                data: {
                    module_id: $('#module_id').val(),
                    filter: $('#filter').val(),
                    from: $('#start_date').val(),
                    to: $('#end_date').val(),
                },
                beforeSend: function () {
                    $('#' + id).empty();
                    $('#loading').show()
                },
                success: function (data) {
                    $("#" + id).append(data.view);

                    if (id === 'admin_earning_breakdown') {
                        loadEarningsPieChart(data.earnings);
                    }
                    $('[data-toggle="tooltip"]').tooltip();
                },
                complete: function () {
                    $('#loading').hide()
                }
            })
        }



        function loadMonthlyEarningCharts() {

            $.ajax({
                url: appendParcelOrderTypes("{{ route('admin.transactions.report.admin-monthly-earnings') }}"),
                type: "GET",
                data: {
                    module_id: $('#module_id').val(),
                    filter: $('#filter').val(),
                    from: $('#start_date').val(),
                    to: $('#end_date').val(),
                },

                success: function (res) {
                    console.log(res);
                    initEarningTrendStatisticsChart(
                        res.categories,
                        res.earning_series
                    );

                    initEarningVsExpensechart(
                        res.categories,
                        res.earning_series,
                        res.expense_series
                    );

                }
            });
        }

        loadMonthlyEarningCharts();

        function buildSinglePointParabola(categories, values) {
            if (!Array.isArray(categories) || !Array.isArray(values) || categories.length !== 1 || values.length !== 1) {
                return { categories, values };
            }

            const selectedLabel = categories[0];
            const selectedDate = new Date(selectedLabel);
            if (Number.isNaN(selectedDate.getTime())) {
                return { categories, values };
            }

            const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const formatLabel = (date) => {
                const day = String(date.getDate()).padStart(2, '0');
                return `${day} ${monthLabels[date.getMonth()]} ${date.getFullYear()}`;
            };

            const previousDate = new Date(selectedDate);
            previousDate.setDate(previousDate.getDate() - 1);

            const nextDate = new Date(selectedDate);
            nextDate.setDate(nextDate.getDate() + 1);

            return {
                categories: [formatLabel(previousDate), selectedLabel, formatLabel(nextDate)],
                values: [0, Number(values[0]) || 0, 0]
            };
        }

        function initEarningTrendStatisticsChart(categories, earnings) {

            const chartElement = document.getElementById('earning-trend-chart');
            if (!chartElement) return;

            if (earningTrendChart) {
                earningTrendChart.destroy();
                earningTrendChart = null;
            }

            const chartData = buildSinglePointParabola(categories, earnings);
            categories = chartData.categories;
            earnings = chartData.values;

            var maxValue = Math.max(...earnings);
            maxValue = maxValue <= 0 ? 1 : Math.ceil(maxValue * 1.1);

            const seriesData = earnings;

            const options = {
                series: [{
                    name: "{{ translate('messages.Earning') }}",
                    data: seriesData
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: false }
                },
                colors: ['#019463'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                markers: {
                    size: 4,
                    strokeWidth: 0,
                    hover: {
                        size: 6
                    }
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: categories,
                },
                yaxis: {
                    min: 0,
                    max: maxValue,
                    tickAmount: 4,
                    labels: {
                        offsetX: -10,
                        formatter: function (val) {
                            return formatGraphValue(val);
                        }
                    }
                },
                grid: {
                    strokeDashArray: 4,
                    padding: {
                        left: 18,
                        right: 12,
                        bottom: 12
                    }
                },
                tooltip: {
                    theme: 'dark',
                    shared: false,
                    x: {
                        show: false
                    },
                    y: {
                        formatter: function (val, opts) {
                            const month = opts.w.globals.categoryLabels[opts.dataPointIndex];
                            return month + ' : ' + formatReportCurrency(val);
                        }
                    }
                }
            };

            earningTrendChart = new ApexCharts(chartElement, options);
            earningTrendChart.render();
        }

        let earningExpenseChart = null;

        function initEarningVsExpensechart(categories, earnings, expenses) {

            if (earningExpenseChart) {
                earningExpenseChart.destroy();
                earningExpenseChart = null;
            }

            let maxValue = Math.max(
                Math.max(...earnings),
                Math.max(...expenses)
            );
            maxValue = Math.ceil(maxValue * 1.1);
            let columnWidth = window.innerWidth <= 768 ? '8px' : '13px';

            let options = {
                series: [
                    { name: "Earning", data: earnings },
                    { name: "Expense", data: expenses }
                ],
                chart: {
                    type: 'bar',
                    height: 380,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: columnWidth,
                        borderRadius: 5,
                        borderRadiusApplication: 'end',
                    }
                },

                colors: ['#059669E5', '#D97706E5'],
                xaxis: {
                    categories: categories,
                },
                yaxis: {
                    min: 0,
                    max: maxValue,
                    tickAmount: 4,
                    labels: {
                        formatter: function (val) {
                            return formatGraphValue(val);
                        }
                    }
                },
                dataLabels: { enabled: false },
                grid: {
                    borderColor: '#e5e7eb',
                    padding: {
                        left: 18,
                        right: 12,
                        bottom: 12
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return formatReportCurrency(val);
                        }
                    }
                }
            };

            earningExpenseChart = new ApexCharts(
                document.querySelector("#monthly-earning-expense-graph"),
                options
            );

            earningExpenseChart.render();
        }



        $('#resetbtn').on('click', function () {
            $('.custom-date-div').hide();
            $('#start_date').removeAttr('max');
            $('#end_date').removeAttr('min');
        })


        $(document).on('click', '.collapse-next-tr', function () {
            const $trigger = $(this);
            const $currentRow = $trigger.closest('tr');
            const $targetRow = $currentRow.next('.collapsing-tr');
            const $icon = $trigger.find('i');

            $targetRow.toggleClass('d-none');

            if ($targetRow.hasClass('d-none')) {
                $icon.removeClass('tio-chevron-up').addClass('tio-chevron-down');
            } else {
                $icon.removeClass('tio-chevron-down').addClass('tio-chevron-up');
            }
        });

        let currentTransactionType = 'order';
        let currentTransactionSearch = '';

        function fetchTransactions(page = 1) {
            let filter = $('#filter').val();
            let from = $('#start_date').val();
            let to = $('#end_date').val();
            let moduleId = $('#module_id').val();

            $.ajax({
                url: appendParcelOrderTypes("{{ route('admin.transactions.report.admin-earning-transactions') }}?page=" + page),
                type: "GET",
                data: {
                    type: currentTransactionType,
                    search: currentTransactionSearch,
                    module_id: moduleId,
                    filter: filter,
                    from: from,
                    to: to
                },
                beforeSend: function () {
                    $('#transaction_table_container').empty();
                    $('#loading').show();
                },
                success: function (data) {
                    $('#transaction_table_container').html(data.view);
                    $('[data-toggle="tooltip"]').tooltip();
                },
                complete: function () {
                    $('#loading').hide();
                }
            });
        }

        $(document).on('click', '.transaction-tab', function (e) {
            e.preventDefault();
            $('.transaction-tab').removeClass('active');
            $(this).addClass('active');
            currentTransactionType = $(this).data('type');
            currentTransactionSearch = '';
            $('#datatableSearch_').val('');

            let placeholder = "{{ translate('messages.Search_by_Transaction_ID') }}";
            if (currentTransactionType === 'subscription') {
                placeholder = "{{ translate('messages.Search_by_Transaction_ID_or_Store_Name') }}";
            } else {
                placeholder = "{{ translate('messages.Search_by_Txn_ID_or_Order_ID') }}";
            }
            $('#datatableSearch_').attr('placeholder', placeholder);

            fetchTransactions();
        });

        $('#transaction-search-form').on('submit', function (e) {
            e.preventDefault();
            currentTransactionSearch = $('#datatableSearch_').val();
            fetchTransactions();
        });

        $('#datatableSearch_').on('input', function () {
            if (this.value === '' && currentTransactionSearch !== '') {
                currentTransactionSearch = '';
                fetchTransactions();
            }
        });

        $('#datatableSearch_').on('search', function () {
            if (this.value === '' && currentTransactionSearch !== '') {
                currentTransactionSearch = '';
                fetchTransactions();
            }
        });

        // initial load
        fetchTransactions();

        // Handle pagination clicks within the transaction table container
        $(document).on('click', '#transaction_table_container .page-area .pagination a', function (e) {
            e.preventDefault();
            let url = new URL($(this).attr('href'), window.location.origin);
            let page = url.searchParams.get('page');
            fetchTransactions(page);
        });

        $(document).on('click', '#export-excel', function () {
            const url = new URL(appendParcelOrderTypes("{{ route('admin.transactions.report.admin-earning-export') }}"), window.location.origin);
            url.searchParams.set('module_id', $('#module_id').val());
            url.searchParams.set('filter', $('#filter').val());
            url.searchParams.set('from', $('#start_date').val());
            url.searchParams.set('to', $('#end_date').val());
            url.searchParams.set('type', currentTransactionType);
            url.searchParams.set('search', $('#datatableSearch_').val());
            url.searchParams.set('export_type', 'excel');
            location.href = url.toString();
        });

        $(document).on('click', '#export-csv', function () {
            const url = new URL(appendParcelOrderTypes("{{ route('admin.transactions.report.admin-earning-export') }}"), window.location.origin);
            url.searchParams.set('module_id', $('#module_id').val());
            url.searchParams.set('filter', $('#filter').val());
            url.searchParams.set('from', $('#start_date').val());
            url.searchParams.set('to', $('#end_date').val());
            url.searchParams.set('type', currentTransactionType);
            url.searchParams.set('search', $('#datatableSearch_').val());
            url.searchParams.set('export_type', 'csv');
            location.href = url.toString();
        });


            </script>
@endpush
@endif
