@extends('layouts.admin.app')

@section('title',translate('messages.add_new_condition'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/condition.png')}}" class="w--20" alt="">
                </span>
                <span>
                    {{translate('messages.Common_Condition_Setup')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form action="{{route('admin.common-condition.store')}}" method="post">
                    @csrf
                    <div class="mb-20">
                        <h3 class="mb-1 fs-18">{{ translate('Add Common Condition') }}</h3>
                        <p class="mb-0">{{ translate('Here you can add and manage common condition names that will be displayed to customers.') }}</p>
                    </div>
                    <div class="bg-light2 rounded p-20">
                        @if($language)
                            @php($defaultLang = $language[0])
                            <ul class="nav nav-tabs mb-4">
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
                            <div class="form-group lang_form" id="default-form">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{ translate('messages.default') }})</label>
                                <input type="text" name="name[]" class="form-control" placeholder="{{translate('messages.new_condition')}}" maxlength="191">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                            @foreach($language as $lang)
                                <div class="form-group d-none lang_form" id="{{$lang}}-form">
                                    <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}} ({{strtoupper($lang)}})</label>
                                    <input type="text" name="name[]" class="form-control" placeholder="{{translate('messages.new_condition')}}" maxlength="191">
                                </div>
                                <input type="hidden" name="lang[]" value="{{$lang}}">
                            @endforeach
                        @else
                            <div class="form-group">
                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.name')}}</label>
                                <input type="text" name="name" class="form-control" placeholder="{{translate('messages.new_condition')}}" value="{{old('name')}}" maxlength="191">
                            </div>
                            <input type="hidden" name="lang[]" value="default">
                        @endif
                        <div class="btn--container justify-content-end mt-20">
                            <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary">{{isset($condition)?translate('messages.update'):translate('messages.add')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-20">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title title-clr fs-16 fw-semibold">{{translate('messages.Common_Conditions')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$conditions->total()}}</span></h5>
                    <form  class="search-form">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch" name="search" value="{{ request()?->search ?? null }}"  type="search" class="form-control" placeholder="{{translate('messages.search_by_name')}}" aria-label="{{translate('messages.Common_Conditions')}}">
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
                                {{ route('admin.campaign.basic_campaign_export', ['type' => 'excel', request()->getQueryString()]) }}
                                ">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="
                            {{ route('admin.campaign.basic_campaign_export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                {{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body py-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                        data-hs-datatables-options='{
                            "search": "#datatableSearch",
                            "entries": "#datatableEntries",
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{translate('sl')}}</th>
                                <th class="border-0 w--1">{{translate('messages.Common_Condition_Name')}}</th>
                                <th class="border-0 text-center">{{translate('messages.Total_Products')}}</th>
                                <th class="border-0 text-center">{{translate('messages.status')}}</th>
                                <th class="border-0 text-center">{{translate('messages.action')}}</th>
                            </tr>
                        </thead>

                        <tbody id="table-div">
                        @foreach($conditions as $key=>$condition)
                            <tr>
                                <td class="title-clr fs-14">{{$key+$conditions->firstItem()}}</td>
                                <td>
                                    <span class="d-block fs-14 title-clr cursor-pointer offcanvas-trigger data-info-show"
                                    data-id="{{ $condition->id }}"
                                    data-url="{{route('admin.common-condition.view',$condition->id)}}"
                                    data-target="#offcanvas_common_condition">
                                        {{Str::limit($condition['name'],20,'...')}}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="d-block fs-14 title-clr cursor-pointer offcanvas-trigger data-info-show"
                                    data-id="{{ $condition->id }}"
                                    data-url="{{route('admin.common-condition.view',$condition->id)}}"
                                    data-target="#offcanvas_common_condition">
                                        {{ $condition->items()->count()}}
                                    </span>
                                </td>
                                <td>
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$condition->id}}">
                                    <input type="checkbox" data-url="{{route('admin.common-condition.status',[$condition['id'],$condition->status?0:1])}}" class="toggle-switch-input redirect-url" id="stocksCheckbox{{$condition->id}}" {{$condition->status?'checked':''}}>
                                        <span class="toggle-switch-label mx-auto">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <button type="#0" class="btn action-btn btn-theme-dark btn-outline-base offcanvas-trigger data-info-show"
                                        data-id="{{ $condition->id }}"
                                    data-url="{{route('admin.common-condition.view',$condition->id)}}"
                                        data-target="#offcanvas_common_condition">
                                            <i class="tio-visible"></i>
                                        </button>
                                        <a class="btn action-btn btn-theme btn-outline-base"
                                            href="{{route('admin.common-condition.edit',[$condition['id']])}}" title="{{translate('messages.edit_condition')}}"><i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:" data-id="condition-{{$condition['id']}}" data-message="{{ translate('messages.Want to delete this condition') }}"  title="{{translate('messages.delete_condition')}}"><i class="tio-delete-outlined"></i>
                                        </a>
                                        <form action="{{route('admin.common-condition.delete',[$condition['id']])}}" method="post" id="condition-{{$condition['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(count($conditions) !== 0)
            <hr class="border-0">
            @endif
            <div class="page-area">
                {!! $conditions->links() !!}
            </div>
            @if(count($conditions) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
        </div>
    </div>



    <div id="offcanvas_common_condition" class="custom-offcanvas d-flex flex-column justify-content-between">
        <div id="data-view" class="h-100">
        </div>
    </div>
    <div id="offcanvasOverlay" class="offcanvas-overlay"></div>




@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/common-condition-index.js"></script>
@endpush
