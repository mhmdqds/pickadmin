
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Driver_Trip_List')}}
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
                <th>{{ translate('Customer') }}</th>
                <th>{{ translate('Provider') }}</th>
                <th>{{ translate('Trip Amount') }}</th>
                <th>{{ translate('Discount On Trip') }}</th>
                <th>{{ translate('Coupon Discount Amount') }}</th>
                <th>{{ translate('Trip Status') }}</th>
                <th>{{ translate('Payment Status') }}</th>
                <th>{{ translate('Tax Amount') }}</th>

            </thead>
            <tbody>
            @foreach($data['data'] as $key => $driverTrip)
                <tr>
                    <td>{{ $loop->index+1}}</td>
                    <td>{{ $driverTrip?->trip?->customer?->fullName ?? translate('messages.Guest_user') }}</td>
                    <td>{{ $driverTrip?->trip?->provider->name  }}</td>
                    <td>{{ $driverTrip?->trip?->trip_amount }}</td>
                    <td>{{ $driverTrip?->trip?->discount_on_trip }}</td>
                    <td>{{ $driverTrip?->trip?->coupon_discount_amount }}</td>
                    <td>{{ ucwords($driverTrip?->trip?->trip_status) }}</td>
                    <td>{{ ucwords($driverTrip?->trip?->payment_status) }}</td>
                    <td>{{ $driverTrip?->trip?->tax_amount }}</td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
