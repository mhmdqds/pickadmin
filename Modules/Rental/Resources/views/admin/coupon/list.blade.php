@extends('layouts.admin.app')

@section('title',translate('messages.coupons'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/add.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('Add new coupon')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row g-2">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <form id="coupon_add_form" action="{{route('admin.rental.coupon.store')}}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-12">
                                    @if ($language)
                                    <ul class="nav nav-tabs mb-3 border-0">
                                        <li class="nav-item">
                                            <a class="nav-link lang_link active"
                                            href="#"
                                            id="default-link">{{translate('messages.default')}}</a>
                                        </li>
                                        @foreach ($language as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link"
                                                    href="#"
                                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="lang_form" id="default-form">
                                        <div class="form-group">
                                            <label class="input-label"
                                                for="default_title">{{ translate('messages.title') }}
                                                (Default)
                                            </label>
                                            <input type="text" name="title[]" id="default_title"
                                                class="form-control" placeholder="{{ translate('messages.new_coupon') }}" required>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                    </div>
                                        @foreach ($language as $lang)
                                            <div class="d-none lang_form"
                                                id="{{ $lang }}-form">
                                                <div class="form-group">
                                                    <label class="input-label"
                                                        for="{{ $lang }}_title">{{ translate('messages.title') }}
                                                        ({{ strtoupper($lang) }})
                                                    </label>
                                                    <input type="text" name="title[]" id="{{ $lang }}_title"
                                                        class="form-control" placeholder="{{ translate('messages.new_coupon') }}"
                                                         >
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{ $lang }}">
                                            </div>
                                        @endforeach
                                    @else
                                        <div id="default-form">
                                            <div class="form-group">
                                                <label class="input-label"
                                                    for="exampleFormControlInput1">{{ translate('messages.title') }} ({{ translate('messages.default') }})</label>
                                                <input type="text" name="title[]" class="form-control"
                                                    placeholder="{{ translate('messages.new_coupon') }}">
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.coupon_type')}}</label>
                                        <select name="coupon_type" id="coupon_type" class="form-control js-select2-custom" required>
                                            <option disabled selected>---{{translate('messages.Select_coupon_type')}}---</option>
                                            <option value="store_wise">{{translate('messages.Provider_wise')}}</option>
                                            <option value="zone_wise">{{translate('messages.zone_wise')}}</option>
                                            <option value="first_order">{{translate('messages.first_trip')}}</option>
                                            <option value="default">{{translate('messages.default')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6" id="store_wise">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlSelect1">{{translate('messages.Provider')}}<span
                                                class="input-label-secondary"></span></label>
                                        <select name="store_ids[]" id="store_id" data-url="{{ route('admin.store.get-providers') }}" class="js-data-example-ajax form-control" data-placeholder="{{translate('messages.select_store')}}" title="{{translate('messages.select_store')}}">
                                            <option disabled selected>---{{translate('messages.Select_Provider')}}---</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6" id="zone_wise">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.select_zone')}}</label>
                                        <select name="zone_ids[]" id="choice_zones"
                                            class="form-control js-select2-custom"
                                            multiple="multiple" data-placeholder="{{translate('messages.select_zone')}}">
                                        @foreach($zones as $zone)
                                            <option value="{{$zone->id}}">{{$zone->name}}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6" id="customer_wise">
                                    <div class="form-group">
                                        <label class="input-label" for="select_customer">{{translate('messages.select_customer')}}</label>
                                        <select name="customer_ids[]" id="select_customer"
                                            class="form-control js-select2-custom"
                                            multiple="multiple" data-placeholder="{{translate('messages.select_customer')}}">
                                            <option  value="all">{{translate('messages.all')}} </option>
                                        @foreach($users as $user)
                                            <option class="select_customer_option" value="{{$user->id}}" {{ (isset($customer) && is_numeric($customer) && ($customer == $user->id))?'selected':'' }}>{{$user->f_name.' '.$user->l_name}}</option>
                                        @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.code')}}</label>
                                        <input type="text" name="code" class="form-control"
                                            placeholder="{{\Illuminate\Support\Str::random(8)}}" required maxlength="100">
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.limit_for_same_user')}}</label>
                                        <input type="number" name="limit" id="coupon_limit" class="form-control" placeholder="EX: 10" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.start_date')}}</label>
                                        <input type="date" name="start_date" class="form-control" id="date_from" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.expire_date')}}</label>
                                        <input type="date" name="expire_date" class="form-control" id="date_to" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.min_trip_amount')}} ({{ \App\CentralLogics\Helpers::currency_symbol() }})</label>
                                        <input type="number" step="0.01" data-error-text="{{translate("Discount amount cannot be greater than minimum purchase amount")}}" id="min_purchase" name="min_purchase" value="0" min="0" max="999999999999.99" class="form-control"
                                            placeholder="100">
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.discount_type')}}</label>
                                        <select name="discount_type" class="form-control" id="discount_type" required>
                                            <option value="amount">{{translate('messages.amount')}} ({{ \App\CentralLogics\Helpers::currency_symbol() }})
                                            </option>
                                            <option value="percent">{{translate('messages.percent')}} (%)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.discount')}}
                                            <span class="input-label-secondary text--title" data-toggle="tooltip"
                                                data-placement="right"
                                                data-original-title="{{ translate('Currently_you_need_to_manage_discount_with_the_Store.') }}">
                                                <i class="tio-info-outined"></i>
                                            </span>
                                        </label>
                                        <input type="number" step="0.01"   placeholder="{{ translate('messages.Ex :') }} 100" min="1" max="999999999999.99" name="discount" id="discount" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4 col-lg-3 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" for="max_discount">{{translate('messages.max_discount')}} ({{ \App\CentralLogics\Helpers::currency_symbol() }})</label>
                                        <input type="number" step="0.01" min="0" value="0" max="999999999999.99" name="max_discount" id="max_discount" class="form-control" readonly>
                                    </div>
                                </div>

                            </div>
                            <div class="btn--container justify-content-end">
                                <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                                <button type="submit" class="btn btn--primary">{{translate('messages.submit')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header py-2 border-0">
                        <div class="search--button-wrapper">
                            <h5 class="card-title">{{translate('messages.coupon_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$coupons->total()}}</span></h5>
                            <form class="search-form min--270">

                                <!-- Search -->
                                <div class="input-group input--group">
                                    <input id="datatableSearch" type="search" name="search" value="{{ request()?->search ?? null }}" class="form-control" placeholder="{{ translate('messages.Ex:_Coupon_Title_Or_Code') }}" aria-label="{{translate('messages.search_here')}}">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                                <!-- End Search -->
                            </form>
                            @if(request()->get('search'))
                            <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                            @endif


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
                                        {{ route('admin.rental.coupon.export', ['type' => 'excel', request()->getQueryString()]) }}
                                        ">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                            alt="Image Description">
                                        {{ translate('messages.excel') }}
                                    </a>
                                    <a id="export-csv" class="dropdown-item" href="
                                    {{ route('admin.rental.coupon.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                            src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                            alt="Image Description">
                                        .{{ translate('messages.csv') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom" id="table-div">
                        <table id="columnSearchDatatable"
                               class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                               data-hs-datatables-options='{
                                "order": [],
                                "orderCellsTop": true,

                                "entries": "#datatableEntries",
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging":false
                               }'>
                            <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{translate('sl')}}</th>
                                <th class="border-0">{{translate('messages.title')}}</th>
                                <th class="border-0">{{translate('messages.code')}}</th>
                                <th class="border-0">{{translate('messages.type')}}</th>
                                <th class="border-0">{{translate('messages.total_uses')}}</th>
                                <th class="border-0">{{translate('messages.min_trip_amount')}}</th>
                                <th class="border-0">{{translate('messages.max_discount')}}</th>
                                <th class="border-0">{{translate('messages.discount')}}</th>
                                <th class="border-0">{{translate('messages.discount_type')}}</th>
                                <th class="border-0">{{translate('messages.start_date')}}</th>
                                <th class="border-0">{{translate('messages.expire_date')}}</th>
                                <th class="border-0">{{translate('messages.status')}}</th>
                                <th class="border-0 text-center">{{translate('messages.action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($coupons as $key=>$coupon)
                                <tr>
                                    <td>{{$key+$coupons->firstItem()}}</td>
                                    <td>
                                    <span title="{{ $coupon['title'] }}" class="d-block font-size-sm text-body">
                                    {{Str::limit($coupon['title'],15,'...')}}
                                    </span>
                                    </td>
                                    <td>{{$coupon['code']}}</td>
                                    @if ( $coupon->coupon_type == 'store_wise')
                                    <td>{{translate('messages.Provider_wise')}}</td>

                                    @elseif($coupon->coupon_type == 'first_order')

                                    <td>{{translate('messages.first_trip')}}</td>
                                    @else

                                    <td>{{translate('messages.'.$coupon->coupon_type)}}</td>
                                    @endif
                                    <td>{{$coupon->total_uses}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($coupon['min_purchase'])}}</td>
                                    <td>{{\App\CentralLogics\Helpers::format_currency($coupon['max_discount'])}}</td>
                                    <td>{{$coupon['discount']}}</td>
                                    <td>{{translate($coupon['discount_type'])}} {{ $coupon['discount_type'] == 'amount' ? (\App\CentralLogics\Helpers::currency_symbol())  : ( $coupon['discount_type'] == 'percent' ? ("%") : '')}}</td>
                                    <td>{{ \App\CentralLogics\Helpers::date_format($coupon['start_date']) }}</td>
                                    <td>{{\App\CentralLogics\Helpers::date_format($coupon['expire_date'])}}</td>
                                    <td>
                                        <label class="toggle-switch toggle-switch-sm" for="couponCheckbox{{$coupon->id}}">
                                            <input type="checkbox" data-url="{{route('admin.rental.coupon.status',[$coupon['id'],$coupon->status?0:1])}}" class="toggle-switch-input redirect-url" id="couponCheckbox{{$coupon->id}}" {{$coupon->status?'checked':''}}>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">

                                            <a class="btn action-btn btn--primary btn-outline-primary" href="{{route('admin.rental.coupon.update',[$coupon['id']])}}"title="{{translate('messages.edit_coupon')}}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="coupon-{{$coupon['id']}}" data-message="{{ translate('Want to delete this coupon ?') }}" title="{{translate('messages.delete_coupon')}}"><i class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{route('admin.rental.coupon.delete',[$coupon['id']])}}"
                                            method="post" id="coupon-{{$coupon['id']}}">
                                                @csrf @method('delete')
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                        @if(count($coupons) !== 0)
                        <hr>
                        @endif
                        <div class="page-area">
                            {!! $coupons->links() !!}
                        </div>
                        @if(count($coupons) === 0)
                        <div class="empty--data">
                            <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                            <h5>
                                {{translate('no_data_found')}}
                            </h5>
                        </div>
                        @endif
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>

    <input type="hidden" id="current_module_id" value="{{ Config::get('module.current_module_id') }}" >
@endsection

@push('script_2')
<script src="{{asset('public/assets/admin')}}/js/view-pages/coupon-index.js"></script>
<script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/coupon-list.js')}}"></script>
@endpush
