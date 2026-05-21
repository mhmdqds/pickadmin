@extends('layouts.admin.app')

@section('title',translate('messages.Reels_List'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script type="text/javascript" src="{{asset('public/assets/admin/js/moment.min.js')}}"></script>
<script type="text/javascript" src="{{asset('public/assets/admin/js/daterangepicker.min.js')}}"></script>
@endpush

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="fs-20 text-capitalize lh-1 mb-0">
            {{ translate('Reels_list') }}
        </h2>
        <p class="mb-0">{{ translate('Manage_your_stores_video_content') }}</p>
    </div>
    <div class="row g-3">
        <div class="col-12">
            <div class="card card-body">
                <h3 class="mb-3">{{ translate('Filter Data') }}</h3>
                <form action="">
                    <div class="bg-light p-3 p-xxl-4 rounded mb-3">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div>
                                    <label for="" class="form-label">{{ translate('Date_Range') }}</label>
                                    <select name="" id="" class="form-control custom-select">
                                        <option value="">{{ translate('Custom_Range') }}</option>
                                        <option value="">{{ translate('demo') }}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div>
                                    <label for="" class="form-label">
                                        {{ translate('Start_Range') }}
                                        <span class="text-danger">*</span>
                                        <span class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Select_a_start_date_to_filter_reels_data_within_a_specific_time_frame.') }}">
                                            <i class="tio-info"></i>
                                        </span>
                                    </label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div>
                                    <label for="" class="form-label">
                                        {{ translate('End_Range') }}
                                        <span class="text-danger">*</span>
                                        <span class="text-muted" data-toggle="tooltip" data-placement="top" title="{{ translate('Select_an_end_date_to_filter_reels_data_within_a_specific_time_frame.') }}">
                                            <i class="tio-info"></i>
                                        </span>
                                    </label>
                                    <input type="date" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end">
                        <button type="reset" class="btn btn--reset">{{ translate('Reset') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12">
            <div class="card card-body">
                <h4 class="mb-3">{{ translate('Reels_Overview') }}</h4>
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 border overflow-wrap-anywhere h-100">
                                <div class="bg-purple bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3">
                                    <i class="tio-video-camera-outlined text-purple fs-20"></i>
                                </div>
                                <h3 class="fs-20 mb-1">37</h3>
                                <p class="text-muted mb-0">{{ translate('Total_Reels') }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 border overflow-wrap-anywhere h-100">
                                <div class="bg-info bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3">
                                     <i class="tio-invisible text-info fs-20"></i>
                                </div>
                                <h3 class="fs-20 mb-1">719.2K</h3>
                                <p class="text-muted mb-0">{{ translate('Total_Views') }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 border overflow-wrap-anywhere h-100">
                                <div class="bg-danger bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3">
                                     <i class="tio-heart-outlined text-danger fs-20"></i>
                                </div>
                                <h3 class="fs-20 mb-1">719.2K</h3>
                                <p class="text-muted mb-0">{{ translate('Total_Likes') }}</p>
                            </div>
                        </a>
                    </div>
                    <div class="col-sm-6 col-xl-3">
                        <a href="#" class="h-100">
                            <div class="p-3 rounded-10 border overflow-wrap-anywhere h-100">
                                <div class="bg-success bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3">
                                     <i class="tio-home-vs-2-outlined text-success fs-20"></i>
                                </div>
                                <h3 class="fs-20 mb-1">719.2K</h3>
                                <p class="text-muted mb-0">{{ translate('Store_Visits') }}</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="card">
                <!-- Header -->
                <div class="card-header py-1 border-0">
                    <div class="search--button-wrapper justify-content-end flex-wrap">
                        <h4 class="flex-grow-1 d-flex gap-2 align-items-center text-capitalize lh-1 mb-0">
                            <span>
                                {{ translate('Reels_list') }}
                            </span>
                            <span class="badge badge-soft-dark">15</span>
                        </h4>
                        <form class="search-form min--260">
                            <!-- Search -->
                            <div class="input-group input--group">
                                <input id="datatableSearch_" type="search" name="search" class="form-control h--40px"
                                    placeholder="{{ translate('messages.Search_here') }}" value="" aria-label="Search" tabindex="1">
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                            </div>
                            <!-- End Search -->
                        </form>
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

@push('script_2')
<script>
    "use strict";

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
