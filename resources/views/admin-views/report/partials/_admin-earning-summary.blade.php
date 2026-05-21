@php
    $filter = request()->filter;
    $show_comparison = in_array($filter, ['this_week', 'this_month', 'this_year', 'custom', 'previous_year']);
    $comparison_text = translate('messages.vs last period');
    if ($filter == 'this_week') {
        $comparison_text = translate('messages.vs last week');
    } elseif ($filter == 'this_month') {
        $comparison_text = translate('messages.vs last month');
    } elseif ($filter == 'this_year') {
        $comparison_text = translate('messages.vs last year');
    } elseif ($filter == 'previous_year') {
        $comparison_text = translate('messages.vs two years ago');
    }
@endphp

<div class="row g-3 mb-20">
    <div class="col-lg-4 col-md-6">
        <div class="card-shape-in position-relative bg-success-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1 d-flex flex-column h-100">
                <div>
                    <div class="opacity-lg fs-14 mb-2">{{ translate('messages.Total_Earnings') }}</div>
                    @if ($show_comparison)
                        <div class="opacity-lg fs-14 mb-2">
                            {{ $summary['admin_earning_positive'] ? '↑' : '↓' }}
                            {{ abs($summary['admin_earning_percentage']) }}%
                            {{ $comparison_text }}
                        </div>
                    @endif
                </div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mt-auto mb-0">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['admin_earning']) }}
                </h2>
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/earning.png') }}" alt="earning">
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card-shape-in position-relative bg-warning-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1 d-flex flex-column h-100">
                <div>
                    <div class="opacity-lg fs-14 mb-2">{{ translate('messages.Total_Expenses') }}</div>
                    @if ($show_comparison)
                        <div class="opacity-lg fs-14 mb-2">
                            {{ $summary['admin_expense_positive'] ? '↑' : '↓' }}
                            {{ abs($summary['admin_expense_percentage']) }}%
                            {{ $comparison_text }}
                        </div>
                    @endif
                </div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mt-auto mb-0">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['admin_expense']) }}
                </h2>
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/earning.png') }}" alt="earning">
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card-shape-in position-relative bg-info-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1 d-flex flex-column h-100">
                <div>
                    <div class="fs-14 mb-2">
                        <span class="opacity-lg">
                            {{ translate('messages.Net_Profit') }}
                        </span>
                        <span data-toggle="tooltip" data-placement="right"
                            data-original-title="{{ translate('Net Profit is the amount of money a business earns after deducting all expenses from its total earnings') }}"
                            class="text-white tio-info fs-16 m-0"></span>
                    </div>
                    @if ($show_comparison)
                        <div class="opacity-lg fs-14 mb-2">
                            {{ $summary['net_profit_positive'] ? '↑' : '↓' }}
                            {{ abs($summary['net_profit_percentage']) }}%
                            {{ $comparison_text }}
                        </div>
                    @endif
                </div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mt-auto mb-0">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['net_profit']) }}
                </h2>
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/wallet.png') }}" alt="profit">
            </div>
        </div>
    </div>
</div>
