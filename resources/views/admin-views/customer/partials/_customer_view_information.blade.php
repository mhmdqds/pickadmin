@if($customer)
    <div class="card-body">
        <div class="media gap-3 flex-wrap">
            <div class="avatar avatar-circle avatar-70">
                <img class="avatar-img onerror-image" width="70" height="70" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}" src="{{ $customer->image_full_url }}"
                alt="Image Description">
            </div>
            <div class="media-body">
                <div class="key-value-list d-flex flex-column gap-2 text-dark" style="--min-width: 60px">
                    <div class="key-val-list-item d-flex gap-3">
                        <div>{{ translate('name') }}</div>:
                        <div class="font-semibold">{{$customer['f_name']? $customer['f_name'].' '.$customer['l_name'] : translate('messages.Incomplete_Profile')}}</div>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <div>{{ translate('contact') }}</div>:
                        <a href="tel:{{ $customer['phone'] }}" class="text-dark font-semibold">{{$customer['phone'] ?? translate('messages.N/A')}}</a>
                    </div>
                    <div class="key-val-list-item d-flex gap-3">
                        <div>{{ translate('email') }}</div>:
                        <a href="mailto:{{ $customer['email'] }}" class="text-dark font-semibold">{{$customer['email'] ?? translate('messages.N/A')}}</a>
                    </div>
                    @foreach($customer->addresses as $address)
                        <div class="key-val-list-item d-flex gap-3">
                            <div>{{ translate('address') }}</div>:
                            <a href="https://www.google.com/maps/search/?api=1&query={{ data_get($address,'latitude',0)}},{{ data_get($address,'longitude',0)}}" target="_blank">{{ $address['address'] }}</a>
                        </div>
                    @endforeach
                </div>

                {{-- <ul class="list-unstyled m-0">
                    <li class="pb-1 d-flex align-items-center">
                        <i class="tio-shopping-basket-outlined mr-2"></i>
                        <span>{{$customer->order_count}} {{translate('messages.Completed_orders')}}</span>
                    </li>
                </ul> --}}
            </div>
        </div>


        {{-- @foreach($customer->addresses as $address)
            <div class="d-flex justify-content-between align-items-center">
                <h5>{{translate('messages.addresses')}}</h5>
            </div>
            <ul class="list-unstyled list-unstyled-py-2">
                <li class="d-flex align-items-center">
                    <i class="tio-tab mr-2"></i>
                    <span>{{translate($address['address_type'])}}</span>
                </li>
                @if($address['contact_person_umber'])
                <li class="d-flex align-items-center">
                    <i class="tio-android-phone-vs mr-2"></i>
                    <span>{{$address['contact_person_number']}}</span>
                </li>
                @endif
                <li>
                    <a target="_blank" href="http://maps.google.com/maps?z=12&t=m&q=loc:{{$address['latitude']}}+{{$address['longitude']}}" class="d-flex align-items-center">
                        <i class="tio-poi mr-2"></i>
                        {{$address['address']}}
                    </a>
                </li>
            </ul>
            <hr>
        @endforeach --}}

    </div>
@endif