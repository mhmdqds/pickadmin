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
        <div class="card-shape-in h-100 position-relative bg-success-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1">
                <div class="opacity-lg fs-14 mb-2">{{ translate('Total Earnings With Admin Commission') }}</div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['total_earnings'] ?? 0) }}
                </h2>
                @if ($show_comparison)
                    <div class="opacity-lg fs-14">
                        {{ $summary['total_earnings_positive'] ? '↑' : '↓' }}
                        {{ $summary['total_earnings_percentage'] }}%
                        {{ $comparison_text }}
                    </div>
                @endif
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/earning.png') }}" alt="earning">
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card-shape-in h-100 position-relative bg-warning-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1">
                <div class="opacity-lg fs-14 mb-2">{{ translate('messages.Commissions_Paid') }}</div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['total_expenses'] ?? 0) }}
                </h2>
                @if ($show_comparison)
                    <div class="opacity-lg fs-14">
                        {{ $summary['total_expenses_positive'] ? '↑' : '↓' }}
                        {{ $summary['total_expenses_percentage'] }}%
                        {{ $comparison_text }}
                    </div>
                @endif
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/earning.png') }}" alt="earning">
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-md-6">
        <div class="card-shape-in h-100 position-relative bg-info-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer">
            <div class="flex-grow-1">
                <div class="fs-14 mb-2">
                    <span class="opacity-lg">
                        {{ translate('messages.Net_Profit') }}
                    </span>
                    <span data-toggle="tooltip" data-placement="right"
                        data-original-title="{{ translate('Net profit shows the amount a deliveryman keeps after adding delivery charge and tips, then subtracting the commission paid.') }}"
                        class="text-white tio-info fs-16 m-0"></span>
                </div>
                <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">
                    {{ \App\CentralLogics\Helpers::format_currency($summary['net_profit'] ?? 0) }}
                </h2>
                @if ($show_comparison)
                    <div class="opacity-lg fs-14">
                        {{ $summary['net_profit_positive'] ? '↑' : '↓' }}
                        {{ $summary['net_profit_percentage'] }}%
                        {{ $comparison_text }}
                    </div>
                @endif
            </div>
            <div class="mark_badge fs-24 flex-shrink-0 rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                <img width="24" src="{{ asset('public/assets/admin/img/report/new/wallet.png') }}" alt="profit">
            </div>
        </div>
    </div>


</div>
