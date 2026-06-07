@extends('layouts.admin.app')

@section('title',translate('messages.new_provider_requests'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <img class="onerror-image"
                src="{{ asset('/public/assets/admin/img/rental/provider.png') }}" width="30" alt="img"> &nbsp;
                {{translate('messages.new_provider_requests')}}</h1>
            <div class="page-header-select-wrapper">

                @if(!isset(auth('admin')->user()->zone_id))
                    <div class="select-item">
                        <select name="zone_id" class="form-control js-select2-custom set-filter" data-url="{{url()->full()}}" data-filter="zone_id">
                            <option value="" {{!request('zone_id')?'selected':''}}>{{ translate('messages.All_Zones') }}</option>
                            @foreach(\App\Models\Zone::orderBy('name')->get(['name','id' ]) as $z)
                                <option
                                    value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                                    {{$z['name']}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="js-nav-scroller hs-nav-scroller-horizontal mt-2">
                        <!-- Nav -->
                        <ul class="nav nav-tabs mb-3 border-0 nav--tabs">
                            <li class="nav-item">
                                <a class="nav-link {{request('request_type') == 'pending_provider' ? 'active' : ''}}" href="{{ route('admin.rental.provider.new-requests') }}?request_type=pending_provider"   aria-disabled="true">{{translate('messages.Pending_Request')}}</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{request('request_type') == 'denied_provider' ? 'active' : ''}}" href="{{ route('admin.rental.provider.new-requests') }}?request_type=denied_provider"  aria-disabled="true">{{translate('messages.Rejected_Request')}}</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.providers_list')}} <span class="badge badge-soft-dark ml-2" id="itemCount">{{$stores->total()}}</span></h5>
                    <form action="javascript:" id="search-form" class="search-form">
                        <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                   placeholder="{{translate('ex_:_Search_Provider_Name')}}" value="{{isset($search_by) ? $search_by : ''}}" aria-label="{{translate('messages.search')}}" required>
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                    </form>
                    <!-- End Search -->
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
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">{{ translate('messages.provider') }}</th>
                            <th class="border-0">{{ translate('messages.owner_info') }}</th>
                            <th class="border-0">{{ translate('messages.business_zone') }}</th>
                            <th class="text-uppercase border-0">{{ translate('messages.business_plan') }}</th>
                            <th class="text-center border-0">{{ translate('messages.action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($stores as $key=>$store)
                        <tr>
                            <td>{{$key+$stores->firstItem()}}</td>
                            <td>
                                <div>
                                    <a href="{{route('admin.rental.provider.new-requests-details', $store->id)}}" class="table-rest-info" alt="{{translate('view provider')}}">
                                        <img class="img--60 circle onerror-image" data-onerror-image="{{asset('public/assets/admin/img/160x160/img1.jpg')}}"
                                             src="{{ $store['logo_full_url'] ?? asset('public/assets/admin/img/160x160/img1.jpg') }}" >
                                        <div class="info"><div class="text--title">
                                                {{Str::limit($store->name,20,'...')}}
                                            </div>
                                            <div class="font-light">
                                                {{$store['phone']}}
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="table-rest-info d-block">
                                    <div class="info">
                                        <div title="{{ $store->vendor->f_name.' '.$store->vendor->l_name }}" class="text--title">
                                            {{Str::limit($store->vendor->f_name.' '.$store->vendor->l_name,20,'...')}}
                                        </div>
                                        <div>
                                            <span class="font-light">
                                                {{ $store['email'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                            </td>
                            <td>
                                {{$store->zone?$store->zone->name:translate('messages.zone_deleted')}}
                            </td>
                            <td>
                                <div class="table-rest-info d-block">
                                    <div class="info">
                                        <div title="Car Rental Service" class="text--title">
                                            {{ ucwords($store->store_business_model) }}
                                        </div>
                                        <div>
                                            <span class="font-light">
                                                {{ $store?->package?->package_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="btn--container justify-content-center">
                                    @if($store->vendor->status == 0)
                                        <button type="button"
                                                class="btn action-btn btn--varify btn-outline-varify shadow-none"
                                                data-deny="approve" data-toggle="modal"
                                                data-target="#exampleModal--approve"><i class="tio-done"></i>
                                        </button>
                                    @endif
                                    @if (!isset($store->vendor->status))
                                        <button type="button"
                                                class="btn action-btn btn--danger btn-outline-danger shadow-none"
                                                data-deny="cancel" data-toggle="modal"
                                                data-target="#exampleModal--cancel"><i class="tio-clear"></i>
                                        </button>
                                    @endif
                                    <a class="btn action-btn btn--warning btn-outline-warning"
                                       href="{{route('admin.rental.provider.new-requests-details', $store->id)}}"
                                       title="{{ translate('messages.details') }}"><i
                                            class="tio-visible-outlined"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="exampleModal--approve" tabindex="-1" aria-labelledby="exampleModalLabel--approve"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body pt-5 p-md-5">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <img src="{{ asset('public/assets/admin/img/new-img/close-icon-dark.svg') }}" alt="">
                                        </button>

                                        <div class="d-flex justify-content-center mb-4">
                                            <img width="75" height="75" src="{{ asset('public/assets/admin/img/modal/mark.png') }}"
                                                 class="rounded-circle" alt="">
                                        </div>

                                        <h3 class="text--title mb-6 font-medium text-center">
                                            {{ translate('Are you sure, want to approve the request?') }}</h3>
                                        <form method="get" action="{{route('admin.rental.provider.approve-or-deny',[$store['id'],1])}}">
                                            @csrf
                                            <div class="form-floating">
                                                <input type="hidden" value="1" name="status">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <button type="button" data-dismiss="modal" aria-label="Close"
                                                            class="btn btn--reset">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn--primary">{{ translate('Approve') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal fade" id="exampleModal--cancel" tabindex="-1" aria-labelledby="exampleModalLabel--cancel"
                             aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body pt-5 p-md-5">
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <img src="{{ asset('public/assets/admin/img/new-img/close-icon-dark.svg') }}" alt="">
                                        </button>

                                        <div class="d-flex justify-content-center mb-4">
                                            <img width="75" height="75" src="{{ asset('public/assets/admin/img/icons/delete.png') }}"
                                                 class="rounded-circle" alt="">
                                        </div>

                                        <h3 class="text--title mb-6 font-medium text-center">
                                            {{ translate('Are you sure, want to cancel the request?') }}</h3>
                                        <form method="get" action="{{route('admin.rental.provider.approve-or-deny',[$store['id'],0])}}">
                                            @csrf
                                            <div class="form-floating">
                                                <label for="add-your-note" class="font-medium input-label text--title">{{ translate('Cancellation Note') }}
                                                    <span class="form-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="Cancellation Note">
                                                            <i class="tio-info text--title opacity-60"></i>
                                                    </span>
                                                </label>
                                                <div class="mb-30">
                                                    <textarea
                                                        class="form-control h--90"
                                                        placeholder="{{ translate('Type your Cancellation Note') }}"
                                                        name="message"
                                                        id="add-your-note"
                                                        maxlength="60"
                                                        required
                                                    ></textarea>
                                                    <div id="char-count">0/60</div>
                                                </div>
                                                <input type="hidden" value="0" name="status">
                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="button" data-dismiss="modal" aria-label="Close"
                                                            class="btn btn--reset">{{ translate('Cancel') }}</button>
                                                    <button type="submit" class="btn btn--primary">{{ translate('Deny') }}</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    </tbody>
                </table>

            </div>
            <!-- End Table -->
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
        </div>
    </div>

    <div class="d-none" id="data-set"
        data-translate-are-you-sure="{{ translate('Are_you_sure?') }}"
        data-translate-no="{{ translate('no') }}"
        data-translate-yes="{{ translate('yes') }}"
         data-full-url="{{ url()->full() }}"
    ></div>

@endsection

@push('script_2')
<script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/new-provider-list.js')}}"></script>
@endpush
