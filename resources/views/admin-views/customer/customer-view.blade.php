@extends('layouts.admin.app')

@section('title',translate('Customer Details'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-print-none pb-3">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title mb-1">{{translate('messages.customer_id')}} #{{$customer['id']}}</h1>
                    <span class="fs-12">
                        {{translate('messages.joined_at')}} : {{date('d M Y '.config('timeformat'),strtotime($customer['created_at']))}}
                    </span>

                </div>
            </div>
        </div>
        @include('admin-views.customer.partials._tab_view')
        <!-- End Page Header -->
        @if ($customer['f_name'])
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex gap-2 align-items-center">
                        <img src="{{asset('public/assets/admin/img/icons/coupon-icon.png')}}" width="16" height="16" alt="">
                        <p class="mb-0">{{ translate('If you want to make a customized COUPON for this customer, click the Create Coupon button and influence them buy more from your store.') }}</p>
                    </div>

                    <a href="{{ route('admin.coupon.add-new',['customer' => $customer['id']]) }}" class="btn btn-warning text-white font-semibold">
                        <i class="tio-add"></i>
                        {{translate('messages.create_coupon')}}
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div class="row mb-3 g-2">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="color-card flex-column align-items-center justify-content-center color-2 flex-grow-1">
                                <div class="img-box">
                                    <img class="resturant-icon w--30" src="{{asset('/public/assets/admin/img/icons/order-icon-1.png')}}" alt="">
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <h2 class="title"> {{ $orders->total() }} </h2>
                                    <div class="subtitle">
                                        {{ translate('total_order') }}
                                    </div>
                                </div>
                            </div>
                            <div class="color-card flex-column align-items-center justify-content-center color-5 flex-grow-1">
                                <div class="img-box">
                                    <img class="resturant-icon w--30" src="{{asset('/public/assets/admin/img/icons/order-icon-2.png')}}" alt="">
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <h2 class="title"> {{ \App\CentralLogics\Helpers::format_currency($total_order_amount[0]->total_order_amount) }} </h2>
                                    <div class="subtitle">
                                        {{ translate('total_order_amount') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="color-card flex-column align-items-center justify-content-center color-7 flex-grow-1">
                                <div class="img-box">
                                    <img class="resturant-icon w--30" src="{{asset('/public/assets/admin/img/icons/order-icon-3.png')}}" alt="transactions">
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <h2 class="title"> {{$customer->wallet_balance??0}} </h2>
                                    <div class="subtitle">
                                        {{translate('messages.wallet_balance')}}
                                    </div>
                                </div>
                            </div>
                            <div class="color-card flex-column align-items-center justify-content-center color-4 flex-grow-1">
                                <div class="img-box">
                                    <img class="resturant-icon w--30" src="{{asset('/public/assets/admin/img/icons/order-icon-4.png')}}" alt="transactions">
                                </div>
                                <div class="d-flex flex-column align-items-center">
                                    <h2 class="title"> {{$customer->loyalty_point??0}} </h2>
                                    <div class="subtitle">
                                        {{translate('messages.loyalty_point')}}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" id="printableArea">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="card">
                    <div class="card-header border-0 py-2 d-flex flex-wrap gap-2">
                        <div class="search--button-wrapper">
                            <h5 class="card-title d-flex gap-2 align-items-center">
                                {{translate('order_list')}}
                                <span class="badge badge-soft-secondary">{{ $orders->total() }}</span>
                            </h5>

                            <div class="min--260">
                                <form class="search-form theme-style">
                                    <div class="input-group input--group">
                                        <input  type="search" name="search" class="form-control"
                                        placeholder="{{translate('ex_: search_by_order_id')}}" aria-label="{{translate('messages.search')}}" value="{{request()?->search}}" >
                                        <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                    </div>
                                </form>

                            </div>
                            @if(request()->get('search'))
                                 <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                                 @endif
                        </div>
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
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.customer.order-export', ['type'=>'excel','id'=>$customer->id,request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.customer.order-export', ['type'=>'csv','id'=>$customer->id,request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                {{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                    <!-- End Unfold -->
                    </div>

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
                                    <th class="border-0 pl-4">{{translate('SL')}}</th>
                                    <th class="border-0">{{translate('messages.order_ID')}}</th>
                                    <th class="border-0">{{translate('messages.store')}}</th>
                                    <th class="border-0 ">{{translate('messages.status')}}</th>
                                    <th class="border-0 text-center ">{{translate('messages.total_Items')}}</th>
                                    <th class="border-0 ">{{translate('messages.total_amount')}}</th>
                                    <th class="border-0 ">{{translate('messages.order_date')}}</th>
                                    <th class="border-0 text-center">{{translate('messages.action')}}</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($orders as $key=>$order)
                                    <tr>
                                        <td>
                                            <div class="pl-2">
                                                {{$key+$orders->firstItem()}}
                                            </div>
                                        </td>
                                        <td>
                                            <a class="text-dark" href="{{route((isset($order) && $order->order_type=='parcel')?'admin.parcel.order.details':'admin.order.details',['id'=>$order['id'],'module_id'=>$order['module_id']])}}">{{$order['id']}}</a>
                                        </td>
                                        <th>
                                            @if ($order->store)
                                            <div><a  class="text--title" href="{{route('admin.store.view', $order->store_id)}}" alt="view store">{{Str::limit($order->store?$order->store->name:translate('messages.store deleted!'),20,'...')}}</a></div>
                                            @else
                                                <div>{{Str::limit(translate('messages.not_found'),20,'...')}}</div>
                                            @endif
                                        </th>
                                        <td class="text-capitalize ">
                                            @if($order['order_status']=='pending')
                                                <span class="badge badge-soft-info">
                                      {{translate('messages.pending')}}
                                    </span>
                                            @elseif($order['order_status']=='confirmed')
                                                <span class="badge badge-soft-info">
                                      {{translate('messages.confirmed')}}
                                    </span>
                                            @elseif($order['order_status']=='processing')
                                                <span class="badge badge-soft-warning">
                                      {{translate('messages.processing')}}
                                    </span>
                                            @elseif($order['order_status']=='picked_up')
                                                <span class="badge badge-soft-warning">
                                      {{translate('messages.out_for_delivery')}}
                                    </span>
                                            @elseif($order['order_status']=='delivered')
                                                <span class="badge badge-soft-success">
                                      {{translate('messages.delivered')}}
                                    </span>
                                            @elseif($order['order_status']=='failed')
                                                <span class="badge badge-soft-danger">
                                      {{translate('messages.payment_failed')}}
                                    </span>
                                            @elseif($order['order_status']=='handover')
                                                <span class="badge badge-soft-danger">
                                      {{translate('messages.handover')}}
                                    </span>
                                            @elseif($order['order_status']=='canceled')
                                                <span class="badge badge-soft-danger">
                                      {{translate('messages.canceled')}}
                                    </span>
                                            @elseif($order['order_status']=='accepted')
                                                <span class="badge badge-soft-danger">
                                      {{translate('messages.accepted')}}
                                    </span>
                                            @elseif($order['order_status']=='refund_requested')
                                                <span class="badge badge-soft-danger">
                                      {{translate('messages.refund_requested')}}
                                    </span>
                                            @else
                                                <span class="badge badge-soft-danger">
                                      {{str_replace('_',' ',$order['order_status'])}}
                                    </span>
                                            @endif

                                        </td>
                                        <td>
                                            <div class="text-center mw--85px mx-auto">
                                                {{ $order?->details_count != 0  ?  $order?->details_count: translate('messages.N/A') }}
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                {{\App\CentralLogics\Helpers::format_currency($order['order_amount'])}}
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div>
                                                    {{ \App\CentralLogics\Helpers::date_format($order->created_at) }}
                                                </div>
                                                <div class="d-block text-uppercase">
                                                    {{ \App\CentralLogics\Helpers::time_format($order->created_at) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--warning btn-outline-warning" href="{{route((isset($order) && $order->order_type=='parcel')?'admin.parcel.order.details':'admin.order.details',['id'=>$order['id']])}}" title="{{translate('messages.view')}} "><i class="tio-visible"></i></a>
                                                {{-- <a class="btn action-btn btn--primary btn-outline-primary" target="_blank" href="{{route('admin.order.generate-invoice',[$order['id']])}}" title="{{translate('messages.invoice')}}">
                                                    <i class="tio-print"></i>
                                                </a> --}}
                                                <a class="btn action-btn btn--primary btn-outline-primary" target="_blank" href="{{route('admin.order.generate-invoice',[$order['id']])}}" title="{{translate('messages.download')}}">
                                                    <i class="tio-download-to"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($orders) !== 0)
                    <hr>
                    @endif
                    <div class="page-area">
                        {!! $orders->links() !!}
                    </div>
                    @if(count($orders) === 0)
                    <div class="empty--data">
                        <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                        <h5>
                            {{translate('no_data_found')}}
                        </h5>
                    </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <h4 class="card-title d-flex flex-wrap align-items-center gap-2">
                            <div class="d-flex align-items-center gap-1">
                                <span class="card-header-icon">
                                    <i class="tio-user"></i>
                                </span>
                                <span class=""> {{ translate('customer_information') }}</span>
                            </div>
                            <span class="badge badge-soft-info">{{ translate('total_order') }}: {{ $orders->total() }}</span>
                        </h4>
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    @include('admin-views.customer.partials._customer_view_information')
                <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>
        </div>
        <!-- End Row -->
    </div>
@endsection

@push('script_2')

    <script>
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });


            $('#column3_search').on('change', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                let select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>
@endpush
