<table>
    <thead>
        <tr>
            <th colspan="8" style="text-align: center;"><h1>{{ translate($data['title']) }}</h1></th>
        </tr>
        <tr>
            <th colspan="3">
                @if(isset($data['delivery_man_name']))
                    {{ translate('messages.Delivery_Man') }} - {{ $data['delivery_man_name'] }}
                @endif
            </th>
            <th colspan="5">
                @if(isset($data['search']))
                {{ translate('Search_Bar_Content') }} - {{ $data['search'] }}
                @endif
            </th>
        </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('messages.Order_ID') }}</th>
            <th>{{ translate('messages.Order_Date') }}</th>
            <th>{{ translate('messages.Delivery_Man') }}</th>
            <th>{{ translate('messages.Delivery_Charge') }}</th>
            <th>{{ translate('messages.Tips') }}</th>
            <th>{{ translate('messages.Commission_Paid') }}</th>
            <th>{{ translate('messages.Net_Profit') }}</th>
        </tr>
    </thead>
    <tbody>
    @foreach($data['transactions'] as $key => $t)
        <tr>
            <td>{{ $key + 1 }}</td>
            <td>{{ $t['order_id'] }}</td>
            <td>
                @php
                    $date = \Carbon\Carbon::parse($t['order_date']);
                @endphp
                {{ $date->format('d M Y') }} {{ $date->format('h:i a') }}
            </td>
            <td>{{ $t['delivery_man'] }}</td>
            <td>{{ \App\CentralLogics\Helpers::format_currency($t['delivery_charge']) }}</td>
            <td>{{ \App\CentralLogics\Helpers::format_currency($t['tips']) }}</td>
            <td>{{ \App\CentralLogics\Helpers::format_currency($t['commission_paid']) }}</td>
            <td>{{ \App\CentralLogics\Helpers::format_currency($t['net_profit']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
