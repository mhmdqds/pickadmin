@extends('layouts.admin.app')

@section('title',translate('Provider List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/rental/provider.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{translate('messages.Provider')}}
                </span></h1>
            <div class="page-header-select-wrapper">
            </div>
        </div>
        <!-- End Page Header -->


        <!-- Provider Card Wrapper -->
        <div class="row g-3 mb-3">
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-1">
                    @php($total_store = \App\Models\Store::whereHas('vendor', function($query){
                        return $query->where('status', 1);
                    })->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($total_store = isset($total_store) ? $total_store : 0)
                    <h4 class="title">{{$total_store}}</h4>
                    <span class="subtitle">{{translate('messages.total_providers')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/total_provider.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-3">
                    @php($active_stores = \App\Models\Store::whereHas('vendor', function($query){
                        return $query->where('status', 1);
                    })->where(['status'=>1])->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($active_stores = isset($active_stores) ? $active_stores : 0)
                    <h4 class="title">{{$active_stores}}</h4>
                    <span class="subtitle">{{translate('messages.active_providers')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/active_provider.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-4">
                    @php($inactive_stores = \App\Models\Store::whereHas('vendor', function($query){
                        return $query->where('status', 1);
                    })->where(['status'=>0])->where('module_id', Config::get('module.current_module_id'))->count())
                    @php($inactive_stores = isset($inactive_stores) ? $inactive_stores : 0)
                    <h4 class="title">{{$inactive_stores}}</h4>
                    <span class="subtitle">{{translate('messages.inactive_providers')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/inactive_providers.png')}}" alt="store">
                </div>
            </div>
            <div class="col-xl-3 col-sm-6">
                <div class="resturant-card card--bg-2">
                    @php($data = \App\Models\Store::whereHas('vendor', function($query){
                        return $query->where('status', 1);
                    })->where('created_at', '>=', now()->subDays(30)->toDateTimeString())->where('module_id', Config::get('module.current_module_id'))->count())
                    <h4 class="title">{{$data}}</h4>
                    <span class="subtitle">{{translate('messages.newly_joined_providers')}}</span>
                    <img class="resturant-icon" src="{{asset('/public/assets/admin/img/new_provider.png')}}" alt="{{translate('provider')}}">
                </div>
            </div>
        </div>

        <ul class="transaction--information text-uppercase">
            <li class="text--info">
                <i class="tio-document-text-outlined"></i>
                <div>
                    <span>{{translate('messages.total_transactions')}}</span> <strong>{{$totalTransaction}}</strong>
                </div>
            </li>
            <li class="seperator"></li>
            <li class="text--success">
                <i class="tio-checkmark-circle-outlined success--icon"></i>
                <div>
                    <span>{{translate('messages.commission_earned')}}</span> <strong>{{\App\CentralLogics\Helpers::format_currency($comissionEarned)}}</strong>
                </div>
            </li>
            <li class="seperator"></li>
            <li class="text--danger">
                <i class="tio-atm"></i>
                <div>
                    <span>{{translate('messages.total_provider_withdraws')}}</span> <strong>{{\App\CentralLogics\Helpers::format_currency($storeWithdraws)}}</strong>
                </div>
            </li>
        </ul>

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.providers_list')}} <span class="badge badge-soft-dark ml-2" id="itemCount">{{$stores->total()}}</span></h5>

                @if(!isset(auth('admin')->user()->zone_id))
                <div class="select-item min--280">
                    <select name="zone_id" class="form-control js-select2-custom set-filter" data-url="{{url()->full()}}" data-filter="zone_id">
                        <option value="" {{!request('zone_id')?'selected':''}}>{{ translate('messages.All_Zones') }}</option>
                        @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
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
                                    placeholder="{{translate('ex_:_Search_provider_Name')}}" aria-label="{{translate('messages.search')}}" >
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
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.store.export', ['is_rental'=>1,'type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.store.export', ['type'=>'csv',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <a href="{{  route('admin.rental.provider.create') }}" type="button" target="_blank" class="btn btn--primary ml-2 location-reload-to-base" rel="noopener noreferrer">{{translate('messages.New_Provider')}}</a>


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
                        <th class="border-0">{{translate('messages.provider')}}</th>
                        <th class="border-0">{{translate('messages.owner_info')}}</th>
                        <th class="border-0">{{translate('messages.Total_vehicle')}}</th>
                        <th class="text-uppercase border-0">{{translate('messages.total_trip')}}</th>
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
                                    <a href="{{route('admin.rental.provider.details', $store->id)}}" class="table-rest-info" alt="{{translate('view provider')}}">
                                    <img class="img--60 circle onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"

                                            src="{{ $store['logo_full_url'] ?? asset('public/assets/admin/img/160x160/img1.jpg') }}"

                                            >
                                        <div class="info"><div title="{{ $store?->name }}" class="text--title">
                                            {{Str::limit($store->name,20,'...')}}
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
                                {{$store->vehicles->count()}}
                            </td>
                            <td>
                                <span class="form-label-secondary cursor-pointer" data-toggle="tooltip" data-placement="bottom" data-html="true"

                                      data-original-title="<div class='text-left p-3'>
                                <div class='d-flex gap-2'><div class='w--100px'>{{translate('Complete')}}</div> : {{ $store->trips()->Completed()->count() }}</div>
                              <div class='d-flex gap-2'><div class='w--100px'>{{translate('Ongoing')}}</div> : {{ $store->trips()->Ongoing()->count() }}</div>
                              <div class='d-flex gap-2'><div class='w--100px'>{{translate('Canceled')}}</div> : {{ $store->trips()->Canceled()->count() }}</div>
                              <div class='text-danger font-bold d-flex gap-2'><div class='w--100px'>{{translate('Cancelation Rate')}}</div> : {{ number_format($store->trips()->Canceled()->count() > 0 ? ($store->trips()->Canceled()->count() / $store->trips->count()) * 100 : 0) }}%</div>
                            </div>">
                                      {{ $store->trips->count() }} <i class="tio-info"></i>
                                </span>
                            </td>

                            <td>
                                @if(isset($store->vendor->status))
                                    @if($store->vendor->status)
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$store->id}}">
                                        <input type="checkbox" data-url="{{route('admin.rental.provider.status',[$store->id])}}" data-message="{{translate('messages.you_want_to_change_this_provider_status')}}" class="toggle-switch-input status_change_alert" id="stocksCheckbox{{$store->id}}" {{$store->status?'checked':''}}>
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
                                            href="{{route('admin.rental.provider.details', $store->id)}}"
                                            title="{{ translate('messages.details') }}"><i
                                                class="tio-visible-outlined"></i>
                                        </a>
                                    <a class="btn action-btn btn--primary btn-outline-primary"
                                    href="{{route('admin.rental.provider.edit-basic-setup',[$store['id']])}}" title="{{translate('messages.edit_provider')}}"><i class="tio-edit"></i>
                                    </a>
                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                    data-id="vendor-{{$store['id']}}" data-message="{{translate('You want to remove this provider')}}" title="{{translate('messages.delete_provider')}}"><i class="tio-delete-outlined"></i>
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


    <div class="d-none" id="data-set"
        data-translate-are-you-sure="{{ translate('Are_you_sure?') }}"
        data-translate-no="{{ translate('no') }}"
        data-translate-yes="{{ translate('yes') }}"
    ></div>


@endsection

@push('script_2')
<script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-list.js')}}"></script>
@endpush
