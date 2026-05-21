@extends('layouts.admin.app')

@section('title', $store->name . ' - ' . translate('messages.reels'))

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
        @include('admin-views.vendor.view.partials._header', ['store' => $store])

        <div class="row g-3">
            {{-- <div class="col-12">
                <div class="card card-body">
                    <h4 class="mb-3">{{ translate('messages.Reels_Overview') }}</h4>
                    <div class="row g-3">
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-purple bg-opacity-10">
                                <div class="flex-grow-1">
                                    <h3 class="fs-20 mb-1">{{ $overview['total_reels'] }}</h3>
                                    <p class="text-muted mb-0">{{ translate('messages.Total_Reels') }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-video-camera-outlined text-purple fs-20"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-info bg-opacity-10">
                                <div class="flex-grow-1">
                                    <h3 class="fs-20 mb-1">{{ $overview['total_views'] }}</h3>
                                    <p class="text-muted mb-0">{{ translate('messages.Total_Views') }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-invisible text-info fs-20"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-danger bg-opacity-10">
                                <div class="flex-grow-1">
                                    <h3 class="fs-20 mb-1">{{ $overview['total_likes'] }}</h3>
                                    <p class="text-muted mb-0">{{ translate('messages.Total_Likes') }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-heart-outlined text-danger fs-20"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-xl-3">
                            <div class="p-3 rounded-10 d-flex justify-content-between align-items-start gap-2 flex-wrap overflow-wrap-anywhere h-100 bg-success bg-opacity-10">
                                <div class="flex-grow-1">
                                    <h3 class="fs-20 mb-1">{{ $overview['total_store_visits'] }}</h3>
                                    <p class="text-muted mb-0">{{ translate('messages.Store_Visits') }}</p>
                                </div>
                                <div class="flex-shrink-0 bg-white p-2 rounded-10 lh--1">
                                    <i class="tio-home-vs-2-outlined text-success fs-20"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> --}}

            <div class="col-12">
                {{-- <h2 class="fs-20 d-flex gap-2 align-items-center text-capitalize lh-1 mb-20">
                    <span class="page-header-icon">
                        <i class="tio-filter-list fs-24"></i>
                    </span>
                    <span>{{ translate('messages.Reels_list') }}</span>
                    <span class="badge badge-soft-dark">{{ $reels->total() }}</span>
                </h2> --}}
                <div class="card">
                    <div class="card-header py-1 border-0">
                        <div class="search--button-wrapper justify-content-end flex-wrap">
                            <h4 class="flex-grow-1 mb-0">{{ translate('messages.reels_list') }}</h4>
                            <form class="search-form min--260" action="{{ route('admin.store.view', ['store' => $store->id, 'tab' => 'reels']) }}" method="GET">
                                <input type="hidden" name="tab" value="reels">
                                @foreach(request()->except(['search', 'page', 'tab']) as $key => $value)
                                    @if(is_array($value))
                                        @foreach($value as $item)
                                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                        @endforeach
                                    @else
                                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                @endforeach
                                <div class="input-group input--group">
                                    <input id="datatableSearch_" type="search" name="search" class="form-control h--40px"
                                        placeholder="{{ translate('messages.Search_here') }}" value="{{ request('search') }}" aria-label="Search">
                                    <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                                </div>
                            </form>

                            <div class="hs-unfold mr-2">
                                <a class="btn btn-outline-primary btn-white filter-button-show h--40px js-hs-unfold-invoker px-4 w-max-content" href="javascript:;">
                                    <i class="tio-filter-list mr-1"></i> {{ translate('messages.Filter') }}
                                    <span class="badge badge-success badge-pill ml-1" id="filter_count">{{ $filterCount ?: '' }}</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive datatable-custom">
                        <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table fz--14px text-title">
                            <thead class="thead-light">
                                <tr>
                                    <th class="border-0">{{ translate('messages.Sl') }}</th>
                                    <th class="table-column-pl-0 border-0">{{ translate('messages.Reel_Id') }}</th>
                                    <th class="border-0">{{ translate('messages.Reel_information') }}</th>
                                    <th class="border-0">{{ $storeInformationLabel }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Total_Views') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Total_Likes') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Total_Store_visit') }}</th>
                                    <th class="border-0">{{ translate('messages.Reel_Duration') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Reels_Status') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Status') }}</th>
                                    <th class="text-center border-0">{{ translate('messages.Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="set-rows">
                                @forelse ($reels as $key => $reel)
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
                                        <td class="table-column-pl-0"><a href="javascript:;">{{ $reel->id }}</a></td>
                                        <td>
                                            <a class="media align-items-center min-w-300px overflow-hidden" href="javascript:;">
                                                <img class="avatar h-160px w-100px mr-3 onerror-image"
                                                    src="{{ $reel->thumbnail_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                                <div class="media-body" title="{{ $reel->description }}">
                                                    <div class="text-title text-wrap line--limit-2 min-w-160 max-w-200px mb-0">{{ $reel->description }}</div>
                                                </div>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="media align-items-center min-w-220 overflow-hidden" href="javascript:;">
                                                <img class="avatar avatar-lg mr-3 rounded-circle border onerror-image"
                                                    src="{{ $reel->store?->logo_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                    data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" alt="">
                                                <div class="media-body" title="{{ $reel->store?->name }}">
                                                    <h5 class="text-wrap line--limit-1 min-w-160 max-w-200px mb-0">{{ $reel->store?->name ?? $deletedStoreLabel }}</h5>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="text-center">{{ $reel->total_views }}</td>
                                        <td class="text-center">{{ $reel->total_likes }}</td>
                                        <td class="text-center">{{ $reel->total_store_visits }}</td>
                                        <td class="text-capitalize">
                                            @if ($reel->is_always_visible)
                                                {{ translate('messages.Always_Visible') }}
                                            @else
                                                {{ optional($reel->start_date)->format('d M Y') }} - {{ optional($reel->end_date)->format('d M Y') }}
                                            @endif
                                        </td>
                                        <td class="text-capitalize text-center">
                                            <span class="{{ $statusClasses[$reel->reel_status_label] ?? 'text-muted bg-light' }} px-2 py-1 rounded-20 w-max-content mx-auto d-inline-block">
                                                {{ translate('messages.' . ucfirst($reel->reel_status_label)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <label class="toggle-switch toggle-switch-sm" for="reelStatus{{ $reel->id }}">
                                                <input type="checkbox"
                                                    data-id="reelStatus{{ $reel->id }}"
                                                    data-image-on="{{ asset('public/assets/admin/img/modal/reel-stratus-on.png') }}"
                                                    data-image-off="{{ asset('public/assets/admin/img/modal/reel-stratus-off.png') }}"
                                                    data-title-on="{{ translate('messages.want_to_turn_on_the_reel?') }}"
                                                    data-title-off="{{ translate('messages.want_to_turn_off_the_reel?') }}"
                                                    data-text-on="<p>{{ translate('messages.if_you_turn_on_the_reel,_it_will_be_visible_to_customers.') }}</p>"
                                                    data-text-off="<p>{{ translate('messages.if_you_turn_off_the_reel,_it_will_no_longer_be_visible_to_customers.') }}</p>"
                                                    class="toggle-switch-input dynamic-checkbox"
                                                    id="reelStatus{{ $reel->id }}" {{ $reel->status ? 'checked' : '' }}>
                                                <span class="toggle-switch-label">
                                                    <span class="toggle-switch-indicator"></span>
                                                </span>
                                            </label>
                                            <form action="{{ route('admin.reels.status', [$reel->id, $reel->status ? 0 : 1]) }}"
                                                  method="GET" id="reelStatus{{ $reel->id }}_form">
                                            </form>
                                        </td>
                                        <td>
                                            <div class="btn--container justify-content-center">
                                                <a class="btn action-btn btn--warning btn-outline-warning action-btn offcanvas-trigger"
                                                   href="javascript:;" data-target="#reelsDetailsOffcanvas{{ $reel->id }}" title="{{ translate('messages.view') }}">
                                                    <i class="tio-invisible"></i>
                                                </a>
                                                <a class="btn action-btn btn--primary btn-outline-primary"
                                                   href="{{ route('admin.reels.edit', $reel->id) }}" title="{{ translate('messages.edit') }}">
                                                    <i class="tio-edit"></i>
                                                </a>
                                                <a class="btn action-btn btn-outline-danger btn--danger" data-toggle="modal"
                                                   data-target="#confirmation-deletes-{{ $reel->id }}" title="{{ translate('messages.delete') }}">
                                                    <i class="tio-delete-outlined"></i>
                                                </a>
                                            </div>

                                            <div class="modal fade" id="confirmation-deletes-{{ $reel->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered status-warning-modal" role="document">
                                                    <form action="{{ route('admin.reels.destroy', $reel->id) }}" method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <div class="modal-content">
                                                            <div class="modal-header px-2 pt-2 border-0">
                                                                <button type="button" class="close btn btn--reset btn-circle" data-dismiss="modal" aria-label="Close">
                                                                    <span aria-hidden="true" class="tio-clear fs-20 opacity-70"></span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body pb-4 pt-0">
                                                                <div class="max-349 mx-auto mt-2">
                                                                    <div class="text-center">
                                                                        <img src="{{ asset('public/assets/admin/img/delete.png') }}" alt="icon" class="mb-20">
                                                                        <h3 class="mb-2 fs-18">{{ translate('messages.Want_to_delete_this_Reel') }}</h3>
                                                                        <p class="text-wrap mb-0">{{ translate('messages.This_reel_will_no_longer_be_visible_after_deletion') }}</p>
                                                                    </div>
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

                    @if(count($reels) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $reels->links() !!}
                    </div>
                </div>
            </div>
        </div>

        <div id="datatableFilterSidebar" class="hs-unfold-content_ sidebar sidebar-bordered sidebar-box-shadow initial-hidden w-500px max-w-100">
            <div class="card card-lg sidebar-card sidebar-footer-fixed">
                <div class="card-header">
                    <h4 class="card-header-title">{{ translate('messages.Filter') }}</h4>
                    <a class="js-hs-unfold-invoker_ p-1 rounded-circle btn-sm btn-ghost-dark ml-2 filter-button-hide" href="javascript:;">
                        <i class="tio-clear tio-lg"></i>
                    </a>
                </div>

                <form class="card-body sidebar-body sidebar-scrollbar" action="{{ route('admin.store.view', ['store' => $store->id, 'tab' => 'reels']) }}" method="GET" id="reels_filter_form">
                    <input type="hidden" name="tab" value="reels">
                    <input type="hidden" name="search" value="{{ request('search') }}">

                    <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                        <label class="form-label">{{ translate('messages.Status') }}</label>
                        <div class="py-2 px-3 rounded min-h-45px bg-white">
                            <div class="row g-1">
                                <div class="col-sm-6">
                                    <label class="custom-control custom-radio mb-0">
                                        <input type="radio" class="custom-control-input" value="active" name="status_filter" {{ request('status_filter', 'active') === 'active' ? 'checked' : '' }}>
                                        <span class="custom-control-label fs-12 text-capitalize">{{ translate('messages.active') }}</span>
                                    </label>
                                </div>
                                <div class="col-sm-6">
                                    <label class="custom-control custom-radio mb-0">
                                        <input type="radio" class="custom-control-input" value="inactive" name="status_filter" {{ request('status_filter') === 'inactive' ? 'checked' : '' }}>
                                        <span class="custom-control-label fs-12 text-capitalize">{{ translate('messages.inactive') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                        <label class="form-label">{{ translate('messages.Reel_Status') }}</label>
                        <div class="py-2 px-3 rounded min-h-45px bg-white">
                            <div class="row g-1">
                                @php
                                    $selectedStatuses = (array) request('reel_status', ['all']);
                                @endphp
                                @foreach (['all', 'upcoming', 'live', 'expired', 'deactivated'] as $status)
                                    <div class="col-sm-6">
                                        <label class="custom_checkbox d-flex align-items-center gap-1 flex-grow-1 m-0 pt-2px">
                                            <input type="checkbox" value="{{ $status }}" name="reel_status[]" {{ in_array($status, $selectedStatuses) ? 'checked' : '' }}>
                                            <span class="label-text">{{ translate('messages.' . $status) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                        <label class="form-label">{{ translate('messages.Sort_By') }}</label>
                        <div class="py-2 px-3 rounded min-h-45px bg-white">
                            <div class="row g-1">
                                @foreach ([
                                    'all' => 'all',
                                    'most_viewed' => 'most_viewed',
                                    'most_liked' => 'most_liked',
                                    'most_store_visit' => 'most_store_visit',
                                ] as $sortValue => $sortLabel)
                                    <div class="col-sm-6">
                                        <label class="custom-control custom-radio mb-0">
                                            <input type="radio" class="custom-control-input" value="{{ $sortValue }}" name="sort_by" {{ request('sort_by', 'all') === $sortValue ? 'checked' : '' }}>
                                            <span class="custom-control-label fs-12 text-capitalize">{{ translate('messages.' . $sortLabel) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-light rounded p-xxl-20 p-3 mb-3 mb-sm-4">
                        <div class="d-flex flex-column gap-3 gap-sm-4">
                            <div>
                                <label class="form-label">{{ translate('messages.Reel_Upload_Date') }}</label>
                                <select name="filter_date" id="filter_date" class="form-control custom-select">
                                    <option value="all_time" {{ request('filter_date', 'all_time') === 'all_time' ? 'selected' : '' }}>{{ translate('messages.All_Time') }}</option>
                                    <option value="this_week" {{ request('filter_date') === 'this_week' ? 'selected' : '' }}>{{ translate('messages.This_Week') }}</option>
                                    <option value="this_month" {{ request('filter_date') === 'this_month' ? 'selected' : '' }}>{{ translate('messages.This_Month') }}</option>
                                    <option value="custom" {{ request('filter_date') === 'custom' ? 'selected' : '' }}>{{ translate('messages.Custom') }}</option>
                                </select>
                            </div>
                            <div id="custom_date_wrapper" class="{{ request('filter_date') === 'custom' ? '' : 'd-none' }}">
                                <div class="mb-3">
                                    <label class="form-label">{{ translate('messages.Start_Date') }}</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                                <div>
                                    <label class="form-label">{{ translate('messages.End_Date') }}</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer sidebar-footer">
                        <div class="row gx-2">
                            <div class="col">
                                <a href="{{ route('admin.store.view', ['store' => $store->id, 'tab' => 'reels']) }}" class="btn btn-block btn--reset" id="reset">{{ translate('messages.Reset') }}</a>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-block btn-primary">{{ translate('messages.Filter') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
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
                                <div class="flex-grow-1">{{ translate('messages.Reel_Id') }}: <span class="text-title">{{ $reel->id }}</span></div>
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
                                <div>{{ translate('messages.Upload_Date') }}: <span class="text-title">{{ optional($reel->created_at)->format('d M Y') }}</span></div>
                                <div>{{ translate('messages.Expired_Date') }}:
                                    <span class="text-title">
                                        {{ $reel->is_always_visible ? translate('messages.Always_Visible') : optional($reel->end_date)->format('d M Y') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded mb-3">
                            <h4 class="d-flex gap-1 mb-2"><i class="tio-shop"></i> {{ translate('messages.Vendor_Information') }}</h4>
                            <div class="d-flex gap-2 align-items-center">
                                <img class="avatar avatar-70 border onerror-image" src="{{ $reel->store?->logo_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}">
                                <div class="flex-grow-1">
                                    <h5 class="mb-1">{{ $reel->store?->name ?? $deletedStoreLabel }}</h5>
                                    <h6 class="mb-1">{{ $reel->store?->phone ?? '' }}</h6>
                                    <p class="fs-12 d-flex gap-1 mb-0">
                                        <i class="tio-location-on"></i>
                                        {{ $reel->store?->address ?? '' }}
                                    </p>
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

        $(document).ready(function() {
            $('.filter-button-show').on('click', function(){
                $('#datatableFilterSidebar,.hs-unfold-overlay').show(500);
                $('body').addClass('modal-open');
            });

            $('.filter-button-hide').on('click', function(){
                $('#datatableFilterSidebar,.hs-unfold-overlay').hide(500);
                $('body').removeClass('modal-open');
            });

            $('#filter_date').on('change', function () {
                $('#custom_date_wrapper').toggleClass('d-none', $(this).val() !== 'custom');
            });

            $(document).on('click', '.reels-video-wrapper', function (e) {
                if ($(e.target).closest('.reels-close-btn').length) {
                    return;
                }

                const wrapper = $(this);
                const video = wrapper.find('.reels-video').get(0);

                if (!video) {
                    return;
                }

                $('video.reels-video').each(function () {
                    this.pause();
                    this.currentTime = 0;
                    const currentWrapper = $(this).closest('.reels-video-wrapper');
                    currentWrapper.find('.reels-video').hide();
                    currentWrapper.find('.reels-thumbnail, .reels-play-btn').show();
                    currentWrapper.find('.reels-close-btn').hide();
                });

                wrapper.find('.reels-thumbnail, .reels-play-btn').hide();
                wrapper.find('.reels-video').show();
                wrapper.find('.reels-close-btn').css('display', 'flex');
                video.load();
                video.play().catch(function () {
                    wrapper.find('.reels-video').attr('controls', true);
                });
            });

            $(document).on('click', '.reels-close-btn', function (e) {
                e.stopPropagation();

                const wrapper = $(this).closest('.reels-video-wrapper');
                const video = wrapper.find('.reels-video').get(0);

                if (video) {
                    video.pause();
                    video.currentTime = 0;
                }

                wrapper.find('.reels-video').hide();
                wrapper.find('.reels-thumbnail, .reels-play-btn').show();
                wrapper.find('.reels-close-btn').hide();
            });

            $(document).on('click', '.offcanvas-close, #offcanvasOverlay', function () {
                $('video.reels-video').each(function () {
                    this.pause();
                    this.currentTime = 0;
                    const wrapper = $(this).closest('.reels-video-wrapper');
                    wrapper.find('.reels-video').hide();
                    wrapper.find('.reels-thumbnail, .reels-play-btn').show();
                    wrapper.find('.reels-close-btn').hide();
                });
            });
        });
    </script>
@endpush
