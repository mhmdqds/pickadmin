<div class="row">
    <div class="col-lg-12 text-center "><h1 >{{ translate('delivery_man_review_list') }}</h1></div>
    <div class="col-lg-12">



    <table>
        <thead>
            <tr>
                <th>{{ translate('Search_Criteria') }}

                    @isset($data['delivery_men'])
                         <br>
                        {{ translate('delivery_man')  }}- {{ $data['delivery_men']}}
                    @endisset

                    @isset($data['order_by'])
                        <br>
                        {{ translate('order_by')  }}- {{ $data['order_by']}}

                    @endisset
                </th>
                <th></th>
                <th>
                    {{-- @dd($data['delivery_men']) --}}



                </th>
                <th>
                    {{ translate('Search_Bar_Content')  }}- {{ $data['search'] ??translate('N/A') }}

                </th>
                <th> </th>
                <th></th>
                <th></th>
                <th></th>
                </tr>
        <tr>
            <th>{{ translate('sl') }}</th>
            <th>{{translate('messages.delivery_man_name')}}</th>
            <th>{{translate('messages.order_id')}}</th>
            <th>{{translate('messages.customer_name')}}</th>
            <th>{{translate('messages.store_name')}}</th>
            <th>{{translate('messages.rating')}}</th>
            <th>{{translate('messages.review')}}</th>
        </thead>
        <tbody>
        @foreach($data['reviews'] as $key => $review)
            <tr>
                <td>{{ $key+1}}</td>
                <td>{{$review->delivery_man->f_name.' '.$review->delivery_man->l_name}}</td>
                <td>
                    {{ $review->order_id }}
                </td>
                <td>
                    @if ($review->customer)
                        {{$review->customer?$review->customer->f_name:""}} {{$review->customer?$review->customer->l_name:""}}
                    @else
                        {{translate('messages.customer_not_found')}}
                    @endif
                </td>
                <td>
                    {{$review->order?->store?->name}}
                </td>
                <td>{{ $review->rating }}</td>
                <td>{{ $review->comment }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
