@extends('layouts.admin.app')

@section('title', translate('messages.Store_Earning_Report'))

@section('store_earning_report')
    active
@endsection
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
        <!-- Page Header -->
        <div class="page-header pb-0">
            <div>
                <h1 class="page-header-title text-capitalize">
                    {{translate('messages.Store_Earning_Report') }}
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
                    <a class="nav-link {{ request()->tab !== 'rental' ? 'active' : '' }}"
                        href="{{ route('admin.transactions.report.store-earning-report') }}"
                        aria-disabled="true">{{ translate('messages.All Modules') }}</a>
                </li>
                @if (addon_published_status('Rental'))
                    <li class="nav-item">
                        <a class="nav-link {{ request()->tab === 'rental' ? 'active' : '' }}"
                            href="{{ route('admin.transactions.report.store-earning-report', ['tab' => 'rental']) }}"
                            aria-disabled="true">{{ translate('messages.Rental Module') }}</a>
                    </li>
                @endif
            
            </ul>
        </div>

        @if (request()->tab === 'rental' && addon_published_status('Rental'))
            @include('rental::provider.report.earning-report.content', [
                'report_url' => route('admin.transactions.report.store-earning-report'),
                'summary_url' => route('admin.transactions.rental.report.provider-earning-summary'),
                'breakdown_url' => route('admin.transactions.rental.report.provider-earning-breakdown'),
                'expense_url' => route('admin.transactions.rental.report.provider-expense-breakdown'),
                'trend_url' => route('admin.transactions.rental.report.provider-earning-trend'),
                'reset_url' => route('admin.transactions.report.store-earning-report', ['tab' => 'rental']),
                'transactions_export_url' => route('admin.transactions.rental.report.provider-earning-export'),
                'transactions_url' => route('admin.transactions.rental.report.provider-earning-transactions'),
                'show_store_select' => true,
                'store' => $store,
                'store_id' => $store_id,
                'module_id' => $module_id,
                'tab' => 'rental',
            ])
        @else
            @include('admin-views.report.partials._store_earning_report_content', [
                'summary_url' => route('admin.transactions.report.store-earning-summary'),
                'breakdown_url' => route('admin.transactions.report.store-earning-breakdown'),
                'expense_url' => route('admin.transactions.report.store-expense-breakdown'),
                'trend_url' => route('admin.transactions.report.store-earning-trend'),
                'reset_url' => route('admin.transactions.report.store-earning-report'),
                'export_url_excel' => route('admin.transactions.report.store-earning-export', array_merge(request()->query(), ['export_type' => 'excel'])),
                'export_url_csv' => route('admin.transactions.report.store-earning-export', array_merge(request()->query(), ['export_type' => 'csv'])),
                'transactions_export_url' => route('admin.transactions.report.store-earning-export'),
                'transactions_url' => route('admin.transactions.report.store-earning-transactions'),
                'show_store_select' => true,
                'store' => $store,
                'store_id' => $store_id,
                'module_id' => $module_id,
            ])
        @endif
    </div>
@endsection
