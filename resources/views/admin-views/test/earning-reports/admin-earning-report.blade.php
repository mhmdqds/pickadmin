@extends('layouts.admin.app')

@section('title', translate('messages.new_page'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-0">
            <div>
                <h1 class="page-header-title text-capitalize">
                    {{translate('messages.Admin_Earning_Report') }}
                </h1>
                <p>
                    {{translate('messages.Comprehensive_financial_overview_and_analytics')}}
                </p>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="js-nav-scroller hs-nav-scroller-horizontal mb-20">
            <!-- Nav -->
            <ul class="nav mb-0 nav-tabs border-0 nav--tabs nav--pills">
                <li class="nav-item">
                    <a class="nav-link active" href="#" aria-disabled="true">{{ translate('messages.All Modules') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" aria-disabled="true">{{ translate('messages.Rental Module') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" aria-disabled="true">{{ translate('messages.Ride Share') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" aria-disabled="true">{{ translate('messages.Parcel Module') }}</a>
                </li>
            </ul>
            <!-- End Nav -->
        </div>

        <div class="card card-body mb-20">
            <h3 class="mb-20">{{ translate('messages.Filter_Data') }}</h3>
            <form action="">
                <div class="__bg-F8F9FC-card">
                    <div class="row g-3 date-filter-wrapper">
                        <div class="col-lg-3 col-sm-6">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Module') }}
                            </label>
                            <select name="" id="" class="form-control custom-select">
                                <option value="all" selected>{{ translate('messages.Grocery') }}</option>
                                <option value="pharmacy">{{ translate('messages.Pharmacy') }}</option>
                                <option value="shop">{{ translate('messages.Shop') }}</option>
                                <option value="food">{{ translate('messages.Food') }}</option>
                                <option value="parcel">{{ translate('messages.Parcel') }}</option>
                                <option value="rental">{{ translate('messages.Rental') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Date_Range') }}
                            </label>
                            <select name="" id="" class="form-control custom-select date-type-select">
                                <option value="all" selected>{{ translate('messages.All_Time') }}</option>
                                <option value="month">{{ translate('messages.This_Month') }}</option>
                                <option value="year">{{ translate('messages.This_Year') }}</option>
                                <option value="custom">{{ translate('messages.Custom_Range') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3 col-sm-6 custom-date-div">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Start_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="" id="" class="form-control">
                        </div>
                        <div class="col-lg-3 col-sm-6 custom-date-div">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.End_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="" id="" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Rental Module Here -->
                {{--<div class="__bg-F8F9FC-card">
                    <div class="row g-3 date-filter-wrapper">
                        <div class="col-lg-4 col-sm-6">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Date_Range') }}
                            </label>
                            <select name="" id="" class="form-control custom-select date-type-select">
                                <option value="all" selected>{{ translate('messages.All_Time') }}</option>
                                <option value="month">{{ translate('messages.This_Month') }}</option>
                                <option value="year">{{ translate('messages.This_Year') }}</option>
                                <option value="custom">{{ translate('messages.Custom_Range') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-4 col-sm-6 custom-date-div">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.Start_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="" id="" class="form-control">
                        </div>
                        <div class="col-lg-4 col-sm-6 custom-date-div">
                            <label for="" class="input-label text-capitalize">
                                {{ translate('messages.End_Date') }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="" id="" class="form-control">
                        </div>
                    </div>
                </div>--}}
                <!-- Rental Module End -->

                <div class="btn--container mt-4 justify-content-end">
                    <button id="resetbtn" type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                    <button type="submit" class="btn btn--primary">{{ translate('messages.filter') }}</button>
                </div>
            </form>
        </div>

        <div class="card card-body mb-20">
            <div class="mb-3">
                <h3 class="mb-1">{{ translate('messages.Earnings_Summary') }}</h3>
                <p class="fs-12 mb-0">{{ translate('messages.Breakdown of Revenue Sources and Performance') }}</p>
            </div>
            <div class="row g-3 mb-20">
                <div class="col-lg-4 col-md-6">
                    <div class="card-shape-in position-relative bg-success-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer"
                        data-url="">
                        <div class="flex-grow-1">
                            <div class="opacity-lg fs-14 mb-2">{{ translate('messages.Total_Earnings') }}</div>
                            <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">$84,000.00</h2>
                            <div class="opacity-lg fs-14">↑ 12.5% {{ translate('messages.vs last period') }}</div>
                        </div>
                        <div
                            class="mark_badge fs-24 flex-shrink-0  rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                            $
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card-shape-in position-relative bg-warning-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer"
                        data-url="">
                        <div class="flex-grow-1">
                            <div class="opacity-lg fs-14 mb-2">{{ translate('messages.Total_Expenses') }}</div>
                            <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">$4,000.00</h2>
                            <div class="opacity-lg fs-14">↓ 3.2% {{ translate('messages.vs last period') }}</div>
                        </div>
                        <div
                            class="mark_badge fs-24 flex-shrink-0  rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                            $
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card-shape-in position-relative bg-info-gradient text-white rounded-10 p-3 p-xxl-20 d-flex gap-2 justify-content-between align-items-start overflow-hidden z-2 cursor-pointer"
                        data-url="">
                        <div class="flex-grow-1">
                            <div class="fs-14 mb-2">
                                <span class="opacity-lg">
                                    {{ translate('messages.Net_Profit') }}
                                </span>
                                <span data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('messages.Content Need')}}"
                                    class="text-white tio-info fs-16 m-0"></span>
                            </div>
                            <h2 class="font-medium fs-32 fs-18-mobile text-white mb-2">$80,000.00</h2>
                            <div class="opacity-lg fs-14">↑ 18.7% {{ translate('messages.vs last period') }}</div>
                        </div>
                        <div
                            class="mark_badge fs-24 flex-shrink-0  rounded-10 w-48 ratio--1 d-flex justify-content-center align-items-center">
                            $
                        </div>
                    </div>
                </div>
            </div>
            <h4 class="mb-3">{{ translate('messages.Earnings_Breakdown') }}</h4>
            <div class="border rounded-10 p-3 p-xxl-20 mb-4">
                <div class="row g-lg-5 gx-3 gy-3 earnings-breakdown">
                    <div class="col-lg-4 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-primary rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/order-commission.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Order Commission') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$42,500.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-purple rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/subscription.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Subscription Packages') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$18,700.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="item">
                            <div
                                class="flex-shrink-0 bg-icon-danger rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/service-charge.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Service Charge Collected') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$42,500.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-warning rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/additional-fees.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Additional Fees') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$42,500.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-success rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/tax-collected.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Tax Collected') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$42,500.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="item">
                            <div
                                class="flex-shrink-0 bg-icon-info-light rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/other-income.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Other Income') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$42,500.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <h4 class="mb-3">{{ translate('messages.Expenses_Breakdown') }}</h4>
            <div class="border rounded-10 p-3 p-xxl-20">
                <div class="row g-lg-5 gx-3 gy-3 earnings-breakdown">
                    <div class="col-lg-3 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-danger rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/free-delivery.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Free Delivery Costs') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$2,400.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-cream rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/coupon-offers.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Coupon Offers') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$800.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="item border-right">
                            <div
                                class="flex-shrink-0 bg-icon-warning rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/refunds.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Refunds') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$350.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <div class="item">
                            <div
                                class="flex-shrink-0 bg-icon-purple rounded-10 w-40px ratio--1 d-flex justify-content-center align-items-center mb-3">
                                <img src="{{asset('public/assets/admin/img/report/earning-breakdown/tax-payments.svg')}}"
                                    alt="earning">
                            </div>
                            <div class="mb-2">{{ translate('messages.Tax Payments') }}</div>
                            <h2 class="font-medium fs-24 fs-18-mobile mb-2">$450.00</h2>
                            <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">50.6%
                                {{ translate('messages.of Total') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-12">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings Trend') }}</h3>
                    </div>
                    <div class="card-body px-1 px-sm-2 py-0">
                        <div id="earning-trend-chart"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings vs Expenses') }}</h3>
                    </div>
                    <div class="card-body px-0 py-0">
                        <div id="monthly-earning-expense-graph"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header border-0 pb-0">
                        <h3 class="mb-1 text-title">{{ translate('messages.Earnings by Source') }}</h3>
                    </div>
                    <div class="card-body px-0 py-0">
                        <div id="earnings-pie-chart" class="chartjs-custom mx-auto" style="max-width:400px;"></div>
                    </div>
                </div>

            </div>
            <div class="col-lg-6">
                <div class="card card-body shadow-none h-100 pb-2">
                    <h3 class="mb-3">{{ translate('messages.Top Earning From Vendors') }}</h3>
                    <div class="">
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/100x100/1.png')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Food Fair</h5>
                                    <p class="fs-12 mb-0 d-flex align-items-center gap-2 flex-wrap">
                                        Mirpur Zone
                                        <span class="border-left ps-8px">Grocery</span>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">230 orders</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card card-body shadow-none h-100 pb-2">
                    <h3 class="mb-3">{{ translate('messages.Zone-wise_Earnings') }}</h3>
                    <div class="">
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                        <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                            <div class="flex-grow-1 d-flex gap-2 align-items-center">
                                <img class="w-40px aspect-1 object-cover rounded"
                                    src="{{asset('public/assets/admin/img/report/earning-breakdown/zone.svg')}}" alt="">
                                <div>
                                    <h5 class="fs-13 font-medium mb-0 line--limit-1 max-w-180px">Mirpur Zone</h5>
                                    <p class="fs-12 mb-0">45 restaurants</p>
                                </div>
                            </div>
                            <div>
                                <h5 class="font-bold mb-1">$280.4k</h5>
                                <p class="fs-12 mb-0">12.5% of total</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card card-body">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap border-0 recent-transaction-header">
                        <div>
                            <h3 class="mb-20">{{ translate('messages.Recent_Transactions') }}</h3>
                            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                                <!-- Nav -->
                                <ul class="nav mb-0 flex-wrap gap-2 nav-tabs border-0 nav--tabs nav--pills">
                                    <li class="nav-item">
                                        <a class="nav-link active" href="#"
                                            aria-disabled="true">{{ translate('messages.Order Earnings') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#"
                                            aria-disabled="true">{{ translate('messages.Subscription Earnings') }}</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#"
                                            aria-disabled="true">{{ translate('messages.Expenses') }}</a>
                                    </li>
                                </ul>
                                <!-- End Nav -->
                            </div>
                        </div>
                        <div class="search--button-wrapper justify-content-end">
                            <form class="m-0">
                                <!-- Search -->
                                <div class="input--group input-group input-group-merge input-group-flush">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control" value=""
                                        placeholder="{{ translate('Search_here') }}" aria-label="Search" required>
                                    <button type="submit" class="btn btn--secondary">
                                        <i class="tio-search"></i>
                                    </button>
                                </div>
                                <!-- End Search -->
                            </form>
                            <div
                                class="d-flex flex-wrap gpa-3 justify-content-sm-end align-items-sm-center ml-0 mr-0 flex-grow-0">
                                <!-- Unfold -->
                                <div class="hs-unfold m-0">
                                    <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40"
                                        href="javascript:;" data-hs-unfold-options='{
                                                    "target": "#usersExportDropdown",
                                                    "type": "css-animation"
                                                }'>
                                        <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                                    </a>

                                    <div id="usersExportDropdown"
                                        class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                                        <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                                        <a id="export-excel" class="dropdown-item"
                                            href="{{route('admin.users.customer.wallet.export', ['type' => 'excel', request()->getQueryString()])}}">
                                            <img class="avatar avatar-xss avatar-4by3 mr-2"
                                                src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                                alt="Image Description">
                                            {{ translate('messages.excel') }}
                                        </a>
                                        <a id="export-csv" class="dropdown-item"
                                            href="{{route('admin.users.customer.wallet.export', ['type' => 'csv', request()->getQueryString()])}}">
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
                        <!-- End Row -->
                    </div>
                    <!-- End Header -->

                    <!-- Table All Module Here -->
                    <div class="table-responsive datatable-custom mt-4 z-index-2">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-dark"
                            data-hs-datatables-options='{
                                "columnDefs": [{
                                    "targets": [0],
                                    "orderable": false
                                }],
                                "order": [],
                                "info": {
                                "totalQty": "#datatableWithPaginationInfoTotalQty"
                                },
                                "search": "#datatableSearch",
                                "entries": "#datatableEntries",
                                "pageLength": 25,
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging":false
                            }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">
                                        {{ translate('SL') }}
                                    </th>
                                    <th class="table-column-pl-0 border-0">{{ translate('messages.Transaction_ID') }}</th>
                                    <th class="border-0">{{ translate('messages.Date') }}</th>
                                    <th class="border-0">{{ translate('messages.Source') }}</th>
                                    <th class="border-0 text-center">{{ translate('messages.Earning_Source') }}</th>
                                    <th class="border-0 text-right">{{ translate('messages.Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                        <div class="badge text-danger bg-danger bg-opacity-10 rounded-lg font-medium px-2">
                                            {{ translate('messages.Vendor') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-12 mt-1">#ORD 100021</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                        <div class="badge text-info bg-info bg-opacity-10 rounded-lg font-medium px-2">
                                            {{ translate('messages.Delivery_Man') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge text-success bg-success bg-opacity-10 rounded-lg font-regular px-2">
                                            {{ translate('messages.Delivery Commission') }}</div>
                                        <div class="fs-12 mt-1">#ORD 100021</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                        <div class="badge text-danger bg-danger bg-opacity-10 rounded-lg font-medium px-2">
                                            {{ translate('messages.Vendor') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge text-success bg-success bg-opacity-10 rounded-lg font-regular px-2">
                                            {{ translate('messages.Tax Collected') }}</div>
                                        <div class="fs-12 mt-1">#ORD 100021</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td colspan="12" class="bg-light">
                                        <div class="pt-2 pb-2 commission-space d-flex flex-column gap--10px">
                                            <div class="d-flex align-items-center gap-2 justify-content-between">
                                                <div class="fs-14">Order Commission</div>
                                                <div class="fs-14">+ $10.90</div>
                                            </div>
                                            <div class="border-bottom"></div>
                                            <div class="d-flex align-items-center gap-2 justify-content-between">
                                                <div class="fs-14">Service Charge </div>
                                                <div class="fs-14">+ $10.90</div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td class="font-medium">TXN004</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Crimson Hearth Grill</div>
                                        <div class="badge text-danger bg-danger bg-opacity-10 rounded-lg font-medium px-2">
                                            {{ translate('messages.Vendor') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-12 mt-1">#ORD 100021</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>3</td>
                                    <td class="font-medium">TXN004</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Crimson Hearth Grill</div>
                                        <div class="badge text-danger bg-danger bg-opacity-10 rounded-lg font-medium px-2">
                                            {{ translate('messages.Vendor') }}</div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fs-12 mt-1">#ORD 100021</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Subscription Earnings tabs -->
                    {{--<div class="table-responsive datatable-custom">
                        <table id="datatable"
                            class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table text-dark"
                            data-hs-datatables-options='{
                                "columnDefs": [{
                                    "targets": [0],
                                    "orderable": false
                                }],
                                "order": [],
                                "info": {
                                "totalQty": "#datatableWithPaginationInfoTotalQty"
                                },
                                "search": "#datatableSearch",
                                "entries": "#datatableEntries",
                                "pageLength": 25,
                                "isResponsive": false,
                                "isShowPaging": false,
                                "paging":false
                            }'>
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">
                                        {{ translate('SL') }}
                                    </th>
                                    <th class="table-column-pl-0 border-0">{{ translate('messages.Transaction_ID') }}</th>
                                    <th class="border-0">{{ translate('messages.Date') }}</th>
                                    <th class="border-0">{{ translate('messages.Vendor') }}</th>
                                    <th class="border-0 text-center">{{ translate('messages.Earning_Source') }}</th>
                                    <th class="border-0 text-right">{{ translate('messages.Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge title-clr fw-normal bg-warning bg-opacity-10 rounded-lg px-2 font-regular">
                                            {{ translate('messages.Upgrade subscription') }}</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge title-clr fw-normal bg-opacity-theme-10 bg-secondary rounded-lg px-2 font-regular">
                                            {{ translate('messages.Upgrade subscription') }}</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge title-clr fw-normal bg-warning bg-opacity-10 rounded-lg px-2 font-regular">
                                            {{ translate('messages.Upgrade subscription') }}</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                                <tr>
                                    <td>1</td>
                                    <td class="font-medium">TXN001</td>
                                    <td>
                                        28 Dec 2024
                                        <br>
                                        11:09 pm
                                    </td>
                                    <td>
                                        <div class="mb-1">Golden Spoon Diner</div>
                                    </td>
                                    <td class="text-center">
                                        <div
                                            class="badge title-clr fw-normal bg-opacity-theme-10 bg-secondary rounded-lg px-2 font-regular">
                                            {{ translate('messages.Upgrade subscription') }}</div>
                                    </td>
                                    <td class="text-right">$1,250.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div> --}}

                    {{-- <div class="empty--data">
                        <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                        <h5>
                            {{ translate('no_data_found') }}
                        </h5>
                    </div> --}}


                    <!-- End Table -->
                    {{-- <div class="page-area px-4 pb-3">
                        <div class="d-flex align-items-center justify-content-end">
                            <div>
                                {!! $customers->withQueryString()->links() !!}
                            </div>
                        </div>
                    </div> --}}
                    <!-- End Footer -->

                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <!-- Apex Charts -->
    <script src="{{ asset('/public/assets/admin/js/apex-charts/apexcharts.js') }}"></script>
    <!-- Apex Charts -->
    <script>
        let earningTrendChart;

        function initEarningTrendStatisticsChart() {

            const chartElement = document.getElementById('earning-trend-chart');
            if (!chartElement) return;

            if (earningTrendChart) {
                earningTrendChart.destroy();
                earningTrendChart = null;
            }

            // Demo months
            const categories = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            // Demo earnings data
            const seriesData = [120, 180, 150, 220, 260, 210, 300, 280, 320, 350, 370, 390];

            const options = {
                series: [{
                    name: '',
                    data: seriesData
                }],
                chart: {
                    height: 350,
                    type: 'line',
                    toolbar: { show: false }
                },
                colors: ['#019463'],
                stroke: {
                    width: 2,
                    curve: 'smooth'
                },
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: categories,
                },
                yaxis: {
                    min: 0,
                    max: 400,
                    tickAmount: 4,
                    labels: {
                        offsetX: -10
                    }
                },
                grid: {
                    strokeDashArray: 4
                },
                tooltip: {
                    theme: 'dark',
                    shared: false,
                    x: {
                        show: false
                    },
                    y: {
                        formatter: function (val, opts) {
                            const month = opts.w.globals.categoryLabels[opts.dataPointIndex];
                            return month + ' : ' + val;
                        }
                    }
                }
            };

            earningTrendChart = new ApexCharts(chartElement, options);
            earningTrendChart.render();
        }

        function initEarningVsExpensechart() {
            let earning = [20000, 35000, 42000, 38000, 55000, 60000, 75000, 82000, 70000, 65000, 90000, 95000];
            let expense = [15000, 25000, 30000, 28000, 40000, 42000, 50000, 52000, 48000, 45000, 60000, 65000];

            let columnWidth = window.innerWidth <= 768 ? '8px' : '13px';

            let options = {
                series: [
                    { name: "Earning", data: earning },
                    { name: "Expense", data: expense }
                ],
                chart: {
                    type: 'bar',
                    height: 380,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: columnWidth,
                        borderRadius: 5,
                        borderRadiusApplication: 'end',
                    }
                },
                colors: ['#059669E5', '#D97706E5'],
                xaxis: {
                    categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
                },
                yaxis: {
                    min: 0,
                    max: 100000,
                    tickAmount: 4,
                    labels: {
                        formatter: function (val) { return (val / 1000) + "k"; }
                    }
                },
                dataLabels: { enabled: false },
                grid: { borderColor: '#e5e7eb' },
                tooltip: {
                    y: {
                        formatter: function (val) { return val.toLocaleString(); }
                    }
                }
            };

            let chart = new ApexCharts(
                document.querySelector("#monthly-earning-expense-graph"),
                options
            );

            chart.render();
        }

        function loadEarningsPieChart() {
            const labels = [
                'Order Commission',
                'Subscription Packages',
                'Additional Fees',
                'Service Charges',
                'Tax Collected',
                'Other Income'
            ];

            const data = [45000, 15000, 8000, 12000, 6000, 4000];

            const options = {
                chart: {
                    type: 'donut',
                    height: 350
                },
                series: data,
                labels: labels,
                colors: ['#04BB7B', '#8B5CF6', '#EC4899', '#F59E0B', '#3C76F1', '#06B6D4'],
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return val.toFixed(0) + '%';
                    },
                    style: {
                        fontSize: '14px',
                        fontWeight: '600',
                        colors: ['#fff']
                    }
                },
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: function (val, opts) {
                            const label = opts?.w?.globals?.labels?.[opts.seriesIndex] || '';
                            return label + ": ৳ " + val.toLocaleString();
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '15px',
                                    fontWeight: 500,
                                    color: '#373d3f',
                                    offsetY: -5
                                },
                                value: {
                                    show: true,
                                    fontSize: '18px',
                                    fontWeight: 700,
                                    color: '#222324',
                                    formatter: function (val, opts) {
                                        const series = opts.w.globals.seriesTotals;
                                        const total = series.reduce((a, b) => a + b, 0);
                                        return (total / 1000).toLocaleString() + 'k';
                                    }
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total Earning',
                                    fontSize: '16px',
                                    fontWeight: 600,
                                    color: '#656566',
                                    formatter: function (w) {
                                        const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return (total / 1000).toLocaleString() + 'k';
                                    }
                                }
                            }
                        }
                    }
                }
            };

            const chartEl = document.querySelector("#earnings-pie-chart");
            if (chartEl) {
                const chart = new ApexCharts(chartEl, options);
                chart.render();
            }
        }

        $(document).ready(function () {

            initEarningTrendStatisticsChart();
            initEarningVsExpensechart();
            loadEarningsPieChart();

            function toggleCustomDate(wrapper) {
                let selectValue = wrapper.find('.date-type-select').val();

                if (selectValue === 'custom') {
                    wrapper.find('.custom-date-div').slideDown(200);
                } else {
                    wrapper.find('.custom-date-div').slideUp(200);
                }
            }

            $(document).on('change', '.date-type-select', function () {
                let wrapper = $(this).closest('.date-filter-wrapper');
                toggleCustomDate(wrapper);
            });

            $('.date-filter-wrapper').each(function () {
                toggleCustomDate($(this));
            });

        });

    </script>
    <script>

    </script>
@endpush