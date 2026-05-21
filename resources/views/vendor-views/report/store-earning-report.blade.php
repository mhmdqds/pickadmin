@extends('layouts.vendor.app')

@section('title', translate('messages.Store_Earning_Report'))

@section('content')
    @php
        $activeTab = request()->tab ?: 'all';
        $reportOverviewTitle = match ($activeTab) {
            'parcel' => 'Comprehensive Financial Overview and Analytics for Store Parcel',
            'rental' => 'Comprehensive Financial Overview and Analytics for Store Rental',
            'ride-share' => 'Comprehensive Financial Overview and Analytics for Store Rides',
            default => 'Comprehensive Financial Overview and Analytics for Store Orders',
        };
    @endphp
    <div class="content container-fluid">
        <div class="page-header pb-0">
            <div>
                <h1 class="page-header-title text-capitalize">
                    {{ translate('messages.Store_Earning_Report') }}
                </h1>
                <p>
                    {{ $reportOverviewTitle }}
                </p>
            </div>
        </div>

        @include('admin-views.report.partials._store_earning_report_content', [
            'summary_url' => route('vendor.report.store-earning-summary'),
            'breakdown_url' => route('vendor.report.store-earning-breakdown'),
            'expense_url' => route('vendor.report.store-expense-breakdown'),
            'trend_url' => route('vendor.report.store-earning-trend'),
            'reset_url' => route('vendor.report.store-earning-report'),
            'export_url_excel' => route('vendor.report.store-earning-export', array_merge(request()->query(), ['export_type' => 'excel'])),
            'export_url_csv' => route('vendor.report.store-earning-export', array_merge(request()->query(), ['export_type' => 'csv'])),
            'transactions_export_url' => route('vendor.report.store-earning-export'),
            'transactions_url' => route('vendor.report.store-earning-transactions'),
            'show_store_select' => false,
            'stores' => [],
            'store_id' => $store_id,
        ])
    </div>
@endsection
