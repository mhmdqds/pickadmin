    <!-- Page Header -->
    <div class="page-header pb-0">
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ translate('messages.Provider_Details') }}
                    </h1></span>
                    </h1>
                </div>
                @if(!request()->tab)
                    <div class="d-flex align-items-start flex-wrap gap-2">
                        <a href="javascript:" class="btn btn--reset d-flex justify-content-between align-items-center gap-4 lh--1 h--45px">
                            {{ translate('messages.status') }}
                            <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$store->id}}">
                                <input type="checkbox" data-url="{{route('admin.store.status',[$store['id'],$store->status?0:1])}}"
                                       class="toggle-switch-input redirect-url" id="stocksCheckbox{{$store->id}}" {{$store->status?'checked':''}}>
                                <span class="toggle-switch-label">
                                    <span class="toggle-switch-indicator"></span>
                                </span>
                            </label>
                        </a>
                        <a href="{{ route('admin.rental.provider.edit-basic-setup', $store->id)}}" class="btn btn--primary font-weight-bold float-right mr-2 mb-0">
                            <i class="tio-edit"></i> {{ translate('messages.edit_provider') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
        @if($store->vendor->status)
        <!-- Nav Scroller -->
        <div class="js-nav-scroller hs-nav-scroller-horizontal">
            <span class="hs-nav-scroller-arrow-prev d-none">
                <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                    <i class="tio-chevron-left"></i>
            </a>
            </span>

            <span class="hs-nav-scroller-arrow-next d-none">
                <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                    <i class="tio-chevron-right"></i>
                </a>
            </span>

            <!-- Nav -->
            <ul class="nav nav-tabs page-header-tabs mb-2">
                <li class="nav-item">
                    <a class="nav-link {{request('tab')==null?'active':''}}" href="{{route('admin.rental.provider.details', $store->id)}}">{{translate('messages.overview')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='order'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'order'])}}"  aria-disabled="true">{{translate('messages.Trip List')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='driver'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'driver'])}}"  aria-disabled="true">{{translate('messages.driver list')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='vehicle'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'vehicle'])}}"  aria-disabled="true">{{translate('messages.Vehicles')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='reviews'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'reviews'])}}"  aria-disabled="true">{{translate('messages.reviews')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='discount'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'discount'])}}"  aria-disabled="true">{{translate('messages.discounts')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='transaction'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'transaction'])}}"  aria-disabled="true">{{translate('messages.transactions')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='settings'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'settings'])}}"  aria-disabled="true">{{translate('messages.settings')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='conversations'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'conversations'])}}"  aria-disabled="true">{{translate('Conversations')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{request('tab')=='meta-data'?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'meta-data'])}}"  aria-disabled="true">{{translate('meta_data')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link  {{request('tab')=='disbursements' ?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'disbursements'])}}"  aria-disabled="true">{{translate('messages.disbursements')}}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link  {{request('tab')=='business_plan' ?'active':''}}" href="{{route('admin.rental.provider.details', ['id'=>$store->id, 'tab'=> 'business_plan'])}}"  aria-disabled="true">{{translate('messages.business_plan')}}</a>
                </li>
            </ul>
            <!-- End Nav -->
        </div>
        <!-- End Nav Scroller -->
        @endif
    </div>
    <!-- End Page Header -->
