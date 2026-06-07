<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('provider_trip_reports') }}</h1></div>
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
                    {{ translate('total_trips')  }}- {{ $data['total_trips'] }}
                    <br>
                    {{ translate('total_trip_amount')  }}- {{ $data['total_trip_amount'] }}
                    <br>
                    {{ translate('canceled_trip')  }}- {{ $data['total_canceled_count'] }}
                    <br>
                    {{ translate('completed_trips')  }}- {{ $data['total_completed_count'] }}
                    <br>
                    {{ translate('incomplete_trips')  }}- {{ $data['total_ongoing_count'] }}
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('messages.trip_id') }}</th>
            <th>{{ translate('messages.trip_booking_date') }}</th>
            <th>{{ translate('messages.trip_schedule_date') }}</th>
            <th>{{ translate('messages.customer_name') }}</th>
            <th>{{ translate('messages.provider_name') }}</th>
            <th>{{ translate('messages.Total Trip Amount') }}</th>
            <th>{{ translate('messages.payment_status') }}</th>
            <th>{{ translate('messages.discounted_amount') }}</th>
            <th>{{ translate('messages.tax') }}</th>
        </thead>
        <tbody>
            @foreach($data['trips'] as $key => $trip)
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $trip->id }}</td>
                <td><div>
                    {{ date('d M Y', strtotime($trip['created_at'])) }}
                </div>
                <br>
                <div>
                    {{ date(config('timeformat'), strtotime($trip['created_at'])) }}
                </div></td>
                <td><div>
                    {{ date('d M Y', strtotime($trip['schedule_at'])) }}
                </div>
                <br>
                <div>
                    {{ date(config('timeformat'), strtotime($trip['schedule_at'])) }}
                </div></td>
                <td>
                    @if ($trip->customer)
                        {{ $trip->customer['f_name'] . ' ' . $trip->customer['l_name'] }}
                    @else
                        {{ translate('not_found') }}
                    @endif
                </td>
                <td>
                    @if($trip->provider)
                        {{$trip->provider->name}}
                    @else
                        {{ translate('messages.not_found') }}
                    @endif
                </td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['trip_amount']) }}</td>
                <td>{{ translate($trip->payment_status) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['coupon_discount_amount']  + $trip['ref_bonus_amount'] +  $trip['discount_on_trip']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['tax_amount']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
