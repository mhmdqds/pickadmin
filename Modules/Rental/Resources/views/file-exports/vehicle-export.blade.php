
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Vehicle_List')}}
    </h1></div>
    <div class="col-lg-12">

    <table>
        <thead>
            <tr>
                <th>{{ translate('Filter_Criteria') }}</th>
                <th></th>
                <th>
                    {{ translate('Search_Bar_Content')  }}: {{ $data['search'] ?? translate('N/A') }}

                </th>
                <th> </th>
                </tr>


        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('Vehicle_Name') }}</th>
            <th>{{ translate('Vehicle_ID') }}</th>
            <th>{{ translate('Vehicle_Category') }}</th>
            <th>{{ translate('Vehicle_Brand') }}</th>
            <th>{{ translate('Trip Fair') }}</th>
            <th>{{ translate('New Tag') }}</th>
            <th>{{ translate('Status') }}</th>

        </thead>
        <tbody>
        @foreach($data['data'] as $key => $vehicle)
            <tr>
                <td>{{ $loop->index+1}}</td>
                <td>{{ $vehicle->name }}</td>
                <td>{{ $vehicle->id }}</td>
                <td>{{ $vehicle?->category?->name }}</td>
                <td>{{ $vehicle?->brand?->name }}</td>
                <td>
                    @if($vehicle->trip_hourly)
                        <div>
                            <span class="opacity-lg">{{translate('Hourly')}}: </span>
                            <span class="font-semibold">{{\App\CentralLogics\Helpers::format_currency($vehicle['hourly_price'])}}</span>
                        </div>
                    @endif
                    @if($vehicle->trip_distance)
                        <div>
                            <span class="opacity-lg">{{translate('Distance Wise')}}: </span>
                            <span class="font-semibold">{{\App\CentralLogics\Helpers::format_currency($vehicle['distance_price'])}}</span>
                        </div>
                    @endif
                </td>
                <td>{{ $vehicle->new_tag == 1 ? translate('messages.Yes') : translate('messages.No')  }}</td>
                <td>{{ $vehicle->status == 1 ? translate('messages.Active') : translate('messages.Inactive')  }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
