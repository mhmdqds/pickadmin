@extends('layouts.vendor.app')

@section('title', translate('messages.trip_report'))

@section('content')
    @php
        $vendorData = \App\CentralLogics\Helpers::get_store_data();
        $vendor = $vendorData?->module_type;
        $title = $vendor == 'rental' ? 'Provider' : 'Store';
        $orderOrTrip = $vendor == 'rental' ? 'trip' : 'order';
    @endphp

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('/public/assets/admin/img/report/report.png')}}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.trip_report') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card mb-20">
            <div class="card-body">
                <h4 class="">{{ translate('Search Data') }}</h4>
                <form action="{{ route('vendor.report.set-date') }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <select class="form-control set-filter" data-url="{{ url()->full() }}" data-filter="filter" name="filter">
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
                            <div class="col-sm-6 col-md-3 ml-auto">
                                <button type="submit"
                                    class="btn btn-primary btn-block h--45px">{{ translate('Filter') }}</button>
                            </div>
                        @endif
                    </div>
                </form>
            </div>
        </div>
        @php
            $from = session('from_date') . ' 00:00:00';
            $to = session('to_date') . ' 23:59:59';
        @endphp
        <div class="mb-20">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a class="__card-1 h-100" href="#">
                        <img src="{{asset('/public/assets/admin/img/report/new/total.png')}}" class="icon" alt="report/new">
                        <h3 class="title">{{$trips->total()}}</h3>
                        <h6 class="subtitle">{{translate('messages.total_trips')}}</h6>
                    </a>
                </div>
                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-6">
                            <a class="__card-2 __bg-1" href="#">
                            <h4 class="title">{{$total_progress_count}}</h4>
                            <span class="subtitle">{{translate('messages.in_progress_trips')}} <span data-toggle="tooltip" data-placement="right" data-original-title="{{translate('This count includes all the pending & confirmed trips')}}"><img src="{{asset('public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.in_progress_trips')}}"></span></span>
                            <img src="{{asset('/public/assets/admin/img/report/new/progress-report.png')}}" alt="report/new" class="card-icon">
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <a class="__card-2 __bg-2" href="#">
                            <h4 class="title">{{$total_ongoing_count}}</h4>
                            <span class="subtitle">{{translate('messages.ongoing_trips')}}</span>
                            <img src="{{asset('/public/assets/admin/img/report/new/on-the-way.png')}}" alt="report/new" class="card-icon">
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <a class="__card-2 __bg-3" href="#">
                            <h4 class="title">{{$total_completed_count}}</h4>
                            <span class="subtitle">{{ translate('messages.completed_trips') }}</span>
                            <img src="{{asset('/public/assets/admin/img/report/new/delivered.png')}}" alt="report/new" class="card-icon">
                            </a>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <a class="__card-2 __bg-6" href="#">
                            <h4 class="title">{{$total_canceled_count}}</h4>
                            <span class="subtitle">{{translate('messages.canceled_trips')}}</span>
                            <img src="{{asset('/public/assets/admin/img/report/new/canceled.png')}}" alt="report/new" class="card-icon">
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
                        {{ translate('messages.Total Trips') }} <span
                            class="badge badge-soft-secondary" id="countItems">{{ $trips->total() }}</span>
                    </h3>
                    <form class="search-form">
                        <!-- Search -->
                        <div class="input--group input-group input-group-merge input-group-flush">
                            <input name="search" type="search" class="form-control" value="{{request()->query('search')}}" placeholder="{{ translate('Search by Trip ID') }}">
                            <button type="button" class="btn btn--secondary"><i class="tio-search"></i></button>
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
                                href="{{ route('vendor.report.trip-report-export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin/svg/components/excel.svg') }}"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('vendor.report.trip-report-export', ['type' => 'csv', request()->getQueryString()]) }}">
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
                    <table class="table table-borderless middle-align __txt-14px">
                        <thead class="thead-light white--space-false">
                            <tr>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.sl') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.trip_id') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Customer info') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Total Fare of Vehicle') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Discount on Vehicle') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.coupon_discount') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.referral_discount') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Total_discounted_amount') }}</th>
                                <th class="text-capitalize border-top border-bottom text-center">{{ translate('messages.tax') }}</th>
                                <th class="text-capitalize border-top border-bottom text-center">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Total Trip Amount') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Total Amount Received By') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.payment_method') }}</th>
                                <th class="text-capitalize border-top border-bottom">{{ translate('messages.Trip Status') }}</th>
                                <th class="text-capitalize border-top border-bottom text-center">{{ translate('messages.action') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody id="set-rows">
                            @foreach ($trips as $key => $trip)
                                <tr class="status-{{ $trip['trip_status'] }} class-all">
                                    <td class="">
                                        {{ $key + $trips->firstItem() }}
                                    </td>
                                    <td class="table-column-pl-0">
                                        <a href="{{ route('vendor.trip.details', $trip->id) }}">{{ $trip->id }}</a>
                                    </td>
                                    <td>
                                        @if($trip->is_guest)
                                        @php($customer_details = $trip['user_info'])
                                        <strong>{{$customer_details['contact_person_name']}}</strong>
                                        <div>{{$customer_details['contact_person_number']}}</div>

                                        @elseif ($trip->customer)
                                        <a class="text-body text-capitalize"
                                            href="{{ route('admin.users.customer.view', [$trip['user_id']]) }}">
                                            <strong>{{ $trip->customer['f_name'] . ' ' . $trip->customer['l_name'] }}</strong>
                                        </a>
                                        @else
                                            <label class="badge badge-danger">{{ translate('messages.invalid_customer_data') }}</label>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-right mw--85px">
                                            <div>
                                                {{ \App\CentralLogics\Helpers::number_format_short(($trip['trip_amount']+$trip['coupon_discount_amount'] + $trip['discount_on_trip'] + $trip['ref_bonus_amount']) - ($trip->additional_charge + $trip['tax_amount']) ) }}
                                            </div>
                                            @if ($trip->payment_status == 'paid')
                                                <strong class="text-success">
                                                    {{ translate('messages.paid') }}
                                                </strong>
                                            @else
                                                <strong class="text-danger">
                                                    {{ translate('messages.unpaid') }}
                                                </strong>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center mw--85px">
                                        {{ \App\CentralLogics\Helpers::number_format_short( $trip['discount_on_trip'] ) }}
                                    </td>
                                    <td class="text-center mw--85px">
                                        {{ \App\CentralLogics\Helpers::number_format_short($trip['coupon_discount_amount']) }}
                                    </td>
                                    <td class="text-center mw--85px">
                                        {{ \App\CentralLogics\Helpers::number_format_short($trip['ref_bonus_amount']) }}
                                    </td>
                                    <td class="text-center mw--85px">
                                        {{ \App\CentralLogics\Helpers::number_format_short($trip['coupon_discount_amount'] + $trip['discount_on_trip'] + $trip['ref_bonus_amount'])  }}
                                    </td>
                                    <td class="text-center mw--85px white-space-nowrap">
                                        {{ \App\CentralLogics\Helpers::number_format_short($trip['tax_amount']) }}
                                    </td>
                                    <td class="text-center mw--85px">
                                        {{ \App\CentralLogics\Helpers::number_format_short($trip['additional_charge']) }}
                                    </td>
                                    <td>
                                        <div class="text-right mw--85px">
                                            <div>
                                                {{ \App\CentralLogics\Helpers::number_format_short($trip['trip_amount']) }}
                                            </div>
                                            @if ($trip->payment_status == 'paid')
                                                <strong class="text-success">
                                                    {{ translate('messages.paid') }}
                                                </strong>
                                            @else
                                                <strong class="text-danger">
                                                    {{ translate('messages.unpaid') }}
                                                </strong>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center mw--85px text-capitalize">
                                        {{isset($trip->trip_transaction) ? $trip->trip_transaction->received_by : translate('messages.not_received_yet')}}
                                    </td>
                                    <td class="text-center mw--85px text-capitalize">
                                            {{ translate(str_replace('_', ' ', $trip['payment_method'])) }}
                                    </td>
                                    <td class="text-center mw--85px text-capitalize">
                                        @if($trip['trip_status']=='pending')
                                                <span class="badge badge-soft-info">
                                                  {{translate('messages.pending')}}
                                                </span>
                                            @elseif($trip['trip_status']=='confirmed')
                                                <span class="badge badge-soft-info">
                                                  {{translate('messages.confirmed')}}
                                                </span>
                                            @elseif($trip['trip_status']=='ongoing')
                                                <span class="badge badge-soft-warning">
                                                  {{translate('messages.ongoing')}}
                                                </span>
                                            @elseif($trip['trip_status']=='picked_up')
                                                <span class="badge badge-soft-warning">
                                                  {{translate('messages.out_for_delivery')}}
                                                </span>
                                            @elseif($trip['trip_status']=='completed')
                                                <span class="badge badge-soft-success">
                                                  {{translate('messages.completed')}}
                                                </span>
                                            @elseif($trip['trip_status']=='failed')
                                                <span class="badge badge-soft-danger">
                                                  {{translate('messages.payment_failed')}}
                                                </span>
                                            @elseif($trip['trip_status']=='handover')
                                                <span class="badge badge-soft-danger">
                                                  {{translate('messages.handover')}}
                                                </span>
                                            @elseif($trip['trip_status']=='canceled')
                                                <span class="badge badge-soft-danger">
                                                  {{translate('messages.canceled')}}
                                                </span>
                                            @elseif($trip['trip_status']=='accepted')
                                                <span class="badge badge-soft-danger">
                                                  {{translate('messages.accepted')}}
                                                </span>
                                            @elseif($trip['trip_status']=='refund_request_canceled')
                                                <span class="badge badge-soft-danger">
                                                  {{translate('messages.refund_request_canceled')}}
                                                </span>
                                            @else
                                                <span class="badge badge-soft-danger">
                                                  {{str_replace('_',' ',$trip['trip_status'])}}
                                                </span>
                                            @endif

                                    </td>


                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="ml-2 btn btn-sm btn--warning btn-outline-warning action-btn"
                                                href="{{ route('vendor.trip.details', $trip->id) }}">
                                                <i class="tio-invisible"></i>
                                            </a>
                                            <a class="ml-2 btn btn-sm btn--primary btn-outline-primary action-btn"
                                                href="{{route("vendor.trip.generate-invoice",["id" => $trip->id])}}">
                                                <i class="tio-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- End Table -->


            </div>
            <!-- End Body -->
            @if (count($trips) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $trips->links() !!}
            </div>
            @if (count($trips) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/vendor/chartjs-chart-matrix/dist/chartjs-chart-matrix.min.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/hs.chartjs-matrix.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/admin-reports.js"></script>
@endpush

