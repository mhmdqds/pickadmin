@extends('layouts.admin.app')

@section('title',translate('Store List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        @php
            $verified_seller_badge = \App\CentralLogics\Helpers::get_business_settings('verified_seller_badge');
            $recommended_store_list = $verified_seller_badge ? \App\CentralLogics\Helpers::get_verified_seller_eligible_stores(countOnly: false , moduleId: config('module.current_module_id')) : [];
            $recommended_stores = count($recommended_store_list) ;
        @endphp
        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <div>
                <h1 class="page-header-title"><i class="tio-filter-list"></i> {{translate('messages.stores')}} <span class="badge badge-soft-dark ml-2" id="itemCount">{{$stores->total()}}</span></h1>
                <div class="page-header-select-wrapper">
                </div>
            </div>
            @if ($recommended_stores??0 > 0)
            <div class="d-flex align-items-center gap-2 bg-success bg-opacity-10 flex-wrap rounded py-1 px-2">
                    <div class="fs-12 mb-0 d-flex align-items-center gap-2">
                        <img src="{{ asset('public/assets/admin/img/badge-rounded-circle.svg') }}" alt="" class="rounded-0 w-auto h-auto object-contain">
                        {{ translate('Recommended') }} <strong class="title-clr">{{$recommended_stores}}</strong> {{ translate('stores for verification') }}
                    </div>

                <button class="btn btn--primary bg-theme2 border-0 py-1 px-3 fs-12 fw-500 mb-0 offcanvas-trigger  " data-target="#offcanvas__customBtn3" data-id="0"  data-url="" type="button">
                    {{ translate('messages.View') }}
                </button>
            </div>
            @endif
        </div>
        <!-- End Page Header -->


        <!-- Resturent Card Wrapper -->
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-1">
                    <h4 class="title">{{$total_store}}</h4>
                    <span class="subtitle">{{translate('messages.total_stores')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/total-store.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-2">
                    <h4 class="title">{{$active_stores}}</h4>
                    <span class="subtitle">{{translate('messages.active_stores')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/active-store.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-3">
                    <h4 class="title">{{$inactive_stores}}</h4>
                    <span class="subtitle">{{translate('messages.inactive_stores')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/close-store.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-4">
                    <h4 class="title">{{$recent_stores}}</h4>
                    <span class="subtitle">{{translate('messages.newly_joined_stores')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/add-store.png')}}" alt="store">
                </div>
            </div>
        </div>
        <!-- Resturent Card Wrapper -->
        <!-- Transaction Information -->
        <ul class="transaction--information text-uppercase">
            <li class="text--info">
                <i class="tio-document-text-outlined"></i>
                <div>
                    <span>{{translate('messages.total_transactions')}}</span> <strong>{{$total_transaction}}</strong>
                </div>
            </li>

            @if (auth('admin')->user()->role_id == 1)
                <li class="seperator"></li>
                <li class="text--success">
                    <i class="tio-checkmark-circle-outlined success--icon"></i>
                    <div>
                        <span>{{translate('messages.commission_earned')}}</span> <strong>{{\App\CentralLogics\Helpers::format_currency($comission_earned)}}</strong>
                    </div>
                </li>
            @endif

            <li class="seperator"></li>
            <li class="text--danger">
                <i class="tio-atm"></i>
                <div>
                    <span>{{translate('messages.total_store_withdraws')}}</span> <strong>{{\App\CentralLogics\Helpers::format_currency($store_withdraws)}}</strong>
                </div>
            </li>
        </ul>
        <!-- Transaction Information -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.stores_list')}}</h5>

                @if(!isset(auth('admin')->user()->zone_id))
                <div class="select-item min--280">
                    <select name="zone_id" class="form-control js-select2-custom set-filter" data-url="{{url()->full()}}" data-filter="zone_id">
                        <option value="" {{!request('zone_id')?'selected':''}}>{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get(['id','name']) as $z)
                            <option
                                value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                {{$z['name']}}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                    <form class="search-form">
                                    <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}" name="search" class="form-control"
                                    placeholder="{{translate('ex_:_Search_Store_Name')}}" aria-label="{{translate('messages.search')}}" >
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                    @if(request()->get('search'))
                    <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif


                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                            data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.store.export', ['type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.store.export', ['type'=>'csv',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                {{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "order": [],
                            "orderCellsTop": true,
                            "paging":false

                        }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('sl')}}</th>
                        <th class="border-0">{{translate('messages.store_information')}}</th>
                        <th class="border-0">{{translate('messages.owner_information')}}</th>
                        <th class="border-0">{{translate('messages.zone')}}</th>
                        <th class="text-uppercase border-0">{{translate('messages.featured')}}</th>
                        <th class="text-uppercase border-0">{{translate('messages.status')}}</th>
                        <th class="text-center border-0">{{translate('messages.action')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($stores as $key=>$store)
                        <tr>
                            <td>{{$key+$stores->firstItem()}}</td>
                            <td>
                                <div>
                                    <a href="{{route('admin.store.view', $store->id)}}" class="table-rest-info" alt="view store">
                                        <img class="img--60 circle onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                                src="{{ $store['logo_full_url'] ?? asset('public/assets/admin/img/160x160/img1.jpg') }}">
                                        <div class="info max-w-200px">
                                            <div title="{{ $store?->name }}" class="text--title ">
                                                {{Str::limit($store->name,20,'...')}}
                                                @if ($verified_seller_badge == 1 && $store->storeConfig?->verified_seller)
                                                    <img src="{{ asset('public/assets/admin/img/checked-badge.svg') }}" alt="" class="rounded-0 w-auto h-auto object-contain">
                                                @endif
                                            </div>
                                            <div class="font-light">
                                                {{translate('messages.id')}}:{{$store->id}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>

                            <td>
                                <span title="{{ $store?->vendor?->f_name.' '.$store?->vendor?->l_name }}" class="d-block font-size-sm text-body">
                                    {{Str::limit($store->vendor->f_name.' '.$store->vendor->l_name,20,'...')}}
                                </span>
                                <div>
                                    <a href="tel:{{ $store['phone'] }}">
                                        {{$store['phone']}}
                                    </a>
                                </div>
                            </td>
                            <td>
                                {{$store->zone?$store->zone->name:translate('messages.zone_deleted')}}
                            </td>
                            <td>
                                <label class="toggle-switch toggle-switch-sm" for="featuredCheckbox{{$store->id}}">
                                    <input type="checkbox" data-url="{{route('admin.store.featured',[$store->id,$store->featured?0:1])}}" class="toggle-switch-input redirect-url" id="featuredCheckbox{{$store->id}}" {{$store->featured?'checked':''}}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </td>

                            <td>
                                @if(isset($store->vendor->status))
                                    @if($store->vendor->status)
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$store->id}}">
                                        <input type="checkbox" data-url="{{route('admin.store.status',[$store->id,$store->status?0:1])}}" data-message="{{translate('messages.you_want_to_change_this_store_status')}}" class="toggle-switch-input status_change_alert" id="stocksCheckbox{{$store->id}}" {{$store->status?'checked':''}}>
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                    @else
                                    <span class="badge badge-soft-danger">{{translate('messages.denied')}}</span>
                                    @endif
                                @else
                                    <span class="badge badge-soft-danger">{{translate('messages.pending')}}</span>
                                @endif
                            </td>

                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--warning btn-outline-warning"
                                            href="{{route('admin.store.view', $store->id)}}"
                                            title="{{ translate('messages.view') }}"><i
                                                class="tio-visible-outlined"></i>
                                        </a>
                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                    href="{{route('admin.store.edit',[$store['id']])}}" title="{{translate('messages.edit_store')}}"><i class="tio-edit"></i>
                                    </a>
                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                    data-id="vendor-{{$store['id']}}" data-message="{{translate('You want to remove this store')}}" title="{{translate('messages.delete_store')}}"><i class="tio-delete-outlined"></i>
                                    </a>
                                    <form action="{{route('admin.store.delete',[$store['id']])}}" method="post" id="vendor-{{$store['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
                @if(count($stores) !== 0)
                <hr>
                @endif
                <div class="page-area">
                    {!! $stores->withQueryString()->links() !!}
                </div>
                @if(count($stores) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>



    <div id="offcanvas__customBtn3" class="custom-offcanvas d-flex flex-column justify-content-between">
        <div class="d-flex flex-column flex-grow-1">
            <div class="custom-offcanvas-header bg-white d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                <h3 class="mb-0 fs-18 text-title fw-semibold">{{ translate('Verification Recommendations') }}</h3>
                <button type="button"
                    class="btn-close w-25px h-25px border rounded-circle d-center bg-white text-dark offcanvas-close fz-15px p-0"
                    aria-label="Close">&times;</button>
            </div>
            <div class="custom-offcanvas-body p-4 d-flex flex-column gap-3">
                <p class="fs-14 lh-base color-5d6167 mb-0">
                    {{ translate('We have detected that') }} <strong>{{ number_format($recommended_stores) }}</strong>
                    {{ translate('stores have GOOD performance based on their overall activity. You can give them a Verified badge, which will appear next to the store name to build customer trust.') }}
                </p>

                <div class="bg--secondary rounded p-4">
                    <h4 class="mb-3 fs-18 text-title fw-semibold">{{ translate('They qualified the criteria') }}</h4>

                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-center flex-shrink-0 mt-1 w-18px h-18px rounded-circle bg-success">
                                <i class="tio-done text-white fz-10px"></i>
                            </span>
                            <span class="fs-14 color-5d6167">
                                <strong class="text-title">{{ config('verified_seller.stores.minimum_total_orders', 10) }}+</strong> {{ translate('Orders completed') }}
                            </span>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-center flex-shrink-0 mt-1 w-18px h-18px rounded-circle bg-success">
                                <i class="tio-done text-white fz-10px"></i>
                            </span>
                            <span class="fs-14 color-5d6167">
                                {{ translate('Order completion rate above') }} <strong class="text-title">{{ config('verified_seller.stores.minimum_success_rate', 40) }}%</strong>
                            </span>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-center flex-shrink-0 mt-1 w-18px h-18px rounded-circle bg-success">
                                <i class="tio-done text-white fz-10px"></i>
                            </span>
                            <span class="fs-14 color-5d6167">
                                <strong class="text-title">{{ config('verified_seller.stores.minimum_account_age_months', 3) }}+</strong> {{ translate('months since account creation') }}
                            </span>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-center flex-shrink-0 mt-1 w-18px h-18px rounded-circle bg-success">
                                <i class="tio-done text-white fz-10px"></i>
                            </span>
                            <span class="fs-14 color-5d6167">
                                <strong class="text-title">{{ translate('Positive') }}</strong> {{ translate('customer feedback trend') }}
                            </span>
                        </div>
                        <div class="d-flex align-items-start gap-2">
                            <span class="d-center flex-shrink-0 mt-1 w-18px h-18px rounded-circle bg-success">
                                <i class="tio-done text-white fz-10px"></i>
                            </span>
                            <span class="fs-14 color-5d6167">
                                <strong class="text-title">{{ config('verified_seller.stores.minimum_avg_rating', 2) }}+</strong> {{ translate('Rating') }}/5.00
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="align-items-center bg-white bottom-0 d-flex gap-3 justify-content-center mt-auto offcanvas-footer p-3 position-sticky border-top">
            <button type="button" id="open-eligible-store-list" class="btn w-100 btn--reset h--40px">{{ translate('View List') }}</button>
            <button type="button" id="verify-all-summary" class="btn w-100 btn--primary h--40px">{{ translate('Verify All') }}</button>
        </div>
    </div>
    <div id="offcanvas__eligibleStores" class="custom-offcanvas d-flex flex-column justify-content-between">
        <div class="d-flex flex-column flex-grow-1">
            <div class="custom-offcanvas-header bg-white d-flex justify-content-between align-items-center px-4 py-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="mb-0 fs-18 text-title fw-semibold d-flex align-items-center gap-2">
                        <span id="back-to-verification" class="d-inline-flex align-items-center justify-content-center w-20px h-20px rounded-circle bg-light">
                            <i class="tio-arrow-backward fs-12"></i>
                        </span>
                        {{ translate('Stores List') }}
                    </h3>
                    <span class="badge badge-soft-dark">{{ $recommended_stores }}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <label class="d-flex align-items-center gap-2 mb-0 cursor-pointer">
                        <input type="checkbox" id="select-all-eligible-stores" class="form-check-input mt-0">
                        <span class="fs-14 text-title">{{ translate('Select All') }}</span>
                    </label>
                    <button type="button" class="btn-close w-25px h-25px border rounded-circle d-center bg-white text-dark offcanvas-close fz-15px p-0" aria-label="Close">&times;</button>
                </div>
            </div>
            <div class="custom-offcanvas-body p-3 p-md-4 d-flex flex-column gap-3" id="eligible-store-list">
                @foreach ($recommended_store_list as $store)
                    <div class="bg-white rounded-10 p-3 eligible-store-item">
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <div class="d-flex align-items-center gap-3">
                                <div class="flex-shrink-0">
                                    <img class="w-50px h-50px rounded-circle border object-fit-cover onerror-image"
                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                         src="{{$store['logo_full_url']}}">
                                </div>
                                <div>
                                    <h4 class="mb-1 fs-16 text-title fw-semibold">{{ $store['name'] }}</h4>
                                    <div class="d-flex flex-wrap gap-2 fs-13 color-6c757d">
                                        <span>{{ translate('messages.Rating') }} {{ number_format($store['avg_rating'], 1) }}/5</span>
                                        <span>{{ translate('messages.Order') }} {{ number_format($store['total_orders']) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="eligible-action-wrapper d-flex align-items-center justify-content-end min-w-110px">
                                <a href="{{ route('admin.store.verified-seller', [$store['id']]) }}" class="btn btn--primary btn-sm min-w-110px eligible-give-btn">
                                    <i class="tio-done mr-1"></i>{{ translate('Give Badge') }}
                                </a>
                                <label class="form-check m-0 d-none eligible-store-check">
                                    <input type="checkbox" class="form-check-input mt-0" checked disabled>
                                </label>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div id="eligible-store-footer" class=" d-none">
            <div class="align-items-center bg-white bottom-0 d-flex gap-3 justify-content-center mt-auto offcanvas-footer p-3 position-sticky border-top">
                <div class="d-flex gap-3 w-100 justify-content-center">
                    <button type="button" class="btn w-100 btn--reset offcanvas-close h--40px">{{ translate('Cancel') }}</button>
                    <button type="button" id="verify-all-stores" class="btn w-100 btn--primary h--40px">{{ translate('Verify All') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div id="offcanvasOverlay" class="offcanvas-overlay"></div>
    <div class="modal shedule-modal fade" id="verify-all-modal" tabindex="-1" aria-labelledby="verifyAllModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content pb-2 max-w-500">
                <div class="modal-header">
                    <button type="button"
                        class="close bg-modal-btn w-30px h-30 rounded-circle position-absolute right-0 top-0 m-2 z-2"
                        data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <img src="{{ asset('public/assets/admin/img/badge-big.png') }}" alt="icon" class="mb-3">
                        <h3 class="mb-2">{{ translate('Verify all qualified stores?') }}</h3>
                        <p class="mb-0">{{ translate('This will give a verified badge to every store that matches the verification criteria.') }}</p>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-0 pt-0 gap-2">
                    <button type="button" class="btn min-w-120px btn--reset" data-dismiss="modal">{{ translate('messages.Cancel') }}</button>
                    <a href="{{ route('admin.store.verified-seller-all') }}" class="btn min-w-120px btn--primary">{{ translate('messages.Yes') }}</a>
                </div>
            </div>
        </div>
    </div>



@endsection

@push('script_2')
    <script>
        "use strict";
        function resetEligibleStoreSelection() {
            $('#select-all-eligible-stores').prop('checked', false);
            $('#eligible-store-footer').addClass('d-none');
            $('#eligible-store-list .eligible-store-item').removeClass('border border-success bg-success bg-opacity-10');
            $('#eligible-store-list .eligible-give-btn').removeClass('d-none');
            $('#eligible-store-list .eligible-store-check').addClass('d-none');
        }

        $(document).on('click', '#open-eligible-store-list', function (e) {
            e.preventDefault();
            $('#offcanvas__customBtn3').removeClass('open');
            $('#offcanvas__eligibleStores').addClass('open');
            $('#offcanvasOverlay').addClass('show');
            resetEligibleStoreSelection();
        });

        $(document).on('click', '#back-to-verification', function (e) {
            e.preventDefault();
            $('#offcanvas__eligibleStores').removeClass('open');
            $('#offcanvas__customBtn3').addClass('open');
            $('#offcanvasOverlay').addClass('show');
            resetEligibleStoreSelection();
        });

        $(document).on('change', '#select-all-eligible-stores', function () {
            const checked = $(this).is(':checked');
            $('#eligible-store-list .eligible-store-item').toggleClass('border border-success bg-success bg-opacity-10', checked);
            $('#eligible-store-list .eligible-give-btn').toggleClass('d-none', checked);
            $('#eligible-store-list .eligible-store-check').toggleClass('d-none', !checked);
            $('#eligible-store-footer').toggleClass('d-none', !checked);
        });

        $(document).on('click', '#verify-all-stores', function (e) {
            e.preventDefault();
            $('#offcanvas__eligibleStores').removeClass('open');
            $('#offcanvas__customBtn3').removeClass('open');
            $('#offcanvasOverlay').removeClass('show');
            $('#verify-all-modal').modal('show');
        });

        $(document).on('click', '#verify-all-summary', function (e) {
            e.preventDefault();
            $('#offcanvas__customBtn3').removeClass('open');
            $('#offcanvas__eligibleStores').removeClass('open');
            $('#offcanvasOverlay').removeClass('show');
            resetEligibleStoreSelection();
            $('#verify-all-modal').modal('show');
        });

        $(document).on('click', '.offcanvas-close, #offcanvasOverlay', function () {
            resetEligibleStoreSelection();
        });

        $('.status_change_alert').on('click', function (event) {
            let url = $(this).data('url');
            let message = $(this).data('message');
            status_change_alert(url, message, event)
        })

        function status_change_alert(url, message, e) {
            e.preventDefault();
            Swal.fire({
                title: '{{ translate('Are you sure?') }}' ,
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{translate('messages.no')}}',
                confirmButtonText: '{{translate('messages.yes')}}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href=url;
                }
            })
        }

    </script>

@endpush
