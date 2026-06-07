<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('provider_summary_reports') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('Search_Criteria') }}</th>
                <th></th>
                <th></th>
                <th>
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
                    {{ translate('new_registered_provider')  }}- {{ $data['new_providers'] ??translate('N/A') }}
                    <br>
                    {{ translate('total_trips')  }}- {{ $data['trips'] ??translate('N/A') }}
                    <br>
                    {{ translate('total_trip_amount')  }}- {{ $data['total_trip_amount'] ??translate('N/A') }}
                    <br>
                    {{ translate('completed_trips')  }}- {{ $data['total_completed'] ??translate('N/A') }}
                    <br>
                    {{ translate('incomplete_trips')  }}- {{ $data['total_ongoing'] ??translate('N/A') }}
                    <br>
                    {{ translate('canceled_trips')  }}- {{ $data['total_canceled'] ??translate('N/A') }}
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>{{ translate('Payment_Statistics') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('cash_payments')  }} - {{ $data['cash_payments'] ??translate('N/A') }}
                    <br>
                    {{ translate('digital_payments')  }} - {{ $data['digital_payments'] ??translate('N/A') }}
                    <br>
                    {{ translate('wallet_payments')  }} - {{ $data['wallet_payments'] ??translate('N/A') }}
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{translate('provider_name')}}</th>
            <th>{{translate('Total Amount')}}</th>
            <th>{{translate('Total Trip')}}</th>
            <th>{{translate('Total Completed Trip')}}</th>
            <th>{{translate('Completion Rate')}}</th>
            <th>{{translate('Ongoing Rate')}}</th>
            <th>{{translate('Cancelation Rate')}}</th>
        </thead>
        <tbody>
        @foreach($data['providers'] as $key => $provider)
        @php($completed = $provider->trips->where('trip_status', 'completed')->count())
        @php($canceled = $provider->trips->where('trip_status', 'canceled')->count())
        @php($refunded = $provider->trips->where('trip_status', 'refunded')->count())
        @php($refund_requested = $provider->trips->whereNotNull('refund_requested')->count())
        <tr>
            <td>{{$key+1}}</td>
            <td>
                {{  $provider->name  }}
            </td>
            <td>
                {{\App\CentralLogics\Helpers::number_format_short($provider->trips->where('trip_status','completed')->sum('trip_amount'))}}
            </td>
            <td>
                {{ $provider->trips->count() }}
            </td>
            <td>
                {{ $completed }}
            </td>
            <td>
                {{ ($provider->trips->count() > 0 && $completed > 0)? number_format((100*$completed)/$provider->trips->count(), config('round_up_to_digit')): 0 }}%
            </td>
            <td>
                {{ ($provider->trips->count() > 0 && $completed > 0)? number_format((100*($provider->trips->count()-($completed+$canceled)))/$provider->trips->count(), config('round_up_to_digit')): 0 }}%
            </td>
            <td>
                {{ ($provider->trips->count() > 0 && $canceled > 0)? number_format((100*$canceled)/$provider->trips->count(), config('round_up_to_digit')): 0 }}%
            </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
