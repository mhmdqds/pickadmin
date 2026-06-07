<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('messages.trip_report') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('filter_criteria') }} -</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('zone' )}} - {{ $data['zone']??translate('all') }}
                    <br>
                    {{ translate('provider' )}} - {{ $data['provider']??translate('all') }}
                    <br>
                    {{ translate('customer' )}} - {{ $data['customer']??translate('all') }}
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
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            <tr>
                <th>{{ translate('messages.sl') }}</th>
                <th>{{ translate('messages.trip_id') }}</th>
                <th>{{ translate('messages.Customer info') }}</th>
                <th>{{ translate('messages.provider_name') }}</th>
                <th>{{ translate('messages.Total Fare of Vehicle') }}</th>
                <th>{{ translate('messages.Discount on Vehicle') }}</th>
                <th>{{ translate('messages.coupon_discount') }}</th>
                <th>{{ translate('messages.referral_discount') }}</th>
                <th>{{ translate('messages.Total_discounted_amount') }}</th>
                <th>{{  \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge')  }}</th>
                <th>{{ translate('messages.tax') }}</th>
                <th>{{ translate('messages.total_amount') }}</th>
                <th>{{ translate('messages.payment_status') }}</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data['trips'] as $key => $trip)
            <tr>
                <td>{{ $key+1 }}</td>
                <td>{{ $trip->id }}</td>
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
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['trip_amount'] - $trip->additional_charge-$trip['tax_amount']+$trip['coupon_discount_amount'] + $trip['discount_on_trip'] + $trip['ref_bonus_amount'] ) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short( $trip['discount_on_trip'] ) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['coupon_discount_amount']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['ref_bonus_amount']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['coupon_discount_amount'] + $trip['discount_on_trip'] + $trip['ref_bonus_amount'] ) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['additional_charge']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['tax_amount']) }}</td>
                <td>{{ \App\CentralLogics\Helpers::number_format_short($trip['trip_amount']) }}</td>
                <td>{{ translate($trip->payment_status) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
