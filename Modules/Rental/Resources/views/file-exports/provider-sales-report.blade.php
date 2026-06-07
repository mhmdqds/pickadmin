<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('provider_vehicle_reports') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('Search_Criteria') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('zone' )}} - {{ $data['zone']??translate('all') }}
                    <br>
                    {{ translate('provider' )}} - {{ $data['provider']??translate('all') }}
                    @if ($data['from'])
                    <br>
                    {{ translate('from' )}} - {{ $data['from']?Carbon\Carbon::parse($data['from'])->format('d M Y'):'' }}
                    @endif
                    @if ($data['to'])
                    <br>
                    {{ translate('to' )}} - {{ $data['to']?Carbon\Carbon::parse($data['to'])->format('d M Y'):'' }}
                    @endif
                    <br>
                    {{ translate('filter')  }}- {{  translate($data['filter']) }}
                    <br>
                    {{ translate('Search_Bar_Content')  }}- {{ $data['search'] ??translate('N/A') }}

                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
                </tr>
            <tr>
                <th>{{ translate('Analytics') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('Total Trip Amount')  }}- {{ \App\CentralLogics\Helpers::number_format_short($data['trips']->sum('trip_amount')) }}
                    <br>
                    {{ translate('total_tax')  }}- {{ \App\CentralLogics\Helpers::number_format_short($data['trips']->sum('tax_amount')) }}
                    <br>
                    {{ translate('total_commission')  }}- {{ \App\CentralLogics\Helpers::number_format_short($data['trips']->sum('trip_transaction_sum_admin_commission')-$data['trips']->sum('trip_transaction_sum_admin_expense')) }}
                    <br>
                    {{ translate('total_provider_earning')  }}- {{ \App\CentralLogics\Helpers::number_format_short($data['trips']->sum('trip_transaction_sum_store_amount')) }}
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{translate('vehicle_image')}}</th>
            <th>{{ translate('Vehicle Info') }}</th>
            <th>{{ translate('Total Trip') }}</th>
            <th>
                {{ translate('Total Trip Amount') }}</th>
            <th>
                {{ translate('Discount_Given') }}</th>
        </thead>
        <tbody>
        @foreach($data['vehicles'] as $key => $item)
        <tr>
            <td>{{$key+1}}</td>
            <td></td>
            <td>{{  $item['name']  }}</td>
            <td>
                {{ $item->trips_count ?? 0 }}
            </td>
            <td>
                {{\App\CentralLogics\Helpers::format_currency($item->trips_sum_price) }}
            </td>
            <td>
                {{ \App\CentralLogics\Helpers::format_currency($item->total_discount) }}
            </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
