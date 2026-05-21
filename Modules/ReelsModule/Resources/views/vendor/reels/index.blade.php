@extends('layouts.vendor.app')

@section('title', translate('messages.Reels_List'))
@section('vendor_reels', 'active')
@section('vendor_reels_list', 'active')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@php
    $storeLabel = \App\CentralLogics\Helpers::getStoreLabelByModuleType($store?->module_type ?? $store?->module?->module_type);
    $storeInformationLabel = $storeLabel . ' ' . translate('messages.information');
    $deletedStoreLabel = $storeLabel . ' ' . translate('messages.deleted');
@endphp

@section('content')
<div class="content container-fluid">
    <div class="mb-3">
        <h2 class="fs-20 text-capitalize lh-1 mb-0">{{ translate('messages.Reels_list') }}</h2>
        <p class="mb-0">{{ translate('messages.Manage_your_stores_video_content') }}</p>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="card card-body">
                <h3 class="mb-3">{{ translate('messages.Filter_Data') }}</h3>
                <form action="{{ route('vendor.reels.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <select class="form-control set-filter" name="filter"
                                    data-url="{{ url()->full() }}" data-filter="filter">
                                <option value="all_time" {{ request('filter', 'all_time') === 'all_time' ? 'selected' : '' }}>{{ translate('messages.All_Time') }}</option>
                                <option value="this_year" {{ request('filter') === 'this_year' ? 'selected' : '' }}>{{ translate('messages.This_Year') }}</option>
                                <option value="previous_year" {{ request('filter') === 'previous_year' ? 'selected' : '' }}>{{ translate('messages.Previous_Year') }}</option>
                                <option value="this_month" {{ request('filter') === 'this_month' ? 'selected' : '' }}>{{ translate('messages.This_Month') }}</option>
                                <option value="this_week" {{ request('filter') === 'this_week' ? 'selected' : '' }}>{{ translate('messages.This_Week') }}</option>
                                <option value="custom" {{ request('filter') === 'custom' ? 'selected' : '' }}>{{ translate('messages.Custom') }}</option>
                            </select>
                        </div>
                        @if (request('filter') === 'custom')
                            <div class="col-sm-6 col-md-3">
                                <input type="date" name="from" id="from_date" class="form-control"
                                    placeholder="{{ translate('Start Date') }}"
                                    value="{{ request('from') }}" required>
                            </div>
                            <div class="col-sm-6 col-md-3">
                                <input type="date" name="to" id="to_date" class="form-control"
                                    placeholder="{{ translate('End Date') }}"
                                    value="{{ request('to') }}" required>
                            </div>
                        @endif
                        <div class="col-sm-6 col-md-3 ml-auto">
                            <button type="submit" class="btn btn-primary btn-block h--45px">{{ translate('messages.Filter') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12">
            <div class="card card-body">
                <h4 class="mb-3">{{ translate('messages.Reels_Overview') }}</h4>
                <div class="row g-3">
                    <div class="col-sm-6 col-xl-3"><div class="p-3 rounded-10 border h-100"><div class="bg-purple bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3"><i class="tio-video-camera-outlined text-purple fs-20"></i></div><h3 class="fs-20 mb-1">{{ $overview['total_reels'] }}</h3><p class="text-muted mb-0">{{ translate('messages.Total_Reels') }}</p></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="p-3 rounded-10 border h-100"><div class="bg-info bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3"><i class="tio-invisible text-info fs-20"></i></div><h3 class="fs-20 mb-1">{{ $overview['total_views'] }}</h3><p class="text-muted mb-0">{{ translate('messages.Total_Views') }}</p></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="p-3 rounded-10 border h-100"><div class="bg-danger bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3"><i class="tio-heart-outlined text-danger fs-20"></i></div><h3 class="fs-20 mb-1">{{ $overview['total_likes'] }}</h3><p class="text-muted mb-0">{{ translate('messages.Total_Likes') }}</p></div></div>
                    <div class="col-sm-6 col-xl-3"><div class="p-3 rounded-10 border h-100"><div class="bg-success bg-opacity-10 p-2 rounded-10 lh--1 w-max-content mb-3"><i class="tio-home-vs-2-outlined text-success fs-20"></i></div><h3 class="fs-20 mb-1">{{ $overview['total_store_visits'] }}</h3><p class="text-muted mb-0">{{ translate('messages.Store_Visits') }}</p></div></div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header py-1 border-0">
                    <div class="search--button-wrapper justify-content-end flex-wrap">
                        <h4 class="flex-grow-1 d-flex gap-2 align-items-center text-capitalize lh-1 mb-0">
                            <span>{{ translate('messages.Reels_list') }}</span>
                            <span class="badge badge-soft-dark">{{ $reels->total() }}</span>
                        </h4>
                        <form class="search-form min--260" action="{{ route('vendor.reels.index') }}" method="GET">
                            @foreach(request()->except(['search', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <div class="input-group input--group">
                                <input type="search" name="search" class="form-control h--40px" placeholder="{{ translate('messages.Search_here') }}" value="{{ request('search') }}">
                                <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="table-responsive datatable-custom">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table fz--14px text-title">
                        <thead class="thead-light">
                            <tr>
                                <th>{{ translate('messages.Sl') }}</th>
                                <th>{{ translate('messages.Reel_Id') }}</th>
                                <th>{{ translate('messages.Reel_information') }}</th>
                                <th>{{ $storeInformationLabel }}</th>
                                <th class="text-center">{{ translate('messages.Total_Views') }}</th>
                                <th class="text-center">{{ translate('messages.Total_Likes') }}</th>
                                <th class="text-center">{{ translate('messages.Total_Store_visit') }}</th>
                                <th>{{ translate('messages.Reel_Duration') }}</th>
                                <th class="text-center">{{ translate('messages.Reels_Status') }}</th>
                                <th class="text-center">{{ translate('messages.Status') }}</th>
                                <th class="text-center">{{ translate('messages.Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reels as $key => $reel)
                                @php
                                    $statusClasses = [
                                        'live' => 'text-success bg-success bg-opacity-10',
                                        'upcoming' => 'text-info bg-info bg-opacity-10',
                                        'expired' => 'text-danger bg-danger bg-opacity-10',
                                        'deactivated' => 'text-warning bg-warning bg-opacity-10',
                                    ];
                                @endphp
                                <tr>
                                    <td>{{ $reels->firstItem() + $key }}</td>
                                    <td><a href="javascript:;">{{ $reel->id }}</a></td>
                                    <td>
                                        <a class="media align-items-center min-w-300px overflow-hidden" href="javascript:;">
                                            <img class="avatar h-160px w-100px mr-3 onerror-image" src="{{ $reel->thumbnail_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                            <div class="media-body"><div class="text-title text-wrap line--limit-2 min-w-160 max-w-200px mb-0">{{ $reel->description }}</div></div>
                                        </a>
                                    </td>
                                    <td>
                                        <a class="media align-items-center min-w-220 overflow-hidden" href="javascript:;">
                                            <img class="avatar avatar-lg mr-3 rounded-circle border onerror-image" src="{{ $reel->store?->logo_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                            <div class="media-body"><h5 class="text-wrap line--limit-1 min-w-160 max-w-200px mb-0">{{ $reel->store?->name ?? $deletedStoreLabel }}</h5></div>
                                        </a>
                                    </td>
                                    <td class="text-center">{{ $reel->total_views }}</td>
                                    <td class="text-center">{{ $reel->total_likes }}</td>
                                    <td class="text-center">{{ $reel->total_store_visits }}</td>
                                    <td>@if($reel->is_always_visible) {{ translate('messages.Always_Visible') }} @else {{ optional($reel->start_date)->format('d M Y') }} - {{ optional($reel->end_date)->format('d M Y') }} @endif</td>
                                    <td class="text-center"><span class="{{ $statusClasses[$reel->reel_status_label] ?? 'text-muted bg-light' }} px-2 py-1 rounded-20 w-max-content mx-auto d-inline-block">{{ translate('messages.' . ucfirst($reel->reel_status_label)) }}</span></td>
                                    <td>
                                        <label class="toggle-switch toggle-switch-sm" for="reelStatus{{ $reel->id }}">
                                            <input type="checkbox" data-id="reelStatus{{ $reel->id }}" data-image-on="{{ asset('public/assets/admin/img/modal/reel-stratus-on.png') }}" data-image-off="{{ asset('public/assets/admin/img/modal/reel-stratus-off.png') }}" data-title-on="{{ translate('messages.want_to_turn_on_the_reel?') }}" data-title-off="{{ translate('messages.want_to_turn_off_the_reel?') }}" data-text-on="<p>{{ translate('messages.if_you_turn_on_the_reel,_it_will_be_visible_to_customers.') }}</p>" data-text-off="<p>{{ translate('messages.if_you_turn_off_the_reel,_it_will_no_longer_be_visible_to_customers.') }}</p>" class="toggle-switch-input dynamic-checkbox" id="reelStatus{{ $reel->id }}" {{ $reel->status ? 'checked' : '' }}>
                                            <span class="toggle-switch-label"><span class="toggle-switch-indicator"></span></span>
                                        </label>
                                        <form action="{{ route('vendor.reels.status', [$reel->id, $reel->status ? 0 : 1]) }}" method="GET" id="reelStatus{{ $reel->id }}_form"></form>
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--warning btn-outline-warning offcanvas-trigger" href="javascript:;" data-target="#reelsDetailsOffcanvas{{ $reel->id }}" title="{{ translate('messages.view') }}"><i class="tio-invisible"></i></a>
                                            <a class="btn action-btn btn--primary btn-outline-primary" href="{{ route('vendor.reels.edit', $reel->id) }}" title="{{ translate('messages.edit') }}"><i class="tio-edit"></i></a>
                                            <a class="btn action-btn btn-outline-danger btn--danger" data-toggle="modal" data-target="#confirmation-deletes-{{ $reel->id }}" title="{{ translate('messages.delete') }}"><i class="tio-delete-outlined"></i></a>
                                        </div>


                                            <div class="modal fade" id="confirmation-deletes-{{ $reel->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered status-warning-modal" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header px-2 pt-2 border-0">
                                                            <button type="button"
                                                                class="close btn btn--reset btn-circle"
                                                                data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true" class="tio-clear fs-20 opacity-70"></span>
                                                            </button>
                                                        </div>
                                                            <form action="{{ route('vendor.reels.destroy', $reel->id) }}" method="post">
                                                                @csrf
                                                                @method('delete')


                                                            <div class="modal-body pb-4 pt-0">
                                                                <div class="max-349 mx-auto mt-2 mb-20">
                                                                    <div class="text-center">
                                                                    <img src="{{ asset('public/assets/admin/img/delete.png') }}" alt="icon" class="mb-20">
                                                                    <h3 class="mb-2 fs-18">{{ translate('messages.Want_to_delete_this_Reel') }}</h3>
                                                                    <p class="text-wrap mb-0">
                                                                        @if ($reel->reel_status_label == 'live')
                                                                            {{ translate('This reel is currently live and has engagement. If you delete it, it will no longer be visible to customers.') }}
                                                                            @else
                                                                            {{ translate('Please confirm before deleting this reel. This will permanently remove this from the reel list.') }}

                                                                         @endif
                                                                    </p>
                                                                    </div>
                                                                </div>

                                                                <div class="modal-footer justify-content-center border-0 pt-0 pb-4 gap-2">
                                                                    <button type="submit" class="btn min-w-120px btn-danger min-h-45px">{{ translate('messages.Yes,_Delete') }}</button>
                                                                    <button type="button" class="btn min-w-120px btn--reset min-h-45px" data-dismiss="modal">{{ translate('messages.cancel') }}</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>


                                    </td>
                                </tr>
                            @empty
                                   <tr>
                                        <td colspan="11">
                                            <div class="empty--data">
                                                <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                                                <h5>{{ translate('messages.no_data_found') }}</h5>
                                            </div>
                                        </td>
                                    </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($reels->count())
                    <div class="card-footer border-0">{!! $reels->links() !!}</div>
                @endif
            </div>
        </div>
    </div>

    @foreach ($reels as $reel)
        @php
            $offcanvasStatusClasses = [
                'live' => 'text-success bg-success bg-opacity-10',
                'upcoming' => 'text-info bg-info bg-opacity-10',
                'expired' => 'text-danger bg-danger bg-opacity-10',
                'deactivated' => 'text-warning bg-warning bg-opacity-10',
            ];
        @endphp
        <div id="reelsDetailsOffcanvas{{ $reel->id }}" style="overflow-y: auto;" class="custom-offcanvas d-flex flex-column justify-content-between global_guideline_offcanvas">
            <div>
                <div class="custom-offcanvas-header bg--secondary d-flex justify-content-between align-items-center px-3 py-3">
                    <h3 class="mb-0">{{ translate('messages.Reels_Details') }}</h3>
                    <button type="button" class="btn-close w-25px h-25px border rounded-circle d-center bg--secondary offcanvas-close fz-15px p-0" aria-label="Close">&times;</button>
                </div>
                <div class="p-3">
                    <div class="d-flex justify-content-center align-items-center mb-3">
                        <div class="reels-video-wrapper">
                            <img src="{{ $reel->thumbnail_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="" class="reels-thumbnail">
                            <video class="reels-video" width="400" height="470" preload="none" controls>
                                <source src="{{ $reel->video_full_url }}" type="video/mp4">
                                {{ translate('messages.Your_browser_does_not_support_the_video_tag.') }}
                            </video>
                            <div class="reels-play-btn">
                                <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                    <i class="tio-play"></i>
                                </div>
                            </div>
                            <div class="reels-close-btn">✕</div>
                        </div>
                    </div>
                    <div class="bg-light p-3 rounded mb-3">
                        <div class="d-flex gap-2 align-items-center justify-content-between mb-3">
                            <div class="flex-grow-1">
                                {{ translate('messages.Reel_Id') }}: <span class="text-title">{{ $reel->id }}</span>
                            </div>
                            <span class="{{ $offcanvasStatusClasses[$reel->reel_status_label] ?? 'text-muted bg-light' }} px-2 py-1 rounded-20 w-max-content flex-shrink-0">
                                {{ translate('messages.' . ucfirst($reel->reel_status_label)) }}
                            </span>
                        </div>
                        <h4 class="mb-2">{{ translate('messages.Short_Description') }}</h4>
                        <p class="fw-medium mb-0">{{ $reel->description }}</p>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <h4 class="mb-2">{{ translate('messages.Reel_Validity') }}</h4>
                        <div class="d-flex gap-2 align-items-center justify-content-between flex-wrap">
                            <div>
                                {{ translate('messages.Upload_Date') }}: <span class="text-title">{{ optional($reel->created_at)->format('d M Y') }}</span>
                            </div>
                            <div>
                                {{ translate('messages.Expired_Date') }}:
                                <span class="text-title">
                                    {{ $reel->is_always_visible ? translate('messages.Always_Visible') : optional($reel->end_date)->format('d M Y') }}
                                </span>
                            </div>
                        </div>
                    </div>


                    <div class="stats-wrapper border-top pt-3">
                        <div class="flex-grow-1 text-center stat-item">
                            <div class="fs-12"><i class="tio-invisible fs-16"></i> {{ translate('messages.Views') }}</div>
                            <h5 class="text-info">{{ $reel->total_views }}</h5>
                        </div>
                        <div class="flex-grow-1 text-center stat-item">
                            <div class="fs-12"><i class="tio-thumbs-up fs-16"></i> {{ translate('messages.Likes') }}</div>
                            <h5 class="text-info">{{ $reel->total_likes }}</h5>
                        </div>
                        <div class="flex-grow-1 text-center stat-item">
                            <div class="fs-12"><i class="tio-shop-outlined fs-16"></i> {{ translate('messages.Store_Visits') }}</div>
                            <h5 class="text-info">{{ $reel->total_store_visits }}</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div id="offcanvasOverlay" class="offcanvas-overlay"></div>
</div>
@endsection

@push('script_2')
<script>
"use strict";
$(document).on('click', '.offcanvas-trigger', function () { $($(this).data('target')).addClass('active'); $('body').addClass('overflow-hidden'); });
$(document).on('click', '.offcanvas-close, #offcanvasOverlay', function () {
    $(this).closest('.custom-offcanvas').removeClass('active');
    $('body').removeClass('overflow-hidden');
    $('video.reels-video').each(function () {
        this.pause();
        this.currentTime = 0;
        const wrapper = $(this).closest('.reels-video-wrapper');
        wrapper.find('.reels-video').hide();
        wrapper.find('.reels-thumbnail, .reels-play-btn').show();
        wrapper.find('.reels-close-btn').hide();
    });
});
$(document).on('click', '.reels-video-wrapper', function (e) {
    if ($(e.target).closest('.reels-close-btn').length) return;
    const wrapper = $(this);
    const video = wrapper.find('.reels-video').get(0);
    $('video.reels-video').each(function () { this.pause(); this.currentTime = 0; const currentWrapper = $(this).closest('.reels-video-wrapper'); currentWrapper.find('.reels-video').hide(); currentWrapper.find('.reels-thumbnail, .reels-play-btn').show(); currentWrapper.find('.reels-close-btn').hide(); });
    wrapper.find('.reels-thumbnail, .reels-play-btn').hide(); wrapper.find('.reels-video').show(); wrapper.find('.reels-close-btn').css('display', 'flex');
    if (video) { wrapper.find('.reels-video').attr('controls', true); video.play(); }
});
$(document).on('click', '.reels-close-btn', function (e) {
    e.stopPropagation();
    const wrapper = $(this).closest('.reels-video-wrapper');
    const video = wrapper.find('.reels-video').get(0);
    if (video) { video.pause(); video.currentTime = 0; }
    wrapper.find('.reels-video').hide(); wrapper.find('.reels-thumbnail, .reels-play-btn').show(); wrapper.find('.reels-close-btn').hide();
});
</script>
@endpush
