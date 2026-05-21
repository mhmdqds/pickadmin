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
                <th class="border-0">{{ translate('SL') }}</th>
                @if($type === 'order')
                    <th class="table-column-pl-0 border-0">{{ translate('messages.Order_ID') }}</th>
                    <th class="border-0">{{ translate('messages.Order_Date') }}</th>
                    <th class="border-0">{{ translate('messages.Delivery_Man') }}</th>
                    <th class="border-0 text-center">{{ translate('messages.Delivery_Charge') }}</th>
                    <th class="border-0 text-right">{{ translate('messages.Tips') }}</th>
                    <th class="border-0 text-right">{{ translate('messages.Commission_Paid') }}</th>
                    <th class="border-0 text-right">{{ translate('messages.Net_Profit') }}</th>
                @endif
            </tr>
        </thead>
        <tbody id="set-rows">
            @foreach($transactions as $k => $t)
                <tr>
                    <td>{{ $k + $transactions->firstItem() }}</td>
                    @if($type === 'order')
                        <td class="font-medium">
                            <a href="{{ route('admin.order.details', ['id' => $t['raw_order_id']]) }}">
                                {{ $t['order_id'] }}
                            </a>
                        </td>
                        <td>
                            @php
                                $date = \Carbon\Carbon::parse($t['order_date']);
                            @endphp
                            {{ $date->format('d M Y') }}
                            <br>
                            {{ $date->format('h:i a') }}
                        </td>
                        <td>
                            <div class="mb-1">{{ $t['delivery_man'] }}</div>
                        </td>
                        <td class="text-center">{{ \App\CentralLogics\Helpers::format_currency($t['delivery_charge']) }}</td>
                        <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t['tips']) }}</td>
                        <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t['commission_paid']) }}</td>
                        <td class="text-right">{{ \App\CentralLogics\Helpers::format_currency($t['net_profit']) }}</td>
                    @endif
                </tr>
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