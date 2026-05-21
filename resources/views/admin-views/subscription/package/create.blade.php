@extends('layouts.admin.app')

@section('title',translate('messages.Subscription'))

@section('subscription_index')
active
@endsection

@section('content')

    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center py-2">
                <div class="col-sm mb-2 mb-sm-0">
                    <div class="d-flex align-items-start">
                        <img src="{{asset('/public/assets/admin/img/create-package-icon.png')}}" width="24" alt="img">
                        <div class="w-0 flex-grow pl-2">
                            <h1 class="page-header-title">{{translate('Subscription Package')}}  <small class="ml-2"> {{ $module == 'rental' ? '('.translate('messages.Rental_Module') .')' : ''}} </small> </h1>
                            <div class="page-header-text">{{ translate('Create_Subscriptions_Packages_for_Subscription_Business_Model') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mb-20">
            <div class="card-header">
                <div class="w-100 d-flex flex-wrap align-items-start gap-2">
                    <img src="{{asset('/public/assets/admin/img/material-symbols_featured-play-list.png')}}" width="18" alt="img" class="mt-1">
                    <div class="w-0 flex-grow">
                        <h5 class="text--title card-title">{{ translate('Package_Information') }}</h5>
                        <div class="fz-12px">{{ translate('Give_Subscriptions_Package_Information') }}</div>
                    </div>
                    <div class="text--primary-2 d-flex flex-wrap align-items-end" type="button" data-toggle="modal" data-target="#initial-modal">
                        <strong class="mr-2">{{ translate('How it Works') }}</strong>
                        <div class="blinkings">
                            <i class="tio-info-outined"></i>
                        </div>
                    </div>
                </div>
            </div>

    <form action="{{ route('admin.business-settings.subscriptionackage.store') }}" method="post">
        @csrf
        @method('post')
                <input type="hidden" value="{{ $module }}"  name="module"  >
                <div class="card-body">
                        @if ($language)
                        <ul class="nav nav-tabs mb-3">
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
                        @endif

                    <div class="row g-3">

                        <div class="col-lg-4 col-sm-6 lang_form" id="default-form">
                            <div class="form-group mb-0">
                                <label class="form-label input-label"
                                for="name">{{ translate('Package_Name') }} ({{ translate('Default') }})</label>
                                <input type="text" name="package_name[]" class="form-control" id="name" maxlength="191"  value="{{ old('package_name.0') }}"
                                placeholder="{{ translate('Package_Name') }}"
                                >
                            <input type="hidden" name="lang[]" value="default">
                            </div>
                        </div>

                        @if($language)
                                @foreach($language as $key => $lang)
                                <div class="col-lg-4 col-sm-6  d-none lang_form" id="{{$lang}}-form">
                                    <div class="form-group mb-0">
                                        <label class="form-label input-label"
                                        for="{{$lang}}_title">{{ translate('Package_Name') }} ({{strtoupper($lang)}})</label>
                                        <input type="text" name="package_name[]" class="form-control" id="{{$lang}}_title" maxlength="191"  value="{{ old('package_name.'.$key+1) }}"
                                        placeholder="{{ translate('Package_Name') }}"
                                        >
                                        <input type="hidden" name="lang[]" value="{{$lang}}">
                                    </div>
                                </div>
                                @endforeach
                        @endif


                        <div class="col-lg-4 col-sm-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Package_Price') }} ({{ \App\CentralLogics\Helpers::currency_symbol() }})</label>
                                <input type="number" name="package_price" value="{{ old('package_price') }}" required  min="0.01" step="0.01" max="999999999" class="form-control" placeholder="{{ translate('Ex: 300') }}">
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="form-group">
                                <label class="input-label">{{ translate('Package_Validity') }} {{ translate('Days') }}</label>
                                <input type="number"   min="1" max="999999999"  value="{{ old('package_validity') }}"  required name="package_validity"  class="form-control" placeholder="{{ translate('Ex: 365') }}">
                            </div>
                        </div>


                        <div class="col-lg-4 col-sm-6 lang_form default-form" >
                            <div class="form-group m-0">
                                <label class="form-label input-label   text-capitalize"
                                    for="package_info">{{ translate('messages.package_info') }}</label>
                                <textarea class="form-control" placeholder="{{ translate('EX:_Value_for_money') }}"  name="text[]" id="package_info">{{ old('text.0') }}</textarea>
                            </div>
                        </div>

                        @if($language)
                        @foreach($language as $lang)
                        <div class="col-lg-4 col-sm-6 d-none lang_form" id="{{$lang}}-form1">
                            <div class="form-group m-0">
                                <label class="form-label input-label   text-capitalize"
                                    for="package_info">{{ translate('messages.package_info') }} ({{strtoupper($lang)}})</label>
                                <textarea class="form-control" name="text[]" placeholder="{{ translate('EX:_Value_for_money') }}" id="package_info">{{ old('text.'.$key+1) }}</textarea>
                            </div>
                        </div>
                        @endforeach
                        @endif

                    </div>
                </div>
            </div>
            <div class="card mb-20">
                <div class="card-header">
                    <div class="w-100 d-flex flex-wrap align-items-start gap-2">
                        <img src="{{asset('/public/assets/admin/img/material-symbols_featured-play-list-2.png')}}" alt="img" class="mt-1">
                        <div class="w-0 flex-grow">
                            <h5 class="text--title card-title d-flex gap-3 flex-wrap mb-1">
                                <div>
                                    {{ translate('Package_Available_Features') }}
                                </div>
                                <label class="form-group form-check form--check">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                    <span class="form-check-label text-dark font-regular text-14">{{ translate('Select_All') }}</span>
                                </label>
                            </h5>
                            <div class="fz-12px">{{ translate('Mark_the_feature_you_want_to_give_in_this_package') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="check--item-wrapper check--item-wrapper-2 mt-0">
                        @if ($module == 'all')
                        <div class="check-item">
                            <label class="form-group form-check form--check">
                                <input type="checkbox" class="form-check-input package-available-feature"  {{ old('pos_system') == 1 ? 'checked' : '' }} name="pos_system" value="1">
                                <span class="form-check-label text-dark">{{ translate('messages.pos_system') }}</span>
                            </label>
                        </div>
                        <div class="check-item">
                            <label class="form-group form-check form--check">
                                <input type="checkbox" class="form-check-input package-available-feature" {{ old('self_delivery') == 1 ? 'checked' : '' }}  name="self_delivery" value="1">
                                <span class="form-check-label text-dark">{{ translate('messages.self_delivery') }}</span>
                            </label>
                        </div>
                        @endif

                        <div class="check-item">
                            <label class="form-group form-check form--check">
                                <input type="checkbox" class="form-check-input package-available-feature" {{ old('mobile_app') == 1 ? 'checked' : '' }}  name="mobile_app" value="1" >
                                <span class="form-check-label text-dark">{{ translate('messages.Mobile_App') }}</span>
                            </label>
                        </div>
                        <div class="check-item">
                            <label class="form-group form-check form--check">
                                <input type="checkbox" class="form-check-input package-available-feature" {{ old('review') == 1 ? 'checked' : '' }}  name="review" value="1" >
                                <span class="form-check-label text-dark">{{ translate('messages.review') }}</span>
                            </label>
                        </div>
                        <div class="check-item">
                            <label class="form-group form-check form--check">
                                <input type="checkbox" class="form-check-input package-available-feature" {{ old('chat') == 1 ? 'checked' : '' }}  name="chat" value="1" >
                                <span class="form-check-label text-dark">{{ translate('messages.chat') }}</span>
                            </label>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="w-100 d-flex flex-wrap align-items-start gap-2">
                        <img src="{{asset('/public/assets/admin/img/bx_category.png')}}" alt="img" class="mt-1">
                        <div class="w-0 flex-grow">
                            <h5 class="text--title card-title d-flex gap-3 flex-wrap mb-1">
                                <div>
                                    {{ translate('Set_limit') }}
                                </div>
                            </h5>
                            <div class="fz-12px">{{ $module == 'rental' ?  translate('Set_maximum_trips_&_vehicle_limit_for_this_package')  : translate('Set_maximum_order_&_product_limit_for_this_package') }}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="__bg-F8F9FC-card p-0">
                            <div class="card-body">
                                <div class="limit-item-card">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-capitalize">{{  $module == 'rental' ? translate('Maximum_Trip_Limit') : translate('Maximum_Order Limit') }}</label>
                                        <div class="d-flex flex-wrap items-center gap-2">
                                            <div class="resturant-type-group p-0">
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input limit-input" type="radio" checked name="minimum_order_limit" >
                                                    <span class="form-check-label">
                                                        {{ translate('Unlimited') }} ({{ translate('Default') }})
                                                    </span>
                                                </label>
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input limit-input" type="radio" name="minimum_order_limit" value="Use_Limit">
                                                    <span class="form-check-label">
                                                        {{ translate('Use_Limit') }}
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="custom-limit-box">
                                                <input id="max_order" type="number" name="max_order" min="1" step="1" max="999999999" class="form-control max_required" placeholder="{{ translate('Ex: 1000') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="__bg-F8F9FC-card p-0">
                            <div class="card-body">
                                <div class="limit-item-card">
                                    <div class="form-group mb-0">
                                        <label class="form-label text-capitalize">{{ $module == 'rental' ? translate('Maximum_Vehicle_Limit') : translate('Maximum_Item_Limit') }}</label>
                                        <div class="d-flex flex-wrap items-center gap-2">
                                            <div class="resturant-type-group p-0">
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input limit-input" type="radio" checked name="maximum_item_limit" >
                                                    <span class="form-check-label">
                                                        {{ translate('Unlimited') }} ({{ translate('Default') }})
                                                    </span>
                                                </label>
                                                <label class="form-check form--check mr-2 mr-md-4">
                                                    <input class="form-check-input limit-input" type="radio" name="maximum_item_limit" value="Use_Limit" >
                                                    <span class="form-check-label">
                                                        {{ translate('Use_Limit') }}
                                                    </span>
                                                </label>
                                            </div>
                                            <div class="custom-limit-box">
                                                <input  id="max_product" type="number" name="max_product" min="1" step="1" max="999999999" class="form-control max_required" placeholder="{{ translate('Ex: 1000') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="btn--container justify-content-end mt-20">
                <button type="reset" id="reset_btn" class="btn btn--reset">
                    {{ translate('messages.reset') }}
                </button>
                <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
            </div>

        </form>

        <div class="modal fade show" id="initial-modal">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header pt-4">
                        <button type="button" class="position-absolute right-0 z-index-2 top-0 m-3 w-32 btn min-w-32 p-1 h-32px bg-light2 d-center rounded-pill" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body px-4 pb-4 pt-2">
                        <div>
                            <div>
                                <div class="text-center">
                                    <h2 class="modal-title">{{ translate('Subscription_Packages') }}</h2>
                                </div>
                                <div class="text-center text-14 mb-4 max-w-542 mx-auto">
                                    {{ translate('Here_you_can_view_all_the_data_placements_in_a_package_card_in_the_subscription_UI_in_the_user_app_and_website') }}
                                </div>
                                <div class="text-center pt-2 text--base overflow-hidden">
                                    <img src="{{ asset('/public/assets/admin/img/standard-subscription.svg') }}" alt="" class="svg w-100">
                                </div>
                            </div>
                            <div class="btn--container justify-content-center pt-4 pb-3">
                                <div class="ps-xxl-24">
                                    <button type="reset" class="btn btn--primary min-w-120px" data-dismiss="modal">
                                        {{ translate('Okay') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




@endsection

@push('script_2')

<script>
"use strict";
    $('#select-all').on('change', function(){
        if($(this).is(':checked')){
            $('.package-available-feature').prop('checked', true);
        }else{
            $('.package-available-feature').prop('checked', false);
        }
    })
    $('.package-available-feature').on('change', function(){
        if($(this).is(':checked')){
            if($('.package-available-feature').length == $('.package-available-feature:checked').length){
                $('#select-all').prop('checked', true);
            }
        }else{
            $('#select-all').prop('checked', false);
        }
    })

    $('.limit-input').on('change', function(){
            if($(this).is(':checked')){
                if($(this).val() == 'Use_Limit'){
                $(this).closest('.limit-item-card').find('.custom-limit-box').show();
                $(this).closest('.limit-item-card').find('.max_required').prop('required', true);
            } else {
                $(this).closest('.limit-item-card').find('.custom-limit-box').hide();
                $(this).closest('.limit-item-card').find('.max_required').removeAttr('required');
            }
        }
    })




    $(document).on("click", ".btn--reset", function () {
        $('.custom-limit-box').hide();
        $('.max_required').removeAttr('required');
    });

</script>

@endpush

