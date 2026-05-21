@extends('layouts.admin.app')

@section('title',translate('Campaign view'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title text-break">{{$campaign->title}}</h1>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card mb-20">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="bg-1079801A p-20 rounded h-100">
                            <div class="campaign-content-group">
                                <div class="upload-file_custom w-100">
                                    <div class="max-w-200px h-75px">

                                        <label class="upload-file__wrapper border-0 bg-white w-100 h-100 m-0">
                                            <div class="upload-file-textbox text-center">
                                                <img width="22" class="svg"
                                                        src="{{asset('public/assets/admin/img/document-upload.svg')}}"
                                                        alt="img">

                                            </div>
                                            <img class="upload-file-img" loading="lazy" src="{{ $campaign->image_full_url }}" alt=""
                                            >
                                        </label>
                                    </div>
                                    <div class="overlay show opacity--1">
                                        <div
                                            class="d-flex gap-1 justify-content-center align-items-center h-100">
                                            <a type="button" href="{{route('admin.campaign.edit',['basic',$campaign['id']])}}"
                                                    class="btn btn-outline-base d-center w-24px h-24px min-w-24px icon-btn ">
                                                <i class="tio-edit fs-14"></i>
                                            </a>
                                            <a type="button" class=" opacity--1 z-50 btn btn-outline-danger icon-btn d-center w-24px h-24px min-w-24px" data-toggle="modal"
                                                data-target="#confirmation-deletes-{{$campaign['id']}}" data-id="campaign-{{$campaign['id']}}"
                                                data-message="{{translate('messages.Want_to_delete_this_item')}}">
                                                <i class="tio-delete text-danger fs-14"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>



                                <div class="mt-12px">
                                    <h4 class="fs-16 mb-1">{{translate('messages.Get Your Grocery Items')}}</h4>
                                    <p class="fs-14 see-more_pragraph" data-character="140">
                                        {{$campaign->description}}
                                        <span class="text-info see__moreBtn d-none text-underline">{{ translate('See more') }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="bg-1079801A p-20 rounded h-100">
                            <h4 class="mb-15 font-weight-normal fs-14">{{translate('messages.Select Store for add this campaign')}}</h4>
                            <form action="{{route('admin.campaign.addstore',$campaign->id)}}" id="store-add-form" method="POST">
                                @csrf
                                <div class="d-flex flex-wrap gap-4 flex-column align-items-end">
                                    <div class="w-100">
                                    @php($allstores=App\Models\Store::Active()->where('module_id', $campaign->module_id)->get(['id', 'name']))
                                        <select name="store_id" id="store_id" class="custom-select js-select2-custom form-control">
                                            <option disabled selected    value=""> {{ translate('messages.select_store') }}</option>
                                            @forelse($allstores as $store)
                                            @if(!in_array($store->id, $store_ids))
                                            <option value="{{$store->id}}" >{{$store->name}}</option>
                                            @endif
                                            @empty
                                            <option value="">{{ translate('messages.no_data_found') }}</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="mt-lg-2">
                                        <button type="submit" class="btn btn--primary font-weight-regular fs-14 h--45px">{{translate('messages.add_store')}}</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- Card -->
        <div class="card">
            <div class="card-header py-3 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">
                        {{translate('messages.campaign_list')}}
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $stores->total() }}</span>
                    </h5>
                    <form class="search-form">

                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch" type="search" name="search"  value="{{ request()?->search ?? null }}" class="form-control" placeholder="{{ translate('messages.Ex:_Search Title ...') }}" aria-label="Search here">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>


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
                            <a id="export-excel" class="dropdown-item" href="
                                {{ route('admin.campaign.basic_campaign_store_export', [ 'id' => $campaign->id ,'type' => 'excel', request()->getQueryString()]) }}
                                ">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="
                            {{ route('admin.campaign.basic_campaign_store_export', [ 'id' => $campaign->id ,'type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                {{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                    <!-- End Unfold -->
                    <a class="btn btn--primary py-10px px-3 fs-12" href="{{route('admin.campaign.add-new', 'basic')}}">
                        <i class="tio-add-circle"></i> {{translate('messages.add_new_campaign')}}
                    </a>
                </div>
            </div>
            <div class="card-body py-0">
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                            class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                            data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true
                            }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('messages.SL') }}</th>
                                <th class="border-0 w--15">{{translate('messages.store')}}</th>
                                <th class="border-0 w--25">{{translate('messages.owner')}}</th>
                                <th class="border-0">{{translate('messages.Contact Info')}}</th>
                                <th class="border-0">{{translate('messages.Joining Date')}}</th>
                                <th class="border-0">{{translate('messages.status')}}</th>
                                <th class="border-0">{{translate('messages.action')}}</th>
                            </tr>
                        </thead>

                        <tbody id="set-rows">
                        @foreach($stores as $key=>$store)
                            <tr>
                                <td>{{$key+1}}</td>
                                <td>
                                    <div class="store-items d-flex align-items-center gap-2">
                                        <img width="40" class="img--circle img--60 onerror-image" src="{{ $store['logo_full_url'] }}">

                                        <h6 class="fw-medium title-clr">
                                            <a href="{{route('admin.store.view', $store->id)}}" title="{{$store->name}}" class="max-w--220px min-w-135px line--limit-1 title-clr font-size-sm">
                                                 {{Str::limit($store->name,12,'...')}}
                                            </a>
                                        </h6>
                                    </div>
                                </td>
                                <td>
                                    <span title=" {{$store->vendor->f_name.' '.$store->vendor->l_name}}" class="max-w--220px min-w-135px line--limit-1 font-size-sm title-clr">
                                        {{$store->vendor->f_name.' '.$store->vendor->l_name}}
                                    </span>
                                </td>
                                <td title="{{$store->email}}">
                                    <a href="mailto:{{$store->email}}" class="title-clr">
                                        {{$store->email}}
                                    </a>
                                    <br>
                                    <a href="tel:{{$store['phone']}}" class="title-clr">
                                        {{$store['phone']}}
                                    </a>
                                </td>
                                <td >
                                    <div class="title-clr">
                                        {{\App\CentralLogics\Helpers::date_format($store->pivot->created_at ?? $campaign->created_at)}}
                                    </div>
                                </td>
                                @php($status = $store->pivot ? $store->pivot->campaign_status : translate('messages.not_found'))
                                    <td class="text-capitalize">
                                        @if ($status == 'pending')
                                            <span class="badge badge-soft-info border-0">
                                                {{ translate('messages.not_approved') }}
                                            </span>
                                        @elseif($status == 'confirmed')
                                            <span class="badge badge-soft-success border-0">
                                                {{ translate('messages.confirmed') }}
                                            </span>
                                        @elseif($status == 'rejected')
                                            <span class="badge badge-soft-danger border-0">
                                                {{ translate('messages.rejected') }}
                                            </span>
                                        @else
                                            <span class="badge badge-soft-info border-0">
                                                {{ translate(str_replace('_', ' ', $status)) }}
                                            </span>
                                        @endif

                                    </td>
                                <td>
                                    @if ($store->pivot && $store->pivot->campaign_status == 'pending')
                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn status-change-alert"
                                            data-url="{{ route('admin.campaign.store_confirmation', [$campaign->id, $store->id, 'confirmed']) }}" data-message="{{ translate('messages.you_want_to_confirm_this_store') }}"
                                            class="toggle-switch-input" data-toggle="tooltip" data-placement="top" title="{{translate('Approve')}}">
                                            <i class="tio-done font-weight-bold"></i>
                                        </a>
                                        <a class="btn btn-sm btn--danger btn-outline-danger action-btn status-change-alert" href="javascript:"
                                            data-url="{{ route('admin.campaign.store_confirmation', [$campaign->id, $store->id, 'rejected']) }}" data-message="{{ translate('messages.you_want_to_reject_this_store') }}" data-toggle="tooltip" data-placement="top" title="{{translate('Deny')}}">
                                            <i class="tio-clear font-weight-bold"></i>
                                        </a>
                                        <div></div>
                                    </div>
                                    @elseif ($store->pivot && $store->pivot->campaign_status == 'rejected')

                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn-sm btn--primary btn-outline-primary action-btn status-change-alert"
                                            data-url="{{ route('admin.campaign.store_confirmation', [$campaign->id, $store->id, 'confirmed']) }}" data-message="{{ translate('messages.you_want_to_confirm_this_store') }}"
                                            class="toggle-switch-input" data-toggle="tooltip" data-placement="top" title="{{translate('Approve')}}">
                                            <i class="tio-done font-weight-bold"></i>
                                        </a>

                                    </div>
                                    @else
                                    <div class="btn--container justify-content-center">
                                        <a class="btn btn--danger btn-outline-danger action-btn form-alert" href="javascript:"
                                            data-id="campaign-{{$store->id}}" data-message="{{translate('messages.want_to_remove_store')}}" title="{{translate('messages.delete_campaign')}}"><i class="tio-delete-outlined"></i>
                                        </a>

                                        <form action="{{route('admin.campaign.remove-store',[$campaign->id, $store['id']])}}"
                                                        method="GET" id="campaign-{{$store->id}}">
                                            @csrf
                                        </form>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="page-area">
                        <table>
                            <tfoot>
                            {!! $stores->links() !!}
                            </tfoot>
                        </table>
                    </div>

                </div>
                <!-- End Table -->
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
        <!-- End Card -->
    </div>


        <div class="modal shedule-modal fade" id="confirmation-deletes-{{$campaign['id']}}" tabindex="-1" aria-labelledby="exampleModalLabel"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                         <form action="{{route('admin.campaign.delete',[$campaign['id']])}}"  method="post" id="campaign-{{$campaign['id']}}">
                                                            @csrf @method('delete')
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
                                                                        <img src="{{asset('public/assets/admin/img/delete.png')}}" alt="icon" class="mb-20">
                                                                        <h3 class="mb-2 fs-18">{{ translate('Want to delete this Campaign?') }}</h3>
                                                                        @if ( $campaign->stores_count > 0)
                                                                        <p class="mb-2 px-3 text-wrap">{{ translate('This campaign is already running, and ') }} {{ $campaign->stores_count }} {{ translate('of your stores have joined. If you delete it, those stores will be removed from the campaign.') }}</p>
                                                                        @else
                                                                        <p class="mb-2 px-3 text-wrap">{{ translate('Please confirm before deleting this campaign. This will permanently remove this from the campaign list.') }}</p>
                                                                        @endif

                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer justify-content-center border-0 pt-0 mb-1 gap-2">
                                                                    <button type="submit" class="btn min-w-120px btn-danger min-h-45px">{{ translate('messages.Yes, Delete') }}</button>
                                                                    <button type="button" class="btn min-w-120px btn--reset min-h-45px" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>




@endsection

@push('script_2')



    <script>
        "use strict";
        $('.status-change-alert').on('click', function (event){
            let url = $(this).data('url');
            let message = $(this).data('message');
            event.preventDefault();
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
        })
    </script>
@endpush
