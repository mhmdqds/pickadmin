@php
    $show_store_select = $show_store_select ?? true;
    $store_id = $store_id ?? 'all';
    $module_id = $module_id ?? 'all';
    $stores = $stores ?? [];
    $show_earning_vs_expense = $show_earning_vs_expense ?? false;
@endphp

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/earning-report.css') }}">
@endpush

<div class="card card-body mb-20">
    <h3 class="mb-20">{{ translate('messages.Filter_Data') }}</h3>
    <form action="">
        <div class="__bg-F8F9FC-card">
            <div class="row g-3 date-filter-wrapper">
                @if($show_store_select)
                <div class="col-lg-4 col-sm-6">
                    <label for="" class="input-label text-capitalize">
                        {{ translate('messages.Module') }}
                    </label>
                    <select name="module_id" id="module_id" class="form-control js-select2-custom"
                        title="{{ translate('messages.select_modules') }}">
                        <option value="all" {{ $module_id == 'all' ? 'selected' : '' }}>
                            {{ translate('messages.all_modules') }}
                        </option>
                        @foreach (\App\Models\Module::whereIn('module_type', ['grocery', 'food', 'pharmacy', 'ecommerce'])->get(['id', 'module_name']) as $module)
                            <option value="{{ $module->id }}" {{ (string) $module_id === (string) $module->id ? 'selected' : '' }}>
                                {{ $module['module_name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <label for="" class="input-label text-capitalize">
                        {{ translate('messages.Select_Store') }}
                    </label>
                    <select name="store_id" id="store_id" data-placeholder="{{ translate('messages.select_store') }}" class="js-data-example-ajax form-control">
                        @if (isset($store))
                            <option value="{{ $store->id }}" selected>{{ $store->name }}</option>
                        @else
                            <option value="all" selected>{{ translate('messages.all_stores') }}</option>
                        @endif
                    </select>

                </div>
                @else
                    <input type="hidden" class="select2-hidden-accessible" name="store_id" id="store_id" value="{{ $store_id }}">
                @endif
                <div class="{{ $show_store_select ? 'col-lg-4 col-sm-6' : 'col-lg-12' }}">
                    <label for="" class="input-label text-capitalize">
                        {{ translate('messages.Date_Range') }}
                    </label>
                    <select name="filter" id="filter" class="form-control custom-select date-type-select">
                        <option value="all_time" {{ request('filter') == 'all_time' ? 'selected' : '' }}>{{ translate('messages.All_Time') }}</option>
                        <option value="this_week" {{ request('filter') == 'this_week' ? 'selected' : '' }}>{{ translate('messages.This_Week') }}</option>
                        <option value="this_month" {{ request('filter') == 'this_month' ? 'selected' : '' }}>{{ translate('messages.This_Month') }}</option>
                        <option value="this_year" {{ request('filter') == 'this_year' ? 'selected' : '' }}>{{ translate('messages.This_Year') }}</option>
                        <option value="previous_year" {{ request('filter') == 'previous_year' ? 'selected' : '' }}>{{ translate('messages.Previous_Year') }}</option>
                        <option value="custom" {{ request('filter') == 'custom' ? 'selected' : '' }}>{{ translate('messages.Custom_Range') }}</option>
                    </select>
                </div>
                <div class="col-lg-6 custom-date-div d--none">
                    <label for="" class="input-label text-capitalize">
                        {{ translate('messages.Start_Date') }} <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="from" id="from" value="{{ request('from') }}" class="form-control">
                </div>
                <div class="col-lg-6 custom-date-div d--none">
                    <label for="" class="input-label text-capitalize">
                        {{ translate('messages.End_Date') }} <span class="text-danger">*</span>
                    </label>
                    <input type="date" name="to" id="to" value="{{ request('to') }}" class="form-control">
                </div>
            </div>
        </div>
            <div class="btn--container mt-4 justify-content-end">
            <button id="resetbtn" type="reset"
            data-url="{{ $reset_url }}"
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
    <div id="store_earning_summary"></div>

    <h4 class="mb-3">{{ translate('messages.Earnings_Breakdown') }}</h4>
    <div id="store_earning_breakdown"></div>

    <h4 class="mb-3">{{ translate('messages.Expenses_Breakdown') }}</h4>
    <div id="store_expense_breakdown"></div>
</div>

<div class="card h-100 mb-20">
    <div class="card-header border-0 d-block pb-0">
        <h3 class="mb-1 text-title">{{ translate('messages.Store Earnings Trend') }}</h3>
        <p class="mb-1">{{ translate('messages.Revenue performance over time') }}</p>
    </div>
    <div class="card-body px-3 px-sm-4 pt-2 pb-3">
        <div class="report-chart-frame">
            <div class="report-chart-y-axis">{{ translate('messages.Earning_Amount') }}</div>
            <div class="report-chart-body">
                <div id="earning-trend-chart"></div>
                <div class="report-chart-x-axis">{{ translate('messages.Time_Period') }}</div>
            </div>
        </div>
    </div>
</div>

@if($show_earning_vs_expense)
<div class="row g-3 mb-20">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header border-0 d-block pb-0">
                <h3 class="mb-1 text-title">{{ translate('messages.Earning_vs_Expense') }}</h3>
                <p class="mb-1">{{ translate('messages.Monthly_earning_and_expense_comparison') }}</p>
            </div>
            <div class="card-body px-3 px-sm-4 pt-2 pb-3">
                <div class="report-chart-frame">
                    <div class="report-chart-y-axis">{{ translate('messages.Amount') }}</div>
                    <div class="report-chart-body">
                        <div id="monthly-earning-expense-graph"></div>
                        <div class="report-chart-x-axis">{{ translate('messages.Time_Period') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div id="top_selling_food_container" class="h-100"></div>
    </div>
</div>
@endif


<div class="card card-body recent-transactions-card">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap border-0 recent-transaction-header">
        <div>
            <h3 class="mb-20">{{ translate('messages.Recent_Transactions') }}</h3>
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <!-- Nav -->
                <ul class="nav nav-tabs border-0 nav--tabs nav--pills transaction-nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link active transaction-tab" data-type="order" href="#" aria-disabled="true">{{ translate('messages.Earnings') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link transaction-tab" data-type="expense" href="#" aria-disabled="true">{{ translate('messages.Expenses') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link transaction-tab" data-type="subscription" href="#" aria-disabled="true">{{ translate('messages.Subscription') }}</a>
                    </li>
                </ul>
                <!-- End Nav -->
            </div>
        </div>
        <div class="search--button-wrapper justify-content-end">
            <form id="store-transaction-search-form" class="">
                <!-- Search -->
                <div class="input--group input-group input-group-merge input-group-flush">
                    <input id="datatableSearch_" type="search" name="report_search" class="form-control"
                        value=""
                        placeholder="{{ translate('Search By Order ID') }}" aria-label="Search" required>
                    <button type="submit" class="btn btn--secondary">
                        <i class="tio-search"></i>
                    </button>
                </div>
                <!-- End Search -->
            </form>
            <div class="d-flex flex-wrap gpa-3 justify-content-sm-end align-items-sm-center ml-0 mr-0 flex-grow-0">
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
                        <a id="export-excel" class="dropdown-item"
                            href="javascript:;">
                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                alt="Image Description">
                            {{ translate('messages.excel') }}
                        </a>
                        <a id="export-csv" class="dropdown-item"
                            href="javascript:;">
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
</div>


@push('script_2')
    <script src="{{ asset('public/assets/admin/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/view-pages/apex-charts.js') }}"></script>
    <script>
        let earningTrendChart;
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

        function initEarningTrendStatisticsChart(categories, seriesData) {

            const chartElement = document.getElementById('earning-trend-chart');
            if (!chartElement) return;

            if (earningTrendChart) {
                earningTrendChart.destroy();
                earningTrendChart = null;
            }

            const chartData = buildSinglePointParabola(categories, seriesData);
            categories = chartData.categories;
            seriesData = chartData.values;

            const options = {
                series: [{
                    name: '{{ translate('messages.Total_Earnings') }}',
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
                    tickAmount: 4,
                    labels: {
                        offsetX: -10,
                        formatter: function(val) {
                            return formatGraphValue(val);
                        }
                    }
                },
                grid: {
                    strokeDashArray: 4
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

        let earningVsExpenseChart;

        function initEarningVsExpensechart(categories, earning, expense) {

            const chartElement = document.querySelector("#monthly-earning-expense-graph");
            if (!chartElement) return;

            if (earningVsExpenseChart) {
                earningVsExpenseChart.destroy();
                earningVsExpenseChart = null;
            }

            let columnWidth = window.innerWidth <= 768 ? '8px' : '13px';

            let options = {
                series: [
                    { name: "{{ translate('messages.Earning') }}", data: earning },
                    { name: "{{ translate('messages.Expense') }}", data: expense }
                ],
                chart: {
                    type: 'bar',
                    height: 350,
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
                colors: ['#059669E5','#D97706E5'],
                xaxis: {
                    categories: categories
                },
                yaxis: {
                    min: 0,
                    tickAmount: 4,
                    labels: {
                        formatter: function(val){
                            return formatGraphValue(val);
                        }
                    }
                },
                dataLabels: { enabled: false },
                grid: { borderColor:'#e5e7eb' },
                tooltip: {
                    y: {
                        formatter: function(val){
                            return formatReportCurrency(val);
                        }
                    }
                }
            };

            earningVsExpenseChart = new ApexCharts(chartElement, options);
            earningVsExpenseChart.render();
        }

        function loadEarningTrendChart() {
            $.ajax({
                url: '{{ $trend_url }}',
                type: 'get',
                data: {
                    store_id: $('#store_id').val(),
                    filter: $('#filter').val(),
                    from: $('#from').val(),
                    to: $('#to').val(),
                },
                beforeSend: function() {
                    $('#loading').show()
                },
                success: function(data) {
                    initEarningTrendStatisticsChart(data.categories, data.earning_series);
                    initEarningVsExpensechart(data.categories, data.earning_series, data.expense_series);
                },
                complete: function() {
                    $('#loading').hide()
                }
            });
        }

        $(document).ready(function () {

            fetch_all_data();

            function fetch_all_data() {
                fetch_data('store_earning_summary', '{{ $summary_url }}');
                fetch_data('store_earning_breakdown', '{{ $breakdown_url }}');
                fetch_data('store_expense_breakdown', '{{ $expense_url }}');
                loadEarningTrendChart();
                @if(isset($top_selling_foods_url))
                    fetch_data('top_selling_food_container', '{{ $top_selling_foods_url }}');
                @endif
                fetchTransactions();
            }

            function fetch_data(id, url) {
                $.ajax({
                    url: url,
                    type: "get",
                    data: {
                        store_id: $('#store_id').val(),
                        filter: $('#filter').val(),
                        from: $('#from').val(),
                        to: $('#to').val(),
                    },
                    beforeSend: function() {
                        $('#'+id).empty();
                        $('#loading').show()
                    },
                    success: function(data) {
                        $("#"+id).append(data.view);
                        $("#"+id).find('[data-toggle="tooltip"]').tooltip();
                    },
                    complete: function() {
                        $('#loading').hide()
                    }
                })
            }

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

            $('#from').on('change', function () {
                $('#to').attr('min', $(this).val());
            });

            $('#to').on('change', function () {
                $('#from').attr('max', $(this).val());
            });

            // Initialize min/max on page load
            let initialStartDate = $('#from').val();
            let initialEndDate = $('#to').val();
            if (initialStartDate) {
                $('#to').attr('min', initialStartDate);
            }
            if (initialEndDate) {
                $('#from').attr('max', initialEndDate);
            }

            var currentTransactionType = 'order';
            var currentTransactionSearch = '';

            function fetchTransactions(page = 1) {
                let filter = $('#filter').val();
                let from = $('#from').val();
                let to = $('#to').val();
                let store_id = $('#store_id').val();
                let url = "{{ $transactions_url ?? route('admin.transactions.report.store-earning-transactions') }}";

                $.ajax({
                    url: url,
                    type: "GET",
                    data: {
                        page: page,
                        type: currentTransactionType,
                        search: currentTransactionSearch,
                        filter: filter,
                        from: from,
                        to: to,
                        store_id: store_id
                    },
                    beforeSend: function() {
                        $('#transaction_table_container').empty();
                        $('#loading').show();
                    },
                    success: function(data) {
                        $('#transaction_table_container').html(data.view);
                        $('#transaction_table_container').find('[data-toggle="tooltip"]').tooltip();
                    },
                    complete: function() {
                        $('#loading').hide();
                    }
                });
            }

            $(document).on('click', '.transaction-tab', function(e) {
                e.preventDefault();
                $('.transaction-tab').removeClass('active');
                $(this).addClass('active');
                currentTransactionType = $(this).data('type');
                currentTransactionSearch = '';
                $('#datatableSearch_').val('');

                let placeholder = "{{ translate('Search By Order ID') }}";
                if (currentTransactionType === 'subscription') {
                    placeholder = "{{ translate('Search By Txn ID or Store Name') }}";
                } else{
                    placeholder = "{{ translate('Search By Order ID') }}";
                }
                $('#datatableSearch_').attr('placeholder', placeholder);

                fetchTransactions();
            });

            $('#store-transaction-search-form').on('submit', function(e) {
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

            $(document).on('click', '#transaction_table_container .page-area .pagination a', function (e) {
                e.preventDefault();
                let url = new URL($(this).attr('href'), window.location.origin);
                let page = url.searchParams.get('page');
                fetchTransactions(page);
            });

            $(document).on('click', '#export-excel', function() {
                let filter = $('#filter').val();
                let from = $('#from').val();
                let to = $('#to').val();
                let store_id = $('#store_id').val();
                let search = $('#datatableSearch_').val();
                let url = "{{ $transactions_export_url ?? route('admin.transactions.report.store-earning-export') }}";
                url += "?filter=" + filter + "&from=" + from + "&to=" + to + "&store_id=" + store_id + "&type=" + currentTransactionType + "&search=" + search + "&export_type=excel";
                location.href = url;
            });

            $(document).on('click', '#export-csv', function() {
                let filter = $('#filter').val();
                let from = $('#from').val();
                let to = $('#to').val();
                let store_id = $('#store_id').val();
                let search = $('#datatableSearch_').val();
                let url = "{{ $transactions_export_url ?? route('admin.transactions.report.store-earning-export') }}";
                url += "?filter=" + filter + "&from=" + from + "&to=" + to + "&store_id=" + store_id + "&type=" + currentTransactionType + "&search=" + search + "&export_type=csv";
                location.href = url;
            });

        });

        $('#resetbtn').on('click', function() {
           $('.custom-date-div').hide();
           $('#from').removeAttr('max');
           $('#to').removeAttr('min');
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


        $(document).on('ready', function() {
            const getModuleId = () => {
                const moduleId = $('#module_id').val();
                return moduleId && moduleId !== 'all' ? moduleId : '';
            };

            const initStoreSelect = function () {
                const $storeSelect = $('#store_id');

                if ($storeSelect.hasClass('select2-hidden-accessible')) {
                    $storeSelect.select2('destroy');
                }

                $storeSelect.select2({
                    ajax: {
                        url: '{{ route('admin.store.get-stores') }}',
                        data: function(params) {
                            return {
                                q: params.term,
                                module_id: getModuleId(),
                                page: params.page
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data
                            };
                        },
                        transport: function(params, success, failure) {
                            let $request = $.ajax(params);

                            $request.then(success);
                            $request.fail(failure);

                            return $request;
                        }
                    }
                });
            };

            initStoreSelect();

            $(document).on('change', '#module_id', function () {
                const $storeSelect = $('#store_id');
                $storeSelect.val(null).trigger('change');
                $storeSelect.empty().append(new Option("{{ translate('messages.all_stores') }}", 'all', true, true));
                initStoreSelect();
            });
        });

    </script>
@endpush
