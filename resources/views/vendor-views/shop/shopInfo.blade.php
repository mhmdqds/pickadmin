@php
    $vendorData = \App\CentralLogics\Helpers::get_store_data();
    $title = $vendorData?->module_type == 'rental' && addon_published_status('Rental') ? 'Provider' : 'Store';
    $verified_seller_badge = \App\CentralLogics\Helpers::get_business_settings('verified_seller_badge');
    $admin_commission = \App\CentralLogics\Helpers::get_business_settings('admin_commission');
@endphp
@extends('layouts.vendor.app')
@section('title', translate('messages.store_view'))
@push('css_or_js')
    <!-- Custom styles for this page -->
@endpush

@section('content')
    <div class="content container-fluid">

        <div class="card mb-3">
            <div class="page-header px-3 pt-3 pb-3 border-bottom">
                <div class="d-flex gap-2 align-items-center flex-wrap justify-content-between">
                    <h3 class="text-capitalize mb-0">
                        <span>
                            {{ translate('messages.my_' . $title . '_info') }}
                        </span>
                    </h3>
                    <div class="mb-0">
                        <a class="btn btn--primary mb-0 fw-medium" href="{{ route('vendor.shop.edit') }}"><i
                                class="tio-edit mr-1"></i>{{ translate('messages.edit_' . $title . '_information') }}</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="">
                    @if ($shop->cover_photo)
                        <div>
                            <img class="my-restaurant-img onerror-image" src="{{ $shop->cover_photo_full_url }}">
                        </div>
                    @endif
                    <div class="my-resturant--card shadow-none">

                        <div class="my-resturant--avatar onerror-image">
                            <img src="{{ $shop->logo_full_url }}" class="border"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}" alt="">
                        </div>


                        <div class="my-resturant--content_wrap flex-grow-1">
                            <div class="info-area mb-20">
                                <h3 class="fs-20 mb-0 fw-bold text--title d-flex align-items-center gap-0">
                                    {{ $shop->name }}
                                    @if ($verified_seller_badge == 1 && $shop->storeConfig?->verified_seller)
                                        <span class="w-20px" data-toggle="tooltip" data-placement="top"
                                            data-original-title="{{ translate('messages.Verified Badge') }}">
                                            <img width="18" height="18"
                                                src="{{ asset('public/assets/admin/img/badge-big.png') }}" alt=""
                                                class="w-100 h-100 rounded-0 object-contain">
                                        </span>
                                    @endif

                                </h3>
                                <span class="fs-12 lh--12 text-8797AB">{{ translate('Created at') }}
                                    {{ \App\CentralLogics\Helpers::date_format($shop->created_at) }}</span>
                            </div>
                            <div class="my-resturant--content">
                                <div class="row g-3 justify-content-between mb-3">
                                    <div class="col-auto">
                                        <div class="details-single d-flex align-items-center gap-2">
                                            <img src="{{ asset('public/assets/admin/img/icons/business-i.png') }}"
                                                width="36" height="36" class="rounded" alt="">
                                            <div>
                                                <h5 class="lh--12 mb-2px color-3C3C3C">
                                                    {{ translate('messages.Business Plan') }}
                                                </h5>
                                                <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                                                    @if ($shop->store_business_model == 'commission')
                                                        {{ translate('messages.Commission Base') }}
                                                    @elseif(in_array($shop->store_business_model, ['subscription', 'unsubscribed']))
                                                        {{ translate('messages.Subscription Base') }}
                                                    @endif
                                                </span>

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="details-single d-flex align-items-center gap-2">
                                            <img src="{{ asset('public/assets/admin/img/icons/commission-i.png') }}"
                                                width="36" height="36" class="rounded" alt="">
                                            <div>
                                                @if ($shop->store_business_model == 'commission')
                                                    <h5 class="lh--12 mb-2px color-3C3C3C">
                                                        {{ translate('messages.Admin Commission') }}
                                                    </h5>
                                                    <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                                                        {{ isset($shop->comission) ? $shop->comission : $admin_commission }}%
                                                    </span>
                                                @elseif(in_array($shop->store_business_model, ['subscription', 'unsubscribed']))
                                                    <h5 class="lh--12 mb-2px color-3C3C3C">
                                                        {{ translate('messages.Subscription Plan') }}
                                                    </h5>
                                                    <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                                                        {{ $shop?->store_sub_update_application?->package?->package_name ?? translate('messages.no_subscription_found') }}
                                                    </span>
                                                @endif

                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="details-single d-flex align-items-center gap-2">
                                            <img src="{{ asset('public/assets/admin/img/icons/s-phone.png') }}"
                                                width="36" height="36" class="rounded" alt="">
                                            <div>
                                                <h5 class="lh--12 mb-2px color-3C3C3C">
                                                    {{ translate('messages.Phone') }}
                                                </h5>
                                                <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                                                    <a href="tel:{{ $shop->phone }}">{{ $shop->phone }}</a>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="details-single d-flex align-items-center gap-2">
                                            <img src="{{ asset('public/assets/admin/img/icons/zone.png') }}" width="36"
                                                height="36" class="rounded" alt="">
                                            <div>
                                                <h5 class="lh--12 mb-2px color-3C3C3C">
                                                    {{ translate('messages.Address') }}
                                                </h5>
                                                <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                                                    {{ $shop->address }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 pb-3 view-details-container">
            <div class="d-flex align-items-center justify-content-between flex-sm-nowrap flex-wrap gap-2 px-3 pt-3">
                <div>
                    <h3 class="lh--12 mb-1 text-dark">
                        {{ translate('messages.Announcement') }}
                    </h3>
                    <span class="fs-13 lh--12 color-484848 opacity-70 d-block">
                        {{ translate('messages.This announcement shown in the user app/web') }}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div
                        class="view-btn order-sm-0 order-3 fz--14px text--primary cursor-pointer text-decoration-underline font-semibold d-flex align-items-center gap-1">
                        {{ translate('messages.view') }}
                        <i class="tio-arrow-downward text--primary"></i>
                    </div>
                    <label class="toggle-switch toggle-switch-sm" for="announcement_status">
                        <input class="toggle-switch-input dynamic-checkbox" type="checkbox" id="announcement_status"
                            data-id="announcement_status" data-type="status"
                            data-image-on='{{ asset('/public/assets/admin/img/modal') }}/digital-payment-on.png'
                            data-image-off="{{ asset('/public/assets/admin/img/modal') }}/digital-payment-off.png"
                            data-title-on="{{ translate('Do_you_want_to_enable_the_announcement') }}"
                            data-title-off="{{ translate('Do_you_want_to_disable_the_announcement') }}"
                            data-text-on="<p>{{ translate('User_will_able_to_see_the_Announcement_on_the_store_page.') }}</p>"
                            data-text-off="<p>{{ translate('User_will_not_be_able_to_see_the_Announcement_on_the_store_page') }}</p>"
                            name="announcement" value="1" {{ $shop->announcement ? 'checked' : '' }}>
                        <span class="toggle-switch-label">
                            <span class="toggle-switch-indicator"></span>
                        </span>
                    </label>
                </div>
            </div>
            <form
                action="{{ route('vendor.business-settings.toggle-settings', [$shop->id, $shop->announcement ? 0 : 1, 'announcement']) }}"
                method="get" id="announcement_status_form">
            </form>
            <div class="view-details">
                <div class="card-body">
                    <form action="{{ route('vendor.shop.update-message') }}" method="post">
                        @csrf
                        <div class="rounded __bg-FAFAFA border-0 p-20">
                            <div class="card-title d-flex gap-0 align-items-center mb-1">
                                <span>{{ translate('Announcement Text') }}</span>
                                <span class="input-label-secondary" data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('This_feature_is_for_sharing_important_information_or_announcements_related_to_the_' . $title . '.') }}">
                                    <i class="tio-info"></i>
                                </span>
                            </div>
                            <textarea name="announcement_message" id="" class="form-control" rows="4"
                                placeholder="{{ translate('messages.ex_:_ABC_Company') }}">{{ $shop->announcement_message ?? '' }}</textarea>
                        </div>
                        <div class="justify-content-end btn--container gap-3 mt-20">
                            <button type="submit" class="btn btn--reset">{{ translate('reset') }}</button>
                            <button type="submit" class="btn btn--primary">{{ translate('publish') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
