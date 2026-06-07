@extends('layouts.admin.app')

@section('title', translate('messages.update Provider'))



@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header pb-20">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ $store->name }}
                    </h1></span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <form action="" method="post" enctype="multipart/form-data" id="providerFormSubmit">
            @csrf
            <div id="businessPlan">
                <div class="custom-timeline d-flex flex-wrap gap-40px text-title mb-2">
                    <h4 class="single text-primary checked"><span class="count-checked">1</span>{{ translate('messages.Business Basic Setup') }}</h4>
                    <h4 class="single font-semibold"><span class="count btn-primary">2</span>{{ translate('messages.Business Plan Setup') }}</h4>
                </div>
                <div class="row g-2">
                    <div class="col-lg-12">
                        <div class="card mt-3">
                            <div class="card-header">
                                <div>
                                    <h5 class="text-title mb-1">
                                        {{ translate('messages.Update Business Plan') }}
                                    </h5>

                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="business-plan-card-wrapper">
                                            <input type="radio" name="business_plan" class="business-plan-radio" value="commission-base" {{ $store->store_business_model == 'commission' ? 'checked' : ''}}/>
                                            <div class="business-plan-card">
                                                <h4 class="fs-16 title text-title mb-10px opacity-70">
                                                    {{ translate('messages.Commission Base') }}
                                                </h4>
                                                <p class="fs-14 text-title opacity-70 mb-0">
                                                    {{ translate('messages.You have to give a certain percentage of commission to admin for every Trip request.') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="business-plan-card-wrapper">
                                            <input type="radio" name="business_plan" class="business-plan-radio" value="subscription-base" {{ $store->store_business_model == 'subscription' ? 'checked' : ''}}/>
                                            <div class="business-plan-card">
                                                <h4 class="fs-16 title text-title mb-10px opacity-70">
                                                    {{ translate('messages.Subscription Base') }}
                                                </h4>
                                                <p class="fs-14 text-title opacity-70 mb-0">
                                                    {{ translate('messages.You have to pay certain amount in every month/year to admin as subscription fee.') }}
                                                </p>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="col-lg-12 mt-20 d-none" id="subscription-plan">
                                        <div>
                                            <div class="text-center mb-20">
                                                <h3 class="modal-title fs-16 opacity-lg font-bold">
                                                    {{ translate('Choose Subscription Package') }}</h3>
                                            </div>
                                            <div class="plan-slider owl-theme owl-carousel owl-refresh">
                                                @forelse ($packages as $key=> $package)
                                                    <label class="__plan-item d-block hover {{ $package->id == $store->store_sub?->package_id ? 'active' : '' }}">
                                                        <input type="radio" name="package_id" id="package_id"
                                                               value="{{ $package->id }}" class="d-none">
                                                        <div class="inner-div">
                                                            <div class="text-center">
                                                                <h3 class="title">{{ $package->package_name }}</h3>
                                                                <h2 class="price">{{ \App\CentralLogics\Helpers::format_currency($package->price) }}</h2>
                                                                <div class="day-count">{{ $package->validity }}
                                                                    {{ translate('messages.days') }}</div>
                                                            </div>
                                                            <ul class="info">
                                                                @if ($package->pos)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.POS') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->mobile_app)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.mobile_app') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->chat)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.chatting_options') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->review)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.review_section') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->self_delivery)
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.self_delivery') }}</span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->max_order == 'unlimited')
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.Unlimited_Orders') }}</span>
                                                                    </li>
                                                                @else
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ $package->max_order }} {{ translate('messages.Orders') }} </span>
                                                                    </li>
                                                                @endif
                                                                @if ($package->max_product == 'unlimited')
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ translate('messages.Unlimited_uploads') }}</span>
                                                                    </li>
                                                                @else
                                                                    <li>
                                                                        <i class="tio-checkmark-circle"></i>
                                                                        <span>{{ $package->max_product }} {{ translate('messages.uploads') }}</span>
                                                                    </li>
                                                                @endif
                                                            </ul>
                                                        </div>
                                                    </label>
                                                @empty
                                                    <div class="text-center">
                                                        {{translate('No Package Found')}}
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="btn--container justify-content-end mt-3">
                            <a href="{{ route('admin.rental.provider.edit-basic-setup', $store->id ) }}" class="btn btn--reset min-w-100px justify-content-center">{{ translate('messages.back') }}</a>
                            <div id="subscriptionBtn">
                                <button data-id="{{ $store->store_business_model == 'commission' ? 0 : $store?->package?->id }}"
                                    data-target="#package_detail" id="package_detail" type="button" class="btn btn--primary shift-btn package_detail">{{ translate('messages.update') }}</button>
                            </div>
                            <?php
                                $cash_backs= \App\CentralLogics\Helpers::calculateSubscriptionRefundAmount(store:$store ,return_data:true);
                            ?>
                            <div id="commissionBtn">
                                <button type="button" data-url="{{route('admin.business-settings.subscriptionackage.switchToCommission',$store->id)}}" data-message="{{translate('You_Want_To_Migrate_To_Commission.')}} {{ data_get($cash_backs,'back_amount') > 0  ?  translate('You will get').' '. \App\CentralLogics\Helpers::format_currency(data_get($cash_backs,'back_amount')) .' '.translate('to_your_wallet_for_remaining') .' '.data_get($cash_backs,'days').' '.translate('messages.days_subscription_plan') : '' }}"  class="btn btn--primary shift_to_commission">{{ translate('Update') }}</button>
                            </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="modal fade __modal" id="subscription-renew-modal">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <!-- Modal Header -->
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body px-4 pt-0">
                    <div class="data_package" id="data_package">
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="d-none" id="data-set"
        data-store-business-model="{{ $store->store_business_model }}"
        data-rental-provider-url="{{ route('admin.rental.provider.list') }}"

        data-store-id="{{ $store->id }}"
        data-translate-are-you-sure="{{ translate('Are_you_sure?') }}"
        data-translate-no="{{ translate('no') }}"
        data-translate-yes="{{ translate('yes') }}"
        data-translate-success="{{ translate('Successfully_Switched_To_Commission') }}"
        data-select-subscription-package="{{ translate('Please select a subscription package.') }}"

        data-subscription-package-view-url="{{ route('admin.business-settings.subscriptionackage.packageView', ['PLACEHOLDER_ID', $store->id]) }}"
    ></div>


@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-business-plan-edit.js')}}"></script>
@endpush
