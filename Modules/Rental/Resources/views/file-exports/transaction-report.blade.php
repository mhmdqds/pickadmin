<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('trip_transactions_report') }}</h1></div>
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
                <th>{{ translate('Transaction_Analytics') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('Total_completed_amount')  }} - {{ $data['totalAmount'] ??translate('N/A') }}
                    <br>
                    {{ translate('Admin_Earnings')  }} - {{ $data['adminEarned'] ??translate('N/A') }}
                    <br>
                    {{ translate('Provider_Earnings')  }} - {{ $data['providerEarned'] ??translate('N/A') }}
                    <br>
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
        <tr>
            <th class="border-0">{{ translate('sl') }}</th>
            <th class="border-0">{{ translate('messages.trip_id') }}</th>
            <th class="border-0">{{ translate('messages.provider') }}</th>
            <th class="border-0">{{ translate('messages.customer') }}</th>
            <th class="border-0 min-w-120">{{ translate('messages.Total_Trip_Amount') }}</th>
            <th class="border-0">{{ translate('messages.Discount on Vehicle') }}</th>
            <th class="border-0">{{ translate('messages.Coupon_Discount') }}</th>
            <th class="border-0">{{ translate('messages.Referral_Discount') }}</th>
            <th class="border-0">{{ translate('messages.Total Discounted Amount') }}</th>
            <th class="border-0">{{ translate('messages.vat/tax') }}</th>
            <th class="border-0">{{ translate('messages.Admin_Commission') }}</th>
            <th class="border-0">
                {{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name') ?? translate('messages.additional_charge') }}
            </th>
            <th class="border-0">{{ translate('messages.Admin_Discount') }}</th>
            <th class="min-w-140 text-capitalize">{{ translate('Admin_net_income') }}</th>
            <th class="border-0 text-capitalize">{{ translate('messages.provider_discount') }}</th>
            <th class="min-w-140 text-capitalize">{{ translate('provider_net_income') }}</th>
            <th class="border-0 min-w-120 text-capitalize">{{ translate('messages.Total Amount Received by') }}</th>
            <th class="border-top border-bottom text-capitalize">
                {{ translate('messages.payment_method') }}</th>
            <th class="border-0 text-capitalize">{{ translate('messages.payment_status') }}</th>
        </thead>
        <tbody>
        @foreach($data['tripTransactions'] as $key => $ot)
            <tr>
                <td>{{ $key+1}}</td>
                <td>{{ $ot->trip_id }}</td>
                <td >
                    {{Str::limit($ot->trip?->provider?->name ?? translate('messages.Not Found'),25,'...')}}
                </td>
                <td>
                    @if ($ot->trip->customer)
                    <strong>{{ $ot->trip->customer['f_name'] . ' ' . $ot->trip->customer['l_name'] }}</strong>

                @elseif($ot->trip?->user_info['contact_person_name'])
                <strong>{{ $ot->trip?->user_info['contact_person_name'] }}</strong>

                @else
                    {{ translate('messages.Guest_user') }}
                @endif
                </td>
                {{--total_trip_amount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->trip_amount) }}</td>

                {{--vehicle_discount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->trip->discount_on_trip) }}</td>

                {{--coupon_discount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->trip['coupon_discount_amount']) }}</td>
                {{--referral_discount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->trip['ref_bonus_amount']) }}</td>
                {{--discounted_amount --}}
                <td>  {{ \App\CentralLogics\Helpers::format_currency($ot->trip['coupon_discount_amount'] + $ot->trip['ref_bonus_amount'] + $ot->trip->discount_on_trip) }}</td>

                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->tax) }}</td>

                {{--admin_commission --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->admin_commission) }}</td>


                <td>{{ \App\CentralLogics\Helpers::format_currency(($ot->additional_charge)) }}</td>
                {{--admin_discount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->admin_expense) }}</td>


                {{--admin_net_income --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency(($ot->admin_net_income)) }}</td>
                {{--store_discount --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->store_expense) }}</td>
                {{--store_net_income --}}
                <td>{{ \App\CentralLogics\Helpers::format_currency($ot->store_amount - $ot->tax) }}</td>
                @if ($ot->received_by == 'admin')
                    <td>{{ translate('messages.admin') }}</td>
                @elseif ($ot->received_by == 'vendor')
                    <td>{{ translate('messages.provider') }}</td>
                @endif
                <td>
                    {{ translate(str_replace('_', ' ', $ot->trip['payment_method'])) }}
                </td>
                <td>
                    {{translate('messages.completed')}}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
