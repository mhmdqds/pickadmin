
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate($data['fileName'] ? $data['fileName'] : 'Trip_List')}}
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
{{--                @if(!$data['providerId'])--}}
                    <th>{{ translate('Provider') }}</th>
{{--                @endif--}}
                <th>{{ translate('Trip Amount') }}</th>
                <th>{{ translate('Discount On Trip') }}</th>
                <th>{{ translate('Coupon Discount Amount') }}</th>
                <th>{{ translate('Trip Status') }}</th>
                <th>{{ translate('Payment Status') }}</th>
                <th>{{ translate('Tax Amount') }}</th>

            </thead>
            <tbody>
            @foreach($data['data'] as $key => $trip)
                <tr>
                    <td>{{ $loop->index+1}}</td>
                    <td>{{ $trip?->customer?->fullName ?? translate('messages.Guest_user') }}</td>
{{--                    @if(!$data['providerId'])--}}
                        <td>{{ $trip->provider->name  }}</td>
{{--                    @endif--}}
                    <td>{{ $trip->trip_amount }}</td>
                    <td>{{ $trip?->discount_on_trip }}</td>
                    <td>{{ $trip?->coupon_discount_amount }}</td>
                    <td>{{ ucwords($trip?->trip_status) }}</td>
                    <td>{{ ucwords($trip?->payment_status) }}</td>
                    <td>{{ $trip?->tax_amount }}</td>

                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
