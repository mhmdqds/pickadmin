@extends('layouts.admin.app')

@section('title', translate('messages.transaction_report'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/report.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.transection_report') }}
                    @if (isset($filter) && $filter != 'all_time')
                        <span class="mb-0 h6 badge badge-soft-success ml-2" id="itemCount">( {{ session('from_date') }} -
                            {{ session('to_date') }} )</span>
                    @endif
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card mb-20">
            <div class="card-body">
                <h4 class="">{{ translate('Search Data') }}</h4>
                <form action="{{ route('admin.transactions.rental.report.set-date') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <select name="zone_id" class="form-control js-select2-custom set-filter"
                                data-url="{{ url()->full() }}" data-filter="zone_id" id="zone">
                                <option value="all">{{ translate('messages.All_Zones') }}</option>
                                @foreach (\App\Models\Zone::orderBy('name')->get(['id', 'name']) as $z)
                                    <option value="{{ $z['id'] }}"
                                        {{ isset($zone) && $zone->id == $z['id'] ? 'selected' : '' }}>
                                        {{ $z['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <select name="provider_id"
                                 data-get-provider-url="{{route('admin.store.get-providers')}}"
                                    data-zone-id="{{ isset($zone) ? $zone->id : '' }}"
                                data-module-id="{{ request('module_id') ?? '' }}"

                            data-url="{{ url()->full() }}" data-filter="provider_id"
                                data-placeholder="{{ translate('messages.select_provider') }}"
                                class="js-data-example-ajax form-control set-filter">
                                @if (isset($provider))
                                    <option value="{{ $provider->id }}" selected>{{ $provider->name }}</option>
                                @else
                                    <option value="all" selected>{{ translate('messages.all_providers') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-sm-6 col-md-3">
                            <select class="form-control set-filter" name="filter" data-url="{{ url()->full() }}"
                                data-filter="filter">
                                <option value="all_time" {{ isset($filter) && $filter == 'all_time' ? 'selected' : '' }}>
                                    {{ translate('messages.All Time') }}</option>
                                <option value="this_year" {{ isset($filter) && $filter == 'this_year' ? 'selected' : '' }}>
                                    {{ translate('messages.This Year') }}</option>
                                <option value="previous_year"
                                    {{ isset($filter) && $filter == 'previous_year' ? 'selected' : '' }}>
                                    {{ translate('messages.Previous Year') }}</option>
                                <option value="this_month"
                                    {{ isset($filter) && $filter == 'this_month' ? 'selected' : '' }}>
                                    {{ translate('messages.This Month') }}</option>
                                <option value="this_week" {{ isset($filter) && $filter == 'this_week' ? 'selected' : '' }}>
                                    {{ translate('messages.This Week') }}</option>
                                <option value="custom" {{ isset($filter) && $filter == 'custom' ? 'selected' : '' }}>
                                    {{ translate('messages.Custom') }}</option>
                            </select>
                        </div>
                        @if (isset($filter) && $filter == 'custom')
                            <div class="col-sm-6 col-md-3">

                                <input type="date" name="from" id="from_date" class="form-control"
                                    placeholder="{{ translate('Start Date') }}"
                                    {{ session()->has('from_date') ? 'value=' . session('from_date') : '' }} required>

                            </div>
                            <div class="col-sm-6 col-md-3">

                                <input type="date" name="to" id="to_date" class="form-control"
                                    placeholder="{{ translate('End Date') }}"
                                    {{ session()->has('to_date') ? 'value=' . session('to_date') : '' }} required>

                            </div>
                        @endif
                        <div class="col-sm-6 col-md-3 ml-auto">
                            <button type="submit"
                                class="btn btn-primary btn-block h--45px">{{ translate('Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="mb-20">
            <div class="row g-3">
                <div class="col-lg-12">
                    <div class="row g-2">
                        <div class="col-sm-4">
                            <a class="__card-3 h-100" href="#">
                                <img src="{{ asset('/public/assets/admin/img/report/new/trx1.png') }}" class="icon"
                                    alt="report/new">
                                <h3 class="title text-008958">
                                    {{ \App\CentralLogics\Helpers::number_format_short($totalAmount) }}
                                </h3>
                                <h6 class="subtitle">{{ translate('Completed Transaction') }}</h6>
                                <div class="info-icon" data-toggle="tooltip" data-placement="top"
                                    data-original-title="{{ translate('After a successful trip completion, the full Trip amount goes to this section.') }}">
                                    <img src="{{ asset('/public/assets/admin/img/report/new/info1.png') }}"
                                        alt="report/new">
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-4">
                            <a class="__card-3 h-100" href="#">
                                <img src="{{ asset('/public/assets/admin/img/report/new/trx7.png') }}" class="icon"
                                    alt="report/new">
                                <h3 class="title text-006AE5">
                                    {{ \App\CentralLogics\Helpers::number_format_short($adminEarned) }}
                                </h3>
                                <h6 class="subtitle">{{ translate('Admin Earning') }}</h6>
                                <div class="info-icon" data-toggle="tooltip" data-placement="top"
                                    data-original-title="{{ translate('After a successful trip completion, the admin commission and service fee will be added to the admin\'s earnings.') }}">
                                    <img src="{{ asset('/public/assets/admin/img/report/new/info2.png') }}"
                                        alt="report/new">
                                </div>
                            </a>
                        </div>
                        <div class="col-sm-4">
                            <a class="__card-3 h-100" href="#">
                                <img src="{{ asset('/public/assets/admin/img/report/new/trx6.png') }}" class="icon"
                                    alt="report/new">
                                <h3 class="title text-success">
                                    {{ \App\CentralLogics\Helpers::number_format_short($providerEarned) }}
                                </h3>
                                <h6 class="subtitle">{{ translate('Provider Earning') }}</h6>
                                <div class="info-icon" data-toggle="tooltip" data-placement="top"
                                    data-original-title="{{ translate('After a successful trip completion, the trip amount without discount will be added to the provider’s earnings') }}">
                                    <img src="{{ asset('/public/assets/admin/img/report/new/info1.png') }}"
                                        alt="report/new">
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- End Stats -->
        <!-- Card -->
        <div class="card mt-3">
            <!-- Header -->
            <div class="card-header border-0 py-2">
                <div class="search--button-wrapper">
                    <h3 class="card-title">
                        {{ translate('messages.trip_transactions') }} <span class="badge badge-soft-secondary"
                            id="countItems">{{ $tripTransactions->total() }}</span>
                    </h3>
                    <form>
                        <!-- Search -->
                        <div class="input--group input-group ">
                            <input  class="form-control" placeholder="{{ translate('Search by Trip ID') }}"
                                value="{{ request()?->search ?? null }}" type="search" name="search">
                            <button class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    <!-- Static Export Button -->
                    <div class="hs-unfold ml-3">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle btn export-btn font--sm"
                            href="javascript:;"
                            data-hs-unfold-options="{
                                &quot;target&quot;: &quot;#usersExportDropdown&quot;,
                                &quot;type&quot;: &quot;css-animation&quot;
                            }"
                            data-hs-unfold-target="#usersExportDropdown" data-hs-unfold-invoker="">
                            <i class="tio-download-to mr-1"></i> {{ translate('export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right hs-unfold-content-initialized hs-unfold-css-animation animated hs-unfold-reverse-y hs-unfold-hidden">

                            <span class="dropdown-header">{{ translate('download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                                href="{{ route('admin.transactions.rental.report.transaction-report-export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin/svg/components/excel.svg') }}"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('admin.transactions.rental.report.transaction-report-export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin/svg/components/placeholder-csv-format.svg') }}"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- Static Export Button -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Body -->
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table id="datatable" class="table table-thead-bordered table-align-middle card-table">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.trip_id') }}</th>
                                <th class="border-0">{{ translate('messages.provider') }}</th>
                                <th class="border-0">{{ translate('messages.customer') }}</th>
                                <th class="border-0 min-w-120">{{ translate('messages.Total_Trip_Amount') }}</th>
                                <th class="border-0">{{ translate('messages.Discount on Vehicle') }}</th>
                                <th class="border-0">{{ translate('messages.Coupon_Discount') }}</th>
                                <th class="border-0">{{ translate('messages.Referral_Discount') }}</th>
                                <th class="border-0">{{ translate('messages.Total Discounted Amount') }}</th>
                                <th class="border-0">{{ translate('messages.vat/tax') }}</th>
                                <th class="border-0">{{ translate('messages.Admin_Commission') }}</th>
                                <th class="border-0">
                                    {{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name') ?? translate('messages.additional_charge') }}
                                </th>
                                <th class="border-0">{{ translate('messages.Admin_Discount') }}</th>
                                <th class="min-w-140 text-capitalize">{{ translate('Admin_net_income') }}</th>
                                <th class="border-0 text-capitalize">{{ translate('messages.provider_discount') }}</th>
                                <th class="min-w-140 text-capitalize">{{ translate('provider_net_income') }}</th>
                                <th class="border-0 min-w-120 text-capitalize">{{ translate('messages.Total Amount Received by') }}</th>
                                <th class="border-top border-bottom text-capitalize">
                                    {{ translate('messages.payment_method') }}</th>
                                <th class="border-0 text-capitalize">{{ translate('messages.payment_status') }}</th>
                                <th class="border-0">{{ translate('messages.action') }}</th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($tripTransactions as $k => $ot)
                                <tr scope="row">
                                    <td>{{ $k + $tripTransactions->firstItem() }}</td>
                                    <td><a
                                            href="{{ route('admin.transactions.rental.trip.details', $ot->trip_id) }}">{{ $ot->trip_id }}</a>
                                    </td>
                                    <td class="text-capitalize">
                                        {{ Str::limit($ot?->trip?->provider?->name ?? translate('messages.Not Found'), 25, '...') }}
                                    </td>
                                    <td class="white-space-nowrap">
                                        @if ($ot->trip->customer)
                                            <a class="text-body text-capitalize"
                                                href="{{ route('admin.users.customer.view', [$ot->trip['user_id']]) }}">
                                                <strong>{{ $ot->trip?->customer?->f_name . ' ' . $ot->trip?->customer?->l_name }}</strong>
                                            </a>
                                        @elseif($ot->trip?->user_info['contact_person_name'])
                                            <a class="text-body text-capitalize" href="#">
                                                <strong>{{ $ot->trip?->user_info['contact_person_name'] }}</strong>
                                            </a>
                                        @else
                                            {{ translate('messages.Guest_user') }}
                                        @endif

                                    </td>
                                    {{-- total_trip_amount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->trip_amount) }}</td>

                                    {{-- vehicle_discount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->trip->discount_on_trip) }}</td>

                                    {{-- coupon_discount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->trip['coupon_discount_amount']) }}
                                    </td>
                                    {{-- referral_discount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->trip['ref_bonus_amount']) }}
                                    </td>
                                    {{-- discounted_amount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->trip['coupon_discount_amount'] + $ot->trip['ref_bonus_amount'] + $ot->trip->discount_on_trip) }}
                                    </td>

                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->tax) }}</td>

                                    {{-- admin_commission --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->admin_commission) }}</td>


                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->additional_charge) }}</td>
                                    {{-- admin_discount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->admin_expense) }}</td>


                                    {{-- admin_net_income --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->admin_net_income) }}</td>
                                    {{-- store_discount --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->store_expense) }}</td>
                                    {{-- store_net_income --}}
                                    <td class="white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::format_currency($ot->store_amount - $ot->tax) }}
                                    </td>
                                    @if ($ot->received_by == 'admin')
                                        <td class="text-capitalize white-space-nowrap">{{ translate('messages.admin') }}
                                        </td>
                                    @elseif ($ot->received_by == 'vendor')
                                        <td class="text-capitalize white-space-nowrap">
                                            {{ translate('messages.provider') }}</td>
                                    @endif
                                    <td class="mw--85px text-capitalize min-w-120 ">
                                        {{ translate(str_replace('_', ' ', $ot->trip['payment_method'])) }}
                                    </td>
                                    <td class="text-capitalize white-space-nowrap">
                                        <span class="badge badge-soft-success">
                                            {{ translate('messages.completed') }}
                                        </span>
                                    </td>

                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn btn-outline-success square-btn btn-sm mr-1 action-btn"
                                                href="{{ route('admin.transactions.rental.report.generate-statement', [$ot['id']]) }}">
                                                <i class="tio-download-to"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <!-- End Body -->
                    @if (count($tripTransactions) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $tripTransactions->links() !!}
                    </div>
                    @if (count($tripTransactions) === 0)
                        <div class="empty--data">
                            <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                            <h5>
                                {{ translate('no_data_found') }}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script')
@endpush

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chartjs-chart-matrix/dist/chartjs-chart-matrix.min.js">
    </script>
    <script src="{{ asset('public/assets/admin') }}/js/hs.chartjs-matrix.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/admin-reports.js"></script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/transaction-report.js')}}"></script>


@endpush
