@extends('layouts.admin.app')

@section('title', translate('messages.Delivery_Man_Earning_Report'))

@section('deliveryman_earning_report')
    active
@endsection
@section('content')
    @php
        $reportOverviewTitle = match (true) {
            request()->routeIs('admin.transactions.ride-share.report.rider-earning-report') => 'Comprehensive Financial Overview and Analytics for Deliveryman Rides',
            request()->tab === 'rental' => 'Comprehensive Financial Overview and Analytics for Deliveryman Rental',
            request()->tab === 'parcel' => 'Comprehensive Financial Overview and Analytics for Deliveryman Parcel',
            default => 'Comprehensive Financial Overview and Analytics for Deliveryman Orders',
        };
    @endphp
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-0">
            <div>
                <h1 class="page-header-title text-capitalize">
                    {{translate('messages.Delivery_Man_Earning_Report') }}
                </h1>
                <p>
                    {{ $reportOverviewTitle }}
                </p>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="js-nav-scroller hs-nav-scroller-horizontal mb-20">
            <ul class="nav mb-0 nav-tabs border-0 nav--tabs nav--pills">
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('admin.transactions.report.deliveryman-earning-report') }}" aria-disabled="true">
                        {{ translate('messages.Delivery_Man') }}
                    </a>
                </li>
                @if (addon_published_status('RideShare'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.transactions.ride-share.report.rider-earning-report') }}" aria-disabled="true">
                            {{ translate('messages.Ride Share') }} {{ translate('messages.Rider') }}
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        @include('admin-views.report.partials._deliveryman_earning_report_content', [
            'summary_url' => route('admin.transactions.report.deliveryman-earning-summary'),
            'breakdown_url' => route('admin.transactions.report.deliveryman-earning-breakdown'),
            'expense_url' => route('admin.transactions.report.deliveryman-expense-breakdown'),
            'trend_url' => route('admin.transactions.report.deliveryman-earning-trend'),
            'reset_url' => route('admin.transactions.report.deliveryman-earning-report'),
            'export_url_excel' => route('admin.transactions.report.admin-deliveryman-earning-export', array_merge(request()->query(), ['export_type' => 'excel'])),
            'export_url_csv' => route('admin.transactions.report.admin-deliveryman-earning-export', array_merge(request()->query(), ['export_type' => 'csv'])),
            'delivery_men' => $delivery_men,
            'delivery_man_id' => $delivery_man_id,
        ])
    </div>
@endsection
