@extends('layouts.admin.app')
@section('title',translate('Edit Role'))
@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Heading -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/edit.png')}}" class="w--26" alt="">
            </span>
            <span>
                {{translate('messages.employee_Role')}}
            </span>
        </h1>
    </div>
    <!-- Page Heading -->
    <!-- Content Row -->
    <div class="row">
        <div class="col-md-12">
            <div class="">
                <div class="">
                    <form action="{{route('admin.users.custom-role.update',[$role['id']])}}" method="post">
                        @csrf
                        <div class="card mb-20">
                            <div class="card-body">
                                <div class="mb-20">
                                    <h4 class="title-clr fs-18 mb-1">{{ translate('messages.Role form') }}</h4>
                                    <p class="fs-12 mb-0">{{ translate('messages.Create role and assignee the role module & usage permission.') }}</p>
                                </div>
                                <div class="bg-light2 rounded p-xxl-20 p-3">
                                    @if($language)
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
                                        <div class="lang_form" id="default-form">
                                            <div class="form-group mb-0">
                                                <label class="input-label" for="default_title">{{translate('messages.role_name')}} ({{translate('messages.default')}}) <span class="form-label-secondary text-danger"
                                                    data-toggle="tooltip" data-placement="right"
                                                    data-original-title="{{ translate('messages.Required.')}}"> *
                                                    </span>
                                             </label>
                                                <input type="text" name="name[]" id="default_title" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$role?->getRawOriginal('name')}}"  >
                                            </div>
                                            <input type="hidden" name="lang[]" value="default">
                                        </div>
                                        @foreach($language as $lang)
                                            <?php
                                                if(count($role['translations'])){
                                                    $translate = [];
                                                    foreach($role['translations'] as $t)
                                                    {
                                                        if($t->locale == $lang && $t->key=="name"){
                                                            $translate[$lang]['name'] = $t->value;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <div class="d-none lang_form" id="{{$lang}}-form">
                                                <div class="form-group mb-0">
                                                    <label class="input-label" for="{{$lang}}_title">{{translate('messages.role_name')}} ({{strtoupper($lang)}})</label>
                                                    <input type="text" name="name[]" id="{{$lang}}_title" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$translate[$lang]['name']??''}}"  >
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$lang}}">
                                            </div>
                                        @endforeach
                                    @else
                                    <div id="default-form">
                                        <div class="form-group mb-0">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('messages.role_name')}} ({{ translate('messages.default') }})</label>
                                            <input type="text" name="name[]" class="form-control" placeholder="{{translate('role_name_example')}}" value="{{$role['name']}}" maxlength="100">
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- <div class="card">
                            <div class="card-header">
                                <div class="d-flex w-100 justify-content-between flex-wrap select--all-checkes gap-2">
                                    <h5 class="input-label m-0 fs-18 title-clr text-capitalize">{{translate('messages.Update_permission')}} : </h5>
                                    <div class="check-item check-item-custom pb-0 w-auto">
                                        <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                            <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input mt-0" id="select-all">
                                            <label class="form-check-label fw-medium pe-inline-end-24 fs-14 title-clr" for="select-all">{{ translate('All Management') }}</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="check--item-wrapper check--item-wrapper-custom">
                                    <div class="shadow-cutom-box-xxl mb-20">
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="">
                                                    <h4 class="title-clr fs-16 mb-20">{{ translate('messages.General') }}</h4>
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Profile Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="general_all">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="general_all">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="dashboard" class="form-check-input"
                                                                        id="dashboard">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="dashboard">{{translate('messages.Dashboard')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="profile" class="form-check-input"
                                                                        id="profile">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="profile">{{translate('messages.Profile')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shadow-cutom-box-xxl mb-20">
                                        <h4 class="title-clr fs-16 mb-20">{{ translate('messages.User Management') }}</h4>
                                        <div class="row g-3">
                                            <div class="col-lg-6">
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Promotion Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="profie_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="profie_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="cashback" class="form-check-input"
                                                                        id="cashback">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="cashback">{{translate('messages.cashback')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Delivery Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="user_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="user_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="category" class="form-check-input"
                                                                        id="category">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="category">{{translate('messages.Vehicle Category')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="deliveryman" class="form-check-input"
                                                                        id="deliveryman">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="deliveryman">{{translate('messages.Deliveryman Manage')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Customer Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="customers_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="customers_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="customer_management" class="form-check-input"
                                                                        id="customer_management">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="customer_management">{{translate('messages.customer_management')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="customer_wallet" class="form-check-input"
                                                                        id="customer_wallet">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="customer_wallet">{{translate('messages.Customer Wallet')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="customer_loyalty_point" class="form-check-input"
                                                                        id="customer_loyalty_point">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="customer_loyalty_point">{{translate('messages.Customer Loyalty Point')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="subscription" class="form-check-input"
                                                                        id="subscription">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="subscription">{{translate('messages.subscription')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="contact_messages" class="form-check-input"
                                                                        id="contact_messages">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="contact_messages">{{translate('messages.Contact Messages')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-12">
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Employee Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="employees_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="employees_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="employee_role" class="form-check-input"
                                                                        id="employee_role">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="employee_role">{{translate('messages.Employee Role')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="employee" class="form-check-input"
                                                                        id="employee">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="employee">{{translate('messages.Employee')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shadow-cutom-box-xxl mb-20">
                                        <h4 class="title-clr fs-16 mb-20">{{ translate('messages.Transaction & Report') }}</h4>
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Business Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="business_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="business_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="withdraw_list" class="form-check-input"
                                                                            id="withdraw_list">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="withdraw_list">{{translate('messages.withdraw_list')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="disbursement" class="form-check-input"
                                                                        id="disbursement">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="disbursement">{{translate('messages.disbursement')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input"
                                                                        id="collect_cash">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="collect_cash">{{translate('messages.collect_Cash')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="deliveryman_payments" class="form-check-input"
                                                                        id="deliveryman_payments">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="deliveryman_payments">{{translate('messages.deliveryman_payments')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Delivery Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="dms_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="dms_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="vehicle_category" class="form-check-input"
                                                                        id="vehicle_category">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="vehicle_category">{{translate('messages.vehicle_category')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="deliveryman_manage" class="form-check-input"
                                                                            id="deliveryman_manage">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="deliveryman_manage">{{translate('messages.deliveryman_manage')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Report & Analytics')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="report_analytics">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="report_analytics">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="report" class="form-check-input"
                                                                            id="report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="report">{{translate('messages.Transaction Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="item" class="form-check-input"
                                                                            id="item">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="item">{{translate('messages.Item Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="store" class="form-check-input"
                                                                        id="store">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="store">{{translate('messages.Store Wise Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="expense_report" class="form-check-input"
                                                                        id="expense_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="expense_report">{{translate('messages.expense_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="disbursement_report" class="form-check-input"
                                                                            id="disbursement_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="disbursement_report">{{translate('messages.disbursement_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="order" class="form-check-input"
                                                                        id="order">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="order">{{translate('messages.Order Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="admin_text_module" class="form-check-input"
                                                                        id="admin_text_module_system">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="admin_text_module_system">{{translate('messages.Admin Tax Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="vendor_vat_report" class="form-check-input"
                                                                        id="vendor_vat_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="vendor_vat_report">{{translate('messages.vendor vat report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item">
                                                                <div class="form-group form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="parcel" class="form-check-input"
                                                                        id="parcel">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="parcel">{{translate('messages.Parcel Tax Report')}}</label>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Rental Report and Analytics')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="rental_report_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="rental_report_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="transaction_report" class="form-check-input"
                                                                        id="transaction_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="transaction_report">{{translate('messages.Transaction Report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="vehicle_reports" class="form-check-input"
                                                                            id="vehicle_reports">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="vehicle_reports">{{translate('messages.vehicle_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="provider_wise_report" class="form-check-input"
                                                                            id="provider_wise_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="provider_wise_report">{{translate('messages.provider_wise_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="trip_reports" class="form-check-input"
                                                                            id="trip_reports">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="trip_reports">{{translate('messages.trip_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="trip_tax_report" class="form-check-input"
                                                                            id="trip_tax_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="trip_tax_report">{{translate('messages.trip_tax_report')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="provider_vat_reports" class="form-check-input"
                                                                            id="provider_vat_reports">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="provider_vat_reports">{{translate('messages.provider_vat_report')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shadow-cutom-box-xxl mb-20">
                                        <h4 class="title-clr fs-16 mb-20">{{ translate('messages.Settings') }}</h4>
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Business Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="bsns_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="bsns_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="module" class="form-check-input"
                                                                        id="module_system">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="module_system">{{translate('messages.Module Setup')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="zone" class="form-check-input"
                                                                        id="zone">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="zone">{{translate('messages.zone')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="settings" class="form-check-input"
                                                                        id="settings">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="settings">{{translate('messages.Business Settings')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="system_tax" class="form-check-input"
                                                                        id="system_tax">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="system_tax">{{translate('messages.system_tax')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="subscription_management" class="form-check-input"
                                                                        id="subscription_management">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="subscription_management">{{translate('messages.subscription_management')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="page_social_management" class="form-check-input"
                                                                        id="page_social_management">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="page_social_management">{{translate('messages.Pages & Social Media')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="gallery" class="form-check-input"
                                                                        id="gallery">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="gallery">{{translate('messages.gallery')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.System Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="sys_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="sys_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="third_party-ms" class="form-check-input"
                                                                        id="third_party-ms">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="third_party-ms">{{translate('messages.3rd Party & Configuration')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="login_setup" class="form-check-input"
                                                                        id="login_setup">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="login_setup">{{translate('messages.login_setup')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="email_setups" class="form-check-input"
                                                                        id="email_setups">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="email_setups">{{translate('messages.Email Setup')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="apps_setting" class="form-check-input"
                                                                        id="apps_setting">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="apps_setting">{{translate('messages.App Settings')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item p-0 m-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                                                        id="addon">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="addon">{{translate('messages.Addon Activation')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item p-0 m-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="notification" class="form-check-input"
                                                                        id="notification">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="notification">{{translate('messages.Notification Setup')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="clean_database" class="form-check-input"
                                                                        id="clean_database">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="clean_database">{{translate('messages.Clean Database')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 p-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="systems_addons" class="form-check-input"
                                                                        id="systems_addons">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="systems_addons">{{translate('messages.System Addons')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="shadow-cutom-box-xxl mb-20">
                                        <h4 class="title-clr fs-16 mb-20">{{ translate('messages.Modules Wise Management') }}</h4>
                                        <div class="row g-3">
                                            <div class="col-lg-12">
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Business Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="module_wises_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="module_wises_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="all_dispatch" class="form-check-input"
                                                                        id="all_dispatch">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="all_dispatch ">{{translate('messages.All Dispatch ')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Order Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="all_orders_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="all_orders_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="order_ms" class="form-check-input"
                                                                        id="order_ms">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="order_ms">{{translate('messages.Orders')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="pos" class="form-check-input"
                                                                        id="pos">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="pos">{{translate('messages.POS Orders')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="mng" class="form-check-input"
                                                                        id="mng">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="dms-mng">{{translate('messages.Delivery Management ')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Promotion Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="promotions_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="promotions_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                                                        id="campaign">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="campaign">{{translate('messages.campaign')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="banner" class="form-check-input"
                                                                        id="banner">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="banner">{{translate('messages.banner')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="coupon" class="form-check-input"
                                                                        id="coupon">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="coupon">{{translate('messages.coupon')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="push_ntf" class="form-check-input"
                                                                        id="push_ntf">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="push_ntf">{{translate('messages.Push Notification ')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item">
                                                                <div class="form-group form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="advertisement" class="form-check-input"
                                                                        id="advertisement">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="advertisement">{{translate('messages.advertisement')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Product Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="products_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="products_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="p_categories" class="form-check-input"
                                                                        id="p_categories">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="p_categories ">{{translate('messages.Categories ')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="attribute" class="form-check-input"
                                                                        id="attribute">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="attribute">{{translate('messages.attribute')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="unit" class="form-check-input"
                                                                        id="unit">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="unit">{{translate('messages.unit')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="common_condition" class="form-check-input"
                                                                        id="common_condition">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="common_condition">{{translate('messages.common_condition')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="brand" class="form-check-input"
                                                                        id="brand">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="brand">{{translate('messages.brand')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="p_addons" class="form-check-input"
                                                                        id="p_addons">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="p_addons">{{translate('messages.Addons')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="p_setups" class="form-check-input"
                                                                        id="p_setups">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="p_setups">{{translate('messages.Product Setup ')}}</label>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mb-20">
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper h-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Store Management')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="str_management">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="str_management">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-2">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="store_setups" class="form-check-input"
                                                                        id="store_setups">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="store_setups">{{translate('messages.Store Setup')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="recommended_store" class="form-check-input"
                                                                        id="recommended_store">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="recommended_store">{{translate('messages.Recommended Store')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="impor_bulk" class="form-check-input"
                                                                        id="impor_bulk">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="impor_bulk">{{translate('messages.Bulk Import/Export')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if (addon_published_status('Rental'))
                                    <div class="shadow-cutom-box-xxl mb-0">
                                        <div class="check--item-wrapper m-0 p-0 d-inline-block w-100">
                                            <div class="row g-3">
                                                <div class="col-lg-12">
                                                    <div class="">
                                                        <h4 class="title-clr fs-16 mb-20">{{ translate('messages.Rental Management') }}</h4>
                                                    </div>
                                                    <div class="bg-light2 rounded sub_slect_all_wrapper w-100">
                                                        <div class="d-flex px-xxl-20 px-3 p-12 w-100 justify-content-between flex-wrap gap-2 border-bottom">
                                                            <h5 class="input-label m-0 fs-14 title-clr text-capitalize">{{translate('messages.Rental')}} </h5>
                                                            <div class="check-item check-item-custom pb-0 w-auto">
                                                                <div class="form-group flex-row-reverse d-flex align-items-center form-check form--check pe-inline-start0 pe-inline-end0 m-0">
                                                                    <input type="checkbox" name="modules[]" value="" class="form-check-input mt-0 sub_select-all" id="manage__all_rental">
                                                                    <label class="form-check-label pe-inline-end-24 fs-14 title-clr" for="manage__all_rental">{{ translate('Select All') }}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="p-xxl-20 p-3 d-flex flex-wrap gap-3">
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="trip" class="form-check-input"
                                                                           id="trip">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="trip">{{translate('messages.Trip')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="promotion" class="form-check-input"
                                                                           id="promotion">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="promotion">{{translate('messages.Promotion')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="vehicle" class="form-check-input"
                                                                           id="vehicle">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="vehicle">{{translate('messages.Vehicle')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="provider" class="form-check-input"
                                                                           id="provider">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="provider">{{translate('messages.Provider')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="driver" class="form-check-input"
                                                                           id="driver">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="driver">{{translate('messages.Driver')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="download_app" class="form-check-input"
                                                                           id="download_app">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="download_app">{{translate('messages.Download app')}}</label>
                                                                </div>
                                                            </div>
                                                            <div class="check-item m-0 p-0">
                                                                <div class="form-group m-0 form-check form--check">
                                                                    <input type="checkbox" name="modules[]" value="rental_report" class="form-check-input"
                                                                           id="rental_report">
                                                                    <label class="form-check-label ps--3 qcont text-dark opacity-70" for="rental_report">{{translate('messages.Report')}}</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div> --}}

                         <div class="card">
                            <div class="card-body">
                                <div class="d-flex flex-wrap select--all-checkes">
                                    <h5 class="input-label m-0 text-capitalize">{{translate('messages.Update_permission')}} : </h5>
                                    <div class="check-item pb-0 w-auto">
                                        <div class="form-group form-check form--check m-0 ml-2">
                                            <input type="checkbox" name="modules[]" value="account" class="form-check-input" id="select-all">
                                            <label class="form-check-label ml-2" for="select-all">{{ translate('Select All') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="check--item-wrapper">
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="collect_cash" class="form-check-input"
                                                   id="collect_cash"  {{in_array('collect_cash',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="collect_cash">{{translate('messages.collect_cash')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="addon" class="form-check-input"
                                                   id="addon"  {{in_array('addon',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="addon">{{translate('messages.addon')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="attribute" class="form-check-input"
                                                   id="attribute"  {{in_array('attribute',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="attribute">{{translate('messages.attribute')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="advertisement" class="form-check-input"
                                                   id="advertisement"  {{in_array('advertisement',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="advertisement">{{translate('messages.advertisement')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="banner" class="form-check-input"
                                                   id="banner"  {{in_array('banner',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="banner">{{translate('messages.banner')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="campaign" class="form-check-input"
                                                   id="campaign"  {{in_array('campaign',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="campaign">{{translate('messages.campaign')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="category" class="form-check-input"
                                                   id="category"  {{in_array('category',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="category">{{translate('messages.category')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="coupon" class="form-check-input"
                                                   id="coupon"  {{in_array('coupon',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="coupon">{{translate('messages.coupon')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="cashback" class="form-check-input"
                                                   id="cashback"  {{in_array('cashback',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="cashback">{{translate('messages.cashback')}}</label>
                                        </div>
                                    </div>

                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="customer_management" class="form-check-input"
                                                   id="customer_management"  {{in_array('customer_management',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="customer_management">{{translate('messages.customer_management')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="deliveryman" class="form-check-input"
                                                   id="deliveryman"  {{in_array('deliveryman',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="deliveryman">{{translate('messages.deliveryman')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="disbursement" class="form-check-input"
                                                   id="disbursement"  {{in_array('disbursement',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="disbursement">{{translate('messages.disbursement')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="provide_dm_earning" class="form-check-input"
                                                   id="provide_dm_earning"  {{in_array('provide_dm_earning',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="provide_dm_earning">{{translate('messages.provide_dm_earning')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="employee" class="form-check-input"
                                                   id="employee"  {{in_array('employee',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="employee">{{translate('messages.Employee')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="item" class="form-check-input"
                                                   id="item"  {{in_array('item',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="item">{{translate('messages.item')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="notification" class="form-check-input"
                                                   id="notification"  {{in_array('notification',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="notification">{{translate('messages.push_notification')}} </label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="order" class="form-check-input"
                                                   id="order"  {{in_array('order',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="order">{{translate('messages.order')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="store" class="form-check-input"
                                                   id="store"  {{in_array('store',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="store">{{translate('messages.store')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="report" class="form-check-input"
                                                    id="report"  {{in_array('report',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="report">{{translate('messages.report')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="settings" class="form-check-input"
                                                   id="settings"  {{in_array('settings',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="settings">{{translate('messages.settings')}}</label>
                                        </div>
                                    </div>

                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="withdraw_list" class="form-check-input"
                                                    id="withdraw_list"  {{in_array('withdraw_list',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="withdraw_list">{{translate('messages.withdraw_list')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="zone" class="form-check-input"
                                                   id="zone"  {{in_array('zone',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="zone">{{translate('messages.zone')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="module" class="form-check-input"
                                                   id="module_system"  {{in_array('module',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="module_system">{{translate('messages.module')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="parcel" class="form-check-input"
                                                   id="parcel"  {{in_array('parcel',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="parcel">{{translate('messages.parcel')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="pos" class="form-check-input"
                                                   id="pos"  {{in_array('pos',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="pos">{{translate('messages.pos')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="unit" class="form-check-input"
                                                   id="unit"  {{in_array('unit',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="unit">{{translate('messages.unit')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="subscription" class="form-check-input"
                                                   id="subscription"  {{in_array('subscription',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="subscription">{{translate('messages.subscription')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="brand" class="form-check-input"
                                                   id="brand"  {{in_array('brand',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="brand">{{translate('messages.brand')}}</label>
                                        </div>
                                    </div>
                                    <div class="check-item">
                                        <div class="form-group form-check form--check">
                                            <input type="checkbox" name="modules[]" value="common_condition" class="form-check-input"
                                                   id="common_condition"  {{in_array('common_condition',(array)json_decode($role['modules']))?'checked':''}}>
                                            <label class="form-check-label qcont text-dark" for="common_condition">{{translate('messages.common_condition')}}</label>
                                        </div>
                                    </div>

                                @if ( addon_published_status('ReelsModule') )
                                  <div class="check-item">
                                      <div class="form-group form-check form--check">
                                          <input type="checkbox" name="modules[]" value="reels" class="form-check-input"
                                                 id="reels" {{in_array('reels',(array)json_decode($role['modules']))?'checked':''}}>
                                          <label class="form-check-label qcont text-dark" for="reels">{{translate('messages.reels')}}</label>
                                      </div>
                                  </div>
                                @endif
                                </div>
                                @if (addon_published_status('Rental'))
                                    <div class="pt-5">
                                        <h4>{{translate('Rental Role')}}</h4>
                                    </div>
                                    <div class="check--item-wrapper">
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="trip" class="form-check-input"
                                                       id="trip" {{in_array('trip',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="trip">{{translate('messages.Trip')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="promotion" class="form-check-input"
                                                       id="promotion" {{in_array('promotion',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="promotion">{{translate('messages.Promotion')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="vehicle" class="form-check-input"
                                                       id="vehicle" {{in_array('vehicle',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="vehicle">{{translate('messages.Vehicle')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="provider" class="form-check-input"
                                                       id="provider" {{in_array('provider',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="provider">{{translate('messages.Provider')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="driver" class="form-check-input"
                                                       id="driver" {{in_array('driver',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="driver">{{translate('messages.Driver')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="download_app" class="form-check-input"
                                                       id="download_app" {{in_array('download_app',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="download_app">{{translate('messages.Download app')}}</label>
                                            </div>
                                        </div>
                                        <div class="check-item">
                                            <div class="form-group form-check form--check">
                                                <input type="checkbox" name="modules[]" value="rental_report" class="form-check-input"
                                                       id="rental_report" {{in_array('rental_report',(array)json_decode($role['modules']))?'checked':''}}>
                                                <label class="form-check-label qcont text-dark" for="rental_report">{{translate('messages.Report')}}</label>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                        @if (addon_published_status('RideShare'))
                            <div class="pt-5">
                                <h4>{{translate('Ride Share Role')}}</h4>
                            </div>
                            <div class="check--item-wrapper">
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="heat_map" class="form-check-input"
                                               id="heat_map" {{in_array('heat_map',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="heat_map">{{translate('messages.heat_map')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="fleet_view" class="form-check-input"
                                               id="fleet_view" {{in_array('fleet_view',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="fleet_view">{{translate('messages.fleet_view')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="ride" class="form-check-input"
                                               id="ride" {{in_array('ride',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="ride">{{translate('messages.ride')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="ride_promotion" class="form-check-input"
                                               id="ride_promotion" {{in_array('ride_promotion',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="ride_promotion">{{translate('messages.promotion')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="fare" class="form-check-input"
                                               id="fare" {{in_array('fare',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="fare">{{translate('messages.fare')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="ride_vehicle" class="form-check-input"
                                               id="ride_vehicle" {{in_array('ride_vehicle',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="ride_vehicle">{{translate('messages.vehicle')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="rider" class="form-check-input"
                                               id="rider" {{in_array('rider',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="rider">{{translate('messages.rider')}}</label>
                                    </div>
                                </div>
                                <div class="check-item">
                                    <div class="form-group form-check form--check">
                                        <input type="checkbox" name="modules[]" value="ride_report" class="form-check-input"
                                               id="ride_report" {{in_array('ride_report',(array)json_decode($role['modules']))?'checked':''}}>
                                        <label class="form-check-label qcont text-dark" for="ride_report">{{translate('messages.report')}}</label>
                                    </div>
                                </div>
                            </div>
                        @endif
                            </div>
                        </div>

                        <div class="btn--container justify-content-end mt-4">
                            <button type="reset" class="btn btn--reset min-w-120px">{{translate('messages.reset')}}</button>
                            <button type="submit" class="btn btn--primary min-w-120px">{{translate('messages.update')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/custom-role-index.js"></script>
@endpush
