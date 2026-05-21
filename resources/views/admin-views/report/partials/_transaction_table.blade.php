@php
    $hide_source_column = $hide_source_column ?? false;
    $use_additional_charge_name_in_breakdown = $use_additional_charge_name_in_breakdown ?? false;
    $additionalChargeLabelForAdmin = \App\CentralLogics\Helpers::get_business_data('additional_charge_name') ?? translate('messages.additional_charge');
    $breakdown_additional_charge_label = $use_additional_charge_name_in_breakdown
        ? $additionalChargeLabelForAdmin
        : translate('messages.Packaging Charge');
@endphp

@if(count($transactions) > 0)
    <table id="datatable"
        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-dark"
        data-hs-datatables-options='{
        "columnDefs": [{
            "targets": [0],
            "orderable": false
        }],
        "order": [],
        "info": {
        "totalQty": "#datatableWithPaginationInfoTotalQty"
        },
        "search": "#datatableSearch",
        "entries": "#datatableEntries",
        "pageLength": 25,
        "isResponsive": false,
        "isShowPaging": false,
        "paging":false
    }'>
        <thead class="thead-light">
            <tr>
                <th class="border-0">
                    {{ translate('SL') }}
                </th>
                <th class="table-column-pl-0 border-0">{{ translate('messages.Transaction_ID') }}</th>
                <th class="border-0">{{ translate('messages.Date') }}</th>
                @if(!$hide_source_column)
                    <th class="border-0">
                        {{ ($type ?? 'order') === 'subscription' ? translate('messages.Store') : translate('messages.Source') }}
                    </th>
                @endif
                @if(($type ?? 'order') === 'subscription')
                    <th class="border-0">{{ translate('messages.Transaction_Type') }}</th>
                @endif
                @if(($type ?? 'order') !== 'subscription')
                    <th class="border-0 text-center">
                        @if(($type ?? 'order') === 'expense')
                            {{ translate('messages.Expense_Source') }}
                        @else
                            {{ translate('messages.Earning_Source') }}
                        @endif
                    </th>
                @endif
                <th class="border-0 text-right">{{ translate('messages.Amount') }}</th>
            </tr>
        </thead>
        <tbody id="set-rows">
            @foreach($transactions as $k => $t)
                <tr>
                    <td>
                        @if(isset($t['breakdown']) && count($t['breakdown']) > 0)
                            <span class="collapse-next-tr cursor-pointer">
                                <i class="tio-chevron-down"></i> {{ $loop->iteration + $transactions->firstItem() - 1 }}
                            </span>
                        @else
                            {{ $loop->iteration + $transactions->firstItem() - 1 }}
                        @endif
                    </td>
                    <td class="font-medium">{{ $t['transaction_id'] }}</td>
                      <td>

                        {{ \App\CentralLogics\Helpers::date_format($t['date']) }}
                        <br>
                        {{ \App\CentralLogics\Helpers::time_format($t['date']) }}
                    </td>
                    @if(!$hide_source_column)
                        <td>
                            <div class="mb-1">{{ $t['source'] ?? $t['store'] ?? '' }}</div>
                            @if(isset($t['source_type']))
                                <div
                                    class="badge text-{{ in_array($t['source_type'], ['Store', 'Restaurant']) ? 'warning bg-warning' : ($t['source_type'] == 'Delivery Man' ? 'info bg-info' : 'info bg-info') }} bg-opacity-10 rounded-lg font-medium px-2">
                                    {{ translate($t['source_type']) }}</div>
                            @endif
                        </td>
                    @endif
                    @if(($type ?? 'order') === 'subscription')
                        <td>
                            @if(isset($t['transaction_type']))
                                <div class="badge rounded-lg font-medium px-2" style="{{ $t['transaction_type_badge_style'] ?? 'background-color: #F4F5F7; color: #4B5563;' }}">
                                    {{ translate($t['transaction_type']) }}
                                </div>
                            @endif
                        </td>
                    @endif
                    @if(($type ?? 'order') !== 'subscription')
                        <td class="text-center">
                            @if($hide_source_column)
                                @php
                                    $order_id = $t['earning_from'] ?? $t['expense_source'] ?? null;
                                    $badge = ($type ?? '') === 'expense' ? ($t['expense_source_badge'] ?? $t['transaction_type'] ?? null) : null;
                                @endphp
                                @if($badge)
                                    <div class="badge text-dark bg-danger bg-opacity-10 rounded-lg font-regular px-2 mb-1">
                                        {{ translate($badge) }}
                                    </div>
                                @endif
                                @if(!empty($t['order_id']))
                                    <a href="{{ route('vendor.order.details', ['id' => $t['order_id']]) }}">
                                        <div class="fs-12">{{ $order_id }}</div>
                                    </a>
                                @elseif(!empty($order_id))
                                    <div class="fs-12">{{ $order_id }}</div>
                                @endif
                            @else
                                @if($type === 'order' || ($type ?? '') === '')
                                    {{-- Just show Order ID for Earnings --}}
                                    @if(isset($t['earning_from']))
                                        @if(isset($t['order_id']) && !empty($t['order_id']))
                                            <a href="{{ request()->is('admin/*') ? route('admin.order.details', $t['order_id']) : route('vendor.order.details', $t['order_id']) }}" class="fs-12 mt-1">{{ $t['earning_from'] }}</a>
                                        @else
                                            <div class="fs-12 mt-1">{{ $t['earning_from'] }}</div>
                                        @endif
                                    @endif
                                @else
                                    {{-- Show Badge and Source --}}
                                    @php
                                        $badge = $t['expense_source_badge'] ?? $t['transaction_type'] ?? null;
                                    @endphp
                                    @if($badge)
                                        <div class="badge text-dark bg-danger bg-opacity-10 rounded-lg font-regular px-2 mb-1">
                                            {{ translate($badge) }}</div>
                                    @endif

                                    @if(isset($t['expense_source']))
                                        @if(isset($t['order_id']) && !empty($t['order_id']))
                                            <a href="{{ request()->is('admin/*') ? route('admin.order.details', ['id' => $t['order_id']]) : route('vendor.order.details', ['id' => $t['order_id']]) }}" class="fs-12 mt-1">
                                                <div class="fs-12">{{ $t['expense_source'] }}</div>
                                            </a>
                                        @else
                                            <div class="fs-12 mt-1">{{ $t['expense_source'] }}</div>
                                        @endif
                                    @endif
                                @endif
                            @endif
                        </td>
                    @endif
                    <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t['amount']) }}</td>
                </tr>
                @if(isset($t['breakdown']) && count($t['breakdown']) > 0)
                    @php
                        $isOrderTransaction = $type === 'order' || ($type ?? '') === '';
                        $hideOrderCommission = isset($t['breakdown']['hide_order_commission']) && $t['breakdown']['hide_order_commission'];
                        $firstEarningLabel = translate('messages.Order Commission');
                        $secondEarningLabel = isset($t['breakdown']['tax_collected'])
                            ? translate('messages.Tax Collected')
                            : translate('messages.Delivery Fee Comission');
                        $secondEarningAmount = $t['breakdown']['delivery_fee_comission'] ?? $t['breakdown']['tax_collected'] ?? 0;
                    @endphp
                    <tr class="collapsing-tr d-none bg-light2">
                        <td></td>
                        <td colspan="{{ $hide_source_column ? '3' : '4' }}" class="pr-0">
                            @if($isOrderTransaction)
                                @if($hide_source_column)
                                    <div class="mb-2">{{ translate('messages.Order Sales') }}</div>
                                    <div class="mb-2">{{ $secondEarningLabel }}</div>
                                    <div>{{ $breakdown_additional_charge_label }}</div>
                                @else
                                    @if(!$hideOrderCommission)
                                        <div class="mb-2">{{ $firstEarningLabel }}</div>
                                    @endif
                                    @if(array_key_exists('delivery_fee_comission', $t['breakdown']) || array_key_exists('tax_collected', $t['breakdown']))
                                        <div class="mb-2">{{ $secondEarningLabel }}</div>
                                    @endif
                                    <div class="mb-2">{{ $breakdown_additional_charge_label }}</div>
                                @endif
                            @else
                                @if(isset($t['breakdown']['general_expense']))
                                    <div class="mb-2">{{ translate($t['breakdown']['type'] ?? 'General Expense') }}</div>
                                @else
                                    <div class="mb-2">{{ translate('messages.Commission Paid') }}</div>
                                    <div class="mb-2">{{ translate('messages.Discount on Item') }}</div>
                                    <div class="mb-2">{{ translate('messages.Coupon Contribution') }}</div>
                                    <div>{{ translate('messages.Free Delivery') }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="text-right pl-0">
                            @if($isOrderTransaction)
                                @if($hide_source_column)
                                    <div class="mb-2">
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['order_commission'] ?? 0) }}</div>
                                    <div class="mb-2">+
                                        {{ \App\CentralLogics\Helpers::format_currency($secondEarningAmount) }}</div>
                                    <div>+
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['packaging_fee_collected'] ?? 0) }}
                                    </div>
                                @else
                                    @if(!$hideOrderCommission)
                                        <div class="mb-2">
                                            {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['order_commission'] ?? 0) }}</div>
                                    @endif
                                    @if(array_key_exists('delivery_fee_comission', $t['breakdown']) || array_key_exists('tax_collected', $t['breakdown']))
                                        <div class="mb-2">+
                                            {{ \App\CentralLogics\Helpers::format_currency($secondEarningAmount) }}</div>
                                    @endif
                                    <div class="mb-2">+
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['packaging_fee_collected'] ?? 0) }}</div>
                                @endif
                            @else
                                @if(isset($t['breakdown']['general_expense']))
                                    <div class="mb-2">
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['general_expense'] ?? 0) }}</div>
                                @else
                                    <div class="mb-2">
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['admin_commission'] ?? 0) }}</div>
                                    <div class="mb-2">+
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['discount_on_item'] ?? 0) }}</div>
                                    <div class="mb-2">+
                                        {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['coupon_contribution'] ?? 0) }}</div>
                                    <div>+ {{ \App\CentralLogics\Helpers::format_currency($t['breakdown']['free_delivery'] ?? 0) }}</div>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    <div class="page-area px-4 pb-3">
        <div class="d-flex align-items-center justify-content-end">
            <div>
                {!! $transactions->links() !!}
            </div>
        </div>
    </div>
@else
    <div class="empty--data py-5 w-100">
        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
        <h5>
            {{ translate('no_data_found') }}
        </h5>
    </div>
@endif
