@extends('layouts.admin.app')

@section('title',translate('messages.Reels_List'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script type="text/javascript" src="{{asset('public/assets/admin/js/moment.min.js')}}"></script>
<script type="text/javascript" src="{{asset('public/assets/admin/js/daterangepicker.min.js')}}"></script>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="row g-3">
        <div class="col-12">
            <div class="card card-body">
                <h4 class="mb-3">{{ translate('Reels_Overview') }}</h4>
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-purple bg-opacity-10">
                                <div class="flex-grow-1">
                                   <h3 class="fs-20 mb-1">37</h3>
                                   <p class="text-muted mb-0">{{ translate('Total_Reels') }}</p>
                               </div>
                               <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-video-camera-outlined text-purple fs-20"></i>
                               </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-info bg-opacity-10">
                                <div class="flex-grow-1">
                                   <h3 class="fs-20 mb-1">719.2K</h3>
                                   <p class="text-muted mb-0">{{ translate('Total_Views') }}</p>
                                   <small class="text-muted fs-12">+12.5% this week</small>
                               </div>
                               <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-invisible text-info fs-20"></i>
                               </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-danger bg-opacity-10">
                                <div class="flex-grow-1">
                                   <h3 class="fs-20 mb-1">719.2K</h3>
                                   <p class="text-muted mb-0">{{ translate('Total_Likes') }}</p>
                                   <small class="text-muted fs-12">+12.5% this week</small>
                               </div>
                               <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-heart-outlined text-danger fs-20"></i>
                               </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-success bg-opacity-10">
                                <div class="flex-grow-1">
                                   <h3 class="fs-20 mb-1">719.2K</h3>
                                   <p class="text-muted mb-0">{{ translate('Store_Visits') }}</p>
                                   <small class="text-muted fs-12">+12.5% this week</small>
                               </div>
                               <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-home-vs-2-outlined text-success fs-20"></i>
                               </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h4 class="mb-0">{{ translate('Views_Trend') }}</h4>
                </div>
                <div class="card-body px-1 px-sm-2 py-0">
                    <div id="view-trend-chart"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header border-0 pb-0">
                    <h4 class="mb-0">{{ translate('Customer_Engagement') }}</h4>
                </div>
                <div class="card-body px-0 py-0">
                    <div id="customer-engagement-pie-chart" class="chartjs-custom mx-auto" style="max-width:400px;"></div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <h2 class="fs-20 d-flex gap-2 align-items-center text-capitalize lh-1 mb-20">
                <span class="page-header-icon">
                    <i class="tio-filter-list fs-24"></i>
                </span>
                <span>
                    {{ translate('Reels_list') }}
                </span>
                <span class="badge badge-soft-dark">15</span>
            </h2>
            <div class="card">
                <!-- Header -->
                <div class="card-header py-1 border-0">
                    <div class="search--button-wrapper justify-content-end flex-wrap">
                        <h4 class="flex-grow-1 mb-0">{{ translate('All Store Reels List') }}</h4>
                        <form class="search-form min--260">
                            <!-- Search -->
                            <div class="input-group input--group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control h--40px"
                                    placeholder="{{ translate('messages.Search_here') }}" value="" aria-label="Search" tabindex="1">
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
        
                        <div class="hs-unfold mr-2">
                            <a class="btn btn-outline-primary btn-white filter-button-show h--40px js-hs-unfold-invoker px-4 w-max-content" href="javascript:;"
                                data-hs-unfold-invoker="">
                                <i class="tio-filter-list mr-1"></i> {{ translate('Filter') }} <span class="badge badge-success badge-pill ml-1"
                                    id="filter_count"></span>
                            </a>
                        </div>
        
        
                        <!-- End Unfold -->
                    </div>
                </div>
                <!-- End Header -->
        
                <!-- Table -->
                <div class="table-responsive datatable-custom">
                    <table
                        class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table fz--14px text-title">
                        <thead class="thead-light">
                            <tr>
                                <th class="border-0">
                                    {{ translate('Sl') }}
                                </th>
                                <th class="table-column-pl-0 border-0">{{ translate('Reel Id') }}</th>
                                <th class="border-0">{{ translate('Reel information') }}</th>
                                <th class="border-0">{{ translate('Store information') }}</th>
                                <th class="text-center border-0">{{ translate('Total Views') }}</th>
                                <th class="text-center border-0">{{ translate('Total Likes') }}</th>
                                <th class="text-center border-0">{{ translate('Total Store visit') }}</th>
                                <th class="border-0">{{ translate('Reel Duration') }}</th>
                                <th class="text-center border-0">{{ translate('Reels Status') }}</th>
                                <th class="text-center border-0">{{ translate('Status') }}</th>
                                <th class="text-center border-0">{{ translate('Action') }}</th>
                            </tr>
                        </thead>
        
                        <tbody id="set-rows">
        
                            <tr class="status-pending class-all">
                                <td class="">
                                    1
                                </td>
                                <td class="table-column-pl-0">
                                    <a href="#">100545</a>
                                </td>
                                <td>
                                    <a class="media align-items-center min-w-300px overflow-hidden" href="#">
                                        <img class="avatar h-160px w-100px mr-3 onerror-image" 
                                            src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" 
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                        <div title="Authentic Mutton Biryani made with love and traditional spices. Order now!" class="media-body">
                                            <div class="text-title text-hover-primary text-wrap line--limit-2 min-w-160 max-w-200px mb-0">Authentic Mutton Biryani made with love and traditional spices. Order now!</div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <a class="media align-items-center min-w-220 overflow-hidden" href="#">
                                        <img class="avatar avatar-lg mr-3 rounded-circle border onerror-image" 
                                            src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" 
                                            data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                        <div title="Country Fair" class="media-body">
                                            <h5 class="text-hover-primary text-wrap line--limit-1 min-w-160 max-w-200px mb-0">Country Fair</h5>
                                        </div>
                                    </a>
                                </td>
                                <td class="text-center">21K</td>
                                <td class="text-center">21K</td>
                                <td class="text-center">21K</td>
                                <td class="text-capitalize">Always Visible</td>
                                <td class="text-capitalize text-center">
                                    <span class="text-success bg-success bg-opacity-10 px-2 py-1 rounded-20 w-max-content mx-auto">
                                        {{ translate('Live') }}
                                    </span>
                                    <span class="text-info bg-info bg-opacity-10 px-2 py-1 rounded-20 w-max-content mx-auto">
                                        {{ translate('Upcoming') }}
                                    </span>
                                    <span class="text-danger bg-danger bg-opacity-10 px-2 py-1 rounded-20 w-max-content mx-auto">
                                        {{ translate('Expired') }}
                                    </span>
                                    <span class="text-warning bg-warning bg-opacity-10 px-2 py-1 rounded-20 w-max-content mx-auto">
                                        {{ translate('Deactivated') }}
                                    </span>
                                </td>
                                <td>
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox1">
                                        <input type="checkbox" data-url=""
                                        data-id="stocksCheckbox1"
                                        data-type="status"
                                        data-image-on="{{ asset('/public/assets/admin/img/modal/reel-stratus-on.png') }}"
                                        data-image-off="{{ asset('/public/assets/admin/img/modal/reel-stratus-off.png') }}"
                                        data-title-on="{{ translate('want_to_turn_on_the_reel?') }}"
                                        data-title-off="{{ translate('want_to_turn_off_the_reel?') }}"
                                        data-text-on="<p>{{ translate('if_you_turn_on_the_reel,_it_will_be_visible_to_customers.') }}</p>"
                                        data-text-off="<p>{{ translate('if_you_turn_off_the_reel,_it_will_no_longer_be_visible_to_customers.') }}</p>"
                                        class="toggle-switch-input dynamic-checkbox" id="stocksCheckbox1">
                                        <span class="toggle-switch-label">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--warning btn-outline-warning action-btn offcanvas-trigger"
                                            href="javascript:;" data-toggle="offcanvas"
                                                    data-target="#reelsDetailsOffcanvas" title="View details">
                                            <i class="tio-invisible"></i>
                                        </a>
                                        <a class="btn action-btn btn--primary btn-outline-primary" 
                                            href="#" title="Edit item">
                                            <i class="tio-edit"></i>
                                        </a>
                                        <a class="btn action-btn btn-outline-danger btn--danger" data-toggle="modal"
                                                data-target="#confirmation-deletes-1" data-id="campaign-1"
                                                data-message="{{translate('messages.Want_to_delete_this_item')}}"
                                                title="{{translate('messages.delete_campaign')}}"><i class="tio-delete-outlined"></i>
                                        </a>
                                        <div class="modal fade" id="confirmation-deletes-1" tabindex="-1" aria-labelledby="exampleModalLabel"
                                                    aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <form action=""  method="post" id="campaign-1">
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
                                                                <h3 class="mb-2 fs-18">{{ translate('Want to delete this Reel?') }}</h3>
                                                                <p class="text-wrap mb-0">
                                                                    {{ translate('This reel is currently live and has engagement. If you delete it, it will no longer be visible to customers.') }}
                                                                </p>
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
                                    </div>
                                    <form action="" method="post" id="">
                                        <input type="hidden" name="_token" value="" autocomplete="off"> <input type="hidden" name="_method" value="delete">
                                    </form>
                                </td>
                            </tr>
        
                        </tbody>
                    </table>
                </div>
                <!-- End Table -->
        
        
                <hr>
                <div class="page-area">
                    <nav>
                        <ul class="pagination">
        
                            <li class="page-item disabled" aria-disabled="true" aria-label="« Previous">
                                <span class="page-link" aria-hidden="true">‹</span>
                            </li>
        
        
        
        
        
                            <li class="page-item active" aria-current="page"><span class="page-link">1</span></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=2">2</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=3">3</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=4">4</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=5">5</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=6">6</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=7">7</a></li>
                            <li class="page-item"><a class="page-link"
                                    href="http://localhost/6ammart/admin/order/list/all?page=8">8</a></li>
        
        
                            <li class="page-item">
                                <a class="page-link" href="http://localhost/6ammart/admin/order/list/all?page=2" rel="next"
                                    aria-label="Next »">›</a>
                            </li>
                        </ul>
                    </nav>
        
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reels Filter sidebar -->
<div id="datatableFilterSidebar" class="hs-unfold-content_ sidebar sidebar-bordered sidebar-box-shadow initial-hidden w-500px max-w-100">
    <div class="card card-lg sidebar-card sidebar-footer-fixed">
        <div class="card-header">
            <h4 class="card-header-title">{{translate('Filter')}}</h4>

            <!-- Toggle Button -->
            <a class="js-hs-unfold-invoker_ p-1 rounded-circle btn-sm btn-ghost-dark ml-2 filter-button-hide" href="javascript:;">
                <i class="tio-clear tio-lg"></i>
            </a>
            <!-- End Toggle Button -->
        </div>
        <!-- Body -->
        <form class="card-body sidebar-body sidebar-scrollbar" action=" method="POST" id="">
            @csrf

            <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                <label for="" class="form-label">{{translate('Status')}}</label>
                <div class="py-2 px-3 rounded min-h-45px bg-white">
                    <div class="row g-1">
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="active" id="active" name="filter_status[]" checked>
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('active') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="inactive" id="inactive" name="filter_status[]">
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('inactive') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                <label for="" class="form-label">{{translate('Store')}}</label>
                <select name="store[]" id="store_ids" class="form-control js-select2-custom" multiple="multiple">
                    <option value="" disabled selected>{{translate('messages.select_store')}}</option>
                    <option value="demo">Demo Store</option>
                </select>
            </div>

            <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                <label for="" class="form-label">{{translate('Reel_Status')}}</label>
                <div class="py-2 px-3 rounded min-h-45px bg-white">
                    <div class="row g-1">
                        <div class="col-sm-6">
                            <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                <input type="checkbox" value="" id="all" name="reel_status[]" checked>
                                <span class="label-text">
                                    {{ translate('all') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                <input type="checkbox" value="" id="upcoming" name="reel_status[]">
                                <span class="label-text">
                                    {{ translate('upcoming') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                <input type="checkbox" value="" id="live" name="reel_status[]">
                                <span class="label-text">
                                    {{ translate('live') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                <input type="checkbox" value="" id="expired" name="reel_status[]">
                                <span class="label-text">
                                    {{ translate('expired') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                <input type="checkbox" value="" id="deactivated" name="reel_status[]">
                                <span class="label-text">
                                    {{ translate('deactivated') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                <label for="" class="form-label">{{translate('Sort_By')}}</label>
                <div class="py-2 px-3 rounded min-h-45px bg-white">
                    <div class="row g-1">
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="" id="all" name="sort_by[]" checked>
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('all') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="" id="most_viewed" name="sort_by[]">
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('most_viewed') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="" id="most_liked" name="sort_by[]">
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('most_liked') }}
                                </span>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="custom-control custom-radio mb-0">
                                <input type="radio" class="custom-control-input" value="" id="most_store_visit" name="sort_by[]">
                                <span class="custom-control-label fs-12 text-capitalize">
                                    {{ translate('most_store_visit') }}
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                <div class="d-flex flex-column gap-3 gap-sm-4">
                    <div class="">
                        <label for="" class="form-label">{{translate('Reel_Upload_Date')}}</label>
                        <select name="" id="" class="form-control custom-select">
                            <option value="all_time">{{translate('All_Time')}}</option>
                            <option value="this_week">{{translate('This_Week')}}</option>
                            <option value="this_month">{{translate('This_Month')}}</option>
                            <option value="custom">{{translate('Custom')}}</option>
                        </select>
                    </div>
                    <div class="">
                        <label for="" class="form-label">{{translate('Start_Date')}}</label>
                        <input type="date" class="form-control">
                    </div>
                    <div class="">
                        <label for="" class="form-label">{{translate('End_Date')}}</label>
                        <input type="date" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer sidebar-footer">
                <div class="row gx-2">
                    <div class="col">
                        <button type="reset" class="btn btn-block btn--reset" id="reset">{{ translate('Reset') }}</button>
                    </div>
                    <div class="col">
                        <button type="submit" class="btn btn-block btn-primary">{{ translate('messages.Filter') }}</button>
                    </div>
                </div>
            </div>
            <!-- End Footer -->
        </form>
    </div>
</div>
<!-- End Reels Filter sidebar -->

{{-- Reels Details Offcanvas --}}
<div id="reelsDetailsOffcanvas" style="overflow-y: auto;"
        class="custom-offcanvas d-flex flex-column justify-content-between global_guideline_offcanvas">
    <div>
        <div class="custom-offcanvas-header bg--secondary d-flex justify-content-between align-items-center px-3 py-3">
            <h3 class="mb-0">{{ translate('Reels_Details') }}</h3>
            <button type="button"
                    class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary offcanvas-close fz-15px p-0"
                    aria-label="Close">&times;</button>
        </div>
        <div class="p-3">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <div class="reels-video-wrapper">
                    <img src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="" class="reels-thumbnail">

                    <video class="reels-video" width="400" height="470" preload="none" controls>
                        <source src="{{ asset('public/assets/admin/img/reels/sample-video.mp4') }}" type="video/mp4">
                        {{ translate('Your browser does not support the video tag.') }}
                    </video>

                    <div class="reels-play-btn">
                        <div class="d-flex justify-content-center align-items-center w-100 h-100">
                            <i class="tio-play"></i>
                        </div>
                    </div>

                    <div class="reels-close-btn">
                        ✕
                    </div>
                </div>
            </div>
            <div class="bg-light p-3 rounded mb-3">
                <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                    <div class="flex-grow-1">
                        Reel Id: <span class="text-title">100136</span>
                    </div>
                    <span class="text-success bg-success bg-opacity-10 px-2 py-1 rounded-20 w-max-content flex-shrink-0">
                        {{ translate('Live') }}
                    </span>
                </div>
                <h4 class="mb-2">{{ translate('Short Description') }}</h4>
                <p class="fw-medium mb-0">
                    Authentic Mutton Biryani made with love and traditional spices. Order now! 👌
                </p>
            </div>
    
            <div class="bg-light p-3 rounded mb-3">
                <h4 class="mb-2">{{ translate('Reel Validity') }}</h4>
                <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap">
                    <div>
                        Upload Date: <span class="text-title">31 Jul 2025</span>
                    </div>
                    <div>
                        Expired Date: <span class="text-title">31 Aug 2025</span>
                    </div>
                </div>
            </div>

            <div class="bg-light p-3 rounded mb-3">
                <h4 class="d-flex gap-1 mb-2"><i class="tio-shop"></i> {{ translate('Vendor Information') }}</h4>
                <div class="d-flex gap-2 align-items-center">
                    <img class="avatar avatar-70 border onerror-image" 
                        src="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Hungry Puppets</h5>
                        <h6 class="mb-1">+90-495-303235</h6>
                        <p class="fs-12 d-flex gap-1 mb-0">
                            <i class="tio-location-on"></i>
                            Șoseaua Gheorghe Ionescu Sisești nr 236, ‘București, Romania
                        </p>
                    </div>
                </div>
            </div>
    
            <div class="stats-wrapper border-top pt-3">
                <div class="flex-grow-1 text-center stat-item">
                    <div class="fs-12">
                        <i class="tio-invisible fs-16"></i> {{ translate('Views') }}
                    </div>
                    <h5 class="text-info">125.4K</h5>
                </div>

                <div class="flex-grow-1 text-center stat-item">
                    <div class="fs-12">
                        <i class="tio-thumbs-up fs-16"></i> {{ translate('Likes') }}
                    </div>
                    <h5 class="text-info">125.4K</h5>
                </div>

                <div class="flex-grow-1 text-center stat-item">
                    <div class="fs-12">
                        <i class="tio-shop-outlined fs-16"></i> {{ translate('Store_Visits') }}
                    </div>
                    <h5 class="text-info">125.4K</h5>
                </div>
            </div>

        </div>


    </div>
</div>
<div id="offcanvasOverlay" class="offcanvas-overlay"></div>
<!-- End Reels Details Offcanvas -->
@endsection

@push('script')
  <script src="{{ asset('public/assets/admin/vendor/apex/apexcharts.min.js') }}"></script>
@endpush

@push('script_2')
<script>
    "use strict";
    $(document).ready(function() {

        $('.js-select2-custom').each(function () {
            let select2 = $.HSCore.components.HSSelect2.init($(this));
        });

        $('.filter-button-show').on('click', function(){
            $('#datatableFilterSidebar,.hs-unfold-overlay').show(500);
            $('body').addClass('modal-open');
        });

        $('.filter-button-hide').on('click', function(){
            $('#datatableFilterSidebar,.hs-unfold-overlay').hide(500);
            $('body').removeClass('modal-open');
        });

    });

    let viewTrendChart = null;
    let customerEngagementChart = null;

    function initViewTrendStatisticsChart(categories = [], views = []) {
        const chartElement = document.getElementById('view-trend-chart');
        if (!chartElement || views.length === 0) return;

        if (viewTrendChart) {
            viewTrendChart.destroy();
            viewTrendChart = null;
        }

        let maxValue = Math.max(...views);
        maxValue = Math.ceil(maxValue * 1.1);

        const formatToK = (val) => {
            if (val >= 1000) {
                return (val / 1000).toFixed(1).replace('.0', '') + 'k';
            }
            return val;
        };

        const options = {
            series: [{
                name: 'Views',
                data: views
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
                categories: categories
            },
            yaxis: {
                min: 0,
                max: maxValue,
                tickAmount: 4,
                labels: {
                    formatter: function (val) {
                        return formatToK(val);
                    }
                }
            },
            grid: {
                strokeDashArray: 4
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatToK(val);
                    }
                }
            }
        };

        viewTrendChart = new ApexCharts(chartElement, options);
        viewTrendChart.render();
    }

    function loadCustomerEngagementPieChart(data = [25, 75]) {
        const labels = ['Likes', 'Views'];

        const options = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: data,
            labels: labels,
            colors: ['#04BB7B', '#F59E0B'],
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val.toFixed(0) + '%';
                }
            },
            tooltip: {
                enabled: true
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Engagement',
                                formatter: function() {
                                    return '100%';
                                }
                            }
                        }
                    }
                }
            }
        };

        const chartEl = document.querySelector("#customer-engagement-pie-chart");

        if (chartEl) {
            if (customerEngagementChart) {
                customerEngagementChart.updateSeries(data);
            } else {
                customerEngagementChart = new ApexCharts(chartEl, options);
                customerEngagementChart.render();
            }
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        const categories = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const views = [1200, 1800, 1500, 2200, 2700, 3000, 3500, 4000, 4500, 5000, 5500, 6000];

        initViewTrendStatisticsChart(categories, views);
        loadCustomerEngagementPieChart([40, 25]);
    });

    $(document).on('click', '.reels-video-wrapper', function (e) {
        if ($(e.target).closest('.reels-close-btn').length) return;

        const wrapper = $(this);
        const video = wrapper.find('.reels-video')[0];

        $('video').each(function () {
            this.pause();
            this.currentTime = 0;
            $(this).closest('.reels-video-wrapper').find('.reels-video').hide();
            $(this).closest('.reels-video-wrapper').find('.reels-thumbnail, .reels-play-btn').show();
            $(this).closest('.reels-video-wrapper').find('.reels-close-btn').hide();
        });

        wrapper.find('.reels-thumbnail, .reels-play-btn').hide();
        wrapper.find('.reels-video').show();
        wrapper.find('.reels-close-btn').css('display', 'flex');

        video.play();
    });

    $(document).on('click', '.reels-close-btn', function (e) {
        e.stopPropagation();

        const wrapper = $(this).closest('.reels-video-wrapper');
        const video = wrapper.find('.reels-video')[0];

        video.pause();
        video.currentTime = 0;

        wrapper.find('.reels-video').hide();
        wrapper.find('.reels-thumbnail, .reels-play-btn').show();
        wrapper.find('.reels-close-btn').hide();
    });
    

</script>
@endpush
