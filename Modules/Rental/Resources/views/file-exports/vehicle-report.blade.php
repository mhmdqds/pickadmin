<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('vehicle_report') }}</h1></div>
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
            <th>{{ translate('sl') }}</th>
            <th>{{translate('messages.vehicle_image')}}</th>
            <th>{{translate('messages.Vehicle Info')}}</th>
            <th>{{translate('messages.provider')}}</th>
            <th>{{translate('messages.hourly_rate')}}</th>
            <th>{{translate('messages.distance_wise_rate')}}</th>
            <th>{{translate('messages.total_trip_count')}}</th>
            <th>{{translate('messages.total_trip_vehicles')}}</th>
            <th>{{translate('messages.total_trip_amount')}}</th>
            <th>{{translate('messages.total_discount_given')}}</th>
            <th>{{translate('messages.Average Trip Value')}}</th>
            <th>{{translate('messages.total_reviews')}}</th>
            <th>{{translate('messages.average_ratings')}}</th>
        </thead>
        <tbody>
        @foreach($data['vehicles'] as $key => $vehicle)
            <tr>
                <td>{{ $key+1}}</td>
                <td></td>
                <td>{{$vehicle['name']}}</td>
                <td>
                    @if($vehicle->provider)
                    {{ $vehicle->provider->name }}
                    @else
                    {{translate('messages.provider_deleted')}}
                    @endif
                </td>
                <td>
                    {{ \App\CentralLogics\Helpers::format_currency($vehicle->hourly_price) }}
                </td>
                <td>
                    {{ \App\CentralLogics\Helpers::format_currency($vehicle->distance_price) }}
                </td>
                <td>
                    {{$vehicle->trips_count ?? 0}}
                </td>
                <td>
                    {{$vehicle->trip_details_sum_quantity ?? 0}}
                </td>

                <td>
                    {{ \App\CentralLogics\Helpers::format_currency($vehicle->trips_sum_price) }}
                </td>
                <td>
                    {{ \App\CentralLogics\Helpers::format_currency($vehicle->total_discount) }}
                <td>
                    {{ $vehicle->trips_count>0? \App\CentralLogics\Helpers::format_currency(($vehicle->trips_sum_price-$vehicle->total_discount)/($vehicle->trip_details_sum_quantity ?? 0) ) :0 }}
                </td>
                <td>{{ $vehicle->total_reviews }}</td>
                <td>{{ round($vehicle->avg_rating,1) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
