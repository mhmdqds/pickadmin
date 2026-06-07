
<div class="row">
    <div class="col-lg-12 text-center "><h1 > {{translate('Coupon_List')}}
    </h1></div>
    <div class="col-lg-12">

    <table>
        <thead>
            <tr>
                <th>{{ translate('Search_Criteria') }}</th>
                <th></th>
                <th></th>
                <th>
                    {{ translate('Search_Bar_Content')  }}: {{ $data['search'] ??translate('N/A') }}
                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
                </tr>


        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{ translate('Coupon_Title') }}</th>
            <th>{{ translate('Coupon_Code') }}</th>
            <th>{{ translate('Module') }}</th>
            <th>{{ translate('Coupon_Type') }}</th>
            <th>{{ translate('Number_of_Uses') }}</th>
            <th>{{ translate('Min_Purchase_Amount') }}</th>
            <th>{{ translate('Max_Discount_Amount') }} </th>
            <th>{{ translate('Discount_Type') }} </th>
            <th>{{ translate('Discount') }} </th>
            <th>{{ translate('Start_Date') }} </th>
            <th>{{ translate('End_Date') }} </th>
        </thead>
        <tbody>
        @foreach($data['data'] as $key => $coupon)
            <tr>
        <td>{{ $loop->index+1}}</td>
        <td>{{ $coupon->title }}</td>
        <td>{{ $coupon->code }}</td>
        <td>{{ translate($coupon->module->module_name) }}</td>
        @if ( $coupon->coupon_type == 'store_wise')
        <td>{{translate('messages.Provider_wise')}}</td>
        @elseif($coupon->coupon_type == 'first_order')
        <td>{{translate('messages.first_trip')}}</td>
        @else
        <td>{{translate('messages.'.$coupon->coupon_type)}}</td>
        @endif
        <td>{{ $coupon->total_uses }}</td>
        <td>{{ $coupon->min_purchase }}</td>
        <td>{{ $coupon->max_discount }}</td>
        <td>{{ $coupon->discount }}</td>
        <td>{{ translate($coupon->discount_type) }}</td>
        <td>{{ \Carbon\Carbon::parse($coupon->start_date)->format('d M Y') }}</td>
        <td>{{ \Carbon\Carbon::parse($coupon->expire_date)->format('d M Y') }}</td>

            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
