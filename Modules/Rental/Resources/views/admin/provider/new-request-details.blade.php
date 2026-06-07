@extends('layouts.admin.app')

@section('title', translate('messages.New Provider Request - Details'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/rental/provider-details.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ translate('messages.Provider_Details') }}
                    </h1></span>
                    </h1>
                </div>
                <div class="d-flex align-items-start flex-wrap gap-2">
                    <a href="{{ route('admin.rental.provider.edit-basic-setup', $store->id)}}" class="btn btn--primary-light float-right mb-0">
                        <i class="tio-edit"></i> {{ translate('messages.edit_provider') }}
                    </a>
                    @if($store->vendor->status === null)
                    <a class="btn btn--warning-light font-weight-bold float-right mb-0" data-deny="cancel" data-toggle="modal"
                       data-target="#exampleModal--cancel"><i
                            class="tio-clear font-weight-bold pr-1"></i>
                        {{ translate('messages.reject') }}</a>
                    @endif
                    <a class="btn btn--primary font-weight-bold float-right mr-2 mb-0" data-deny="approve" data-toggle="modal"
                       data-target="#exampleModal--approve"
                       href="javascript:"><i
                            class="tio-done font-weight-bold pr-1"></i>{{ translate('messages.approve') }}</a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="taxi-banner radius-10 mt-4 mb-20"
             style="background-image: url('{{ $store->cover_photo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}'); background-repeat: no-repeat; background-position: center; background-size: cover;">
        <div class="taxi-info-wrapper d-flex flex-wrap flex-sm-nowrap gap-30px">
                <div class="logo">
                    <img data-onerror-image="{{asset('public/assets/admin/img/100x100/1.png')}}"
                         src="{{ $store->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}" width="150" class="rounded-8"
                         alt="">
                </div>
                <div class="taxi-info">
                    <h3 class="fs-20 fw-bold text--title mb-20"> {{ $store->name }}</h3>
                    <div class="details d-flex flex-wrap flex-column flex-sm-row gap-40px">
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/zone.png') }}" width="36" height="36"
                                 class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Business_zone') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{$store->address}}</span>
                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/job-type.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Business_Plan') }}
                                </h5>
                                @if($store->store_business_model == 'none')
                                    <span class="fs-13 lh--12 color-484848">{{ translate($store->package->package_name ) }}</span><br>
                                    <span class="fs-13 lh--12 color-484848">{{ translate('payment_failed') }}</span>
                                @else
                                <span class="fs-13 lh--12 color-484848">{{ translate($store->store_business_model ) }}</span>
                                @endif

                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('/public/assets/admin/img/rental/det-icon.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.Approx. Pickup Time') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{ $store->delivery_time }}</span>
                            </div>
                        </div>
                        <div class="details-single d-flex align-items-center gap-2">
                            <img src="{{ asset('public/assets/admin/img/icons/vehicle-type.png') }}" width="36"
                                 height="36" class="rounded" alt="">
                            <div>
                                <h5 class="lh--12 mb-0 color-3C3C3C"> {{ translate('messages.VAT / TAX') }}
                                </h5>
                                <span class="fs-13 lh--12 color-484848">{{ $store->tax }} %</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div>
                    <h5 class="text-title mb-1">
                        {{ translate('messages.Provider_Information') }}
                    </h5>
                    <p class="fs-12">
                        {{ translate('messages.Here you can see all the information that provider submit during registration') }}
                    </p>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.General_Information') }}
                                </h5>
                                @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                                @php($language = $language->value ?? null)
                                @php($defaultLang = 'en')
                                <div class="div">
                                    @if ($language)
                                        <ul class="nav nav-tabs mb-4">
                                            <li class="nav-item">
                                                <a class="nav-link lang_link active" href="#"
                                                   id="default-link">{{ translate('Default') }}</a>
                                            </li>
                                            @foreach (json_decode($language) as $lang)
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link" href="#"
                                                       id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($language)
                                        <div class="lang_form" id="default-form">
                                            <div class="resturant--info-address">
                                                <ul class="address-info address-info-2 p-0 text-dark">
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Vendor Name') }}</span>
                                                        <span>: {{$store->name}} {{$store->name}}</span>
                                                    </li>
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Business Address') }}</span>
                                                        <span>: {{$store->address}} </span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                        @foreach (json_decode($language) as $lang)
                                            <?php
                                                if(count($store?->translations ?? [])){
                                                    $translate = [];
                                                    foreach($store['translations'] as $t)
                                                    {
                                                        if($t->locale == $lang && $t->key=="name"){
                                                            $translate[$lang]['name'] = $t->value;
                                                        }
                                                    }
                                                }
                                            ?>
                                            <div class="d-none lang_form" id="{{ $lang }}-form">
                                                <div class="resturant--info-address">
                                                    <ul class="address-info address-info-2 p-0 text-dark">
                                                        <li class="d-flex align-items-start">
                                                            <span class="label min-w-sm-auto">{{ translate('messages.Provider Name') }}</span>
                                                            <span>: {{$translate[$lang]['name']??''}}</span>
                                                        </li>
                                                        <li class="d-flex align-items-start">
                                                            <span class="label min-w-sm-auto">{{ translate('messages.Business Address') }}</span>
                                                            <span>: {{$store->address}} </span>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div id="default-form">
                                            <div class="resturant--info-address">
                                                <ul class="address-info address-info-2 p-0 text-dark">
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Provider Name') }}</span>
                                                        <span>: {{ $store->name }} {{ $store->name }}</span>
                                                    </li>
                                                    <li class="d-flex align-items-start">
                                                        <span class="label min-w-sm-auto">{{ translate('messages.Business Address') }}</span>
                                                        <span>: {{ $store->address }}</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.Owner_Information') }}
                                </h5>
                                <div class="resturant--info-address">
                                    <ul class="address-info address-info-2 p-0 text-dark">
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.First Name') }}</span>
                                            <span>: {{$store->vendor->f_name}} </span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Last Name') }}</span>
                                            <span>: {{$store->vendor->l_name}}</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Phone') }}</span>
                                            <span>: {{$store->vendor->phone}}</span>
                                        </li>
                                    </ul>
                                </div>


                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.Pickup_Zone') }}
                                </h5>
                                <div class="d-flex gap-2 gap-sm-3 flex-wrap">
                                    @foreach(json_decode($store->pickup_zone_id) ?? [] as $pickup)
                                        <?php
                                            $zoneName = $store->pickupZones[$pickup] ?? 'Unknown Zone';
                                        ?>
                                        <label class="badge badge-soft-dark rounded-20 p-2 m-0 font-medium">
                                            {{ $zoneName }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card __bg-FAFAFA border-0 h-100">
                            <div class="card-body">
                                <h5 class="mb-10px font-bold"> {{ translate('messages.Login Information') }}
                                </h5>


                                <div class="resturant--info-address">
                                    <ul class="address-info address-info-2 p-0 text-dark">
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Email') }}</span>
                                            <span>: {{ $store->vendor->email }}</span>
                                        </li>
                                        <li class="d-flex align-items-start">
                                            <span class="label min-w-sm-auto">{{ translate('messages.Password') }}</span>
                                            <span>: {{ translate('*************') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal--approve" tabindex="-1" aria-labelledby="exampleModalLabel--approve"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="{{ asset('public/assets/admin/img/new-img/close-icon-dark.svg') }}" alt="">
                    </button>

                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75" src="{{ asset('public/assets/admin/img/modal/mark.png') }}"
                             class="rounded-circle" alt="">
                    </div>

                    <h3 class="text--title mb-6 font-medium text-center">
                        {{ translate('Are you sure, want to approve the request?') }}</h3>
                    <form method="get" action="{{route('admin.rental.provider.approve-or-deny',[$store['id'],1])}}">
                        @csrf
                        <div class="form-floating">
                            <input type="hidden" value="1" name="status">
                            <div class="d-flex justify-content-end gap-3">
                                <button type="button" data-dismiss="modal" aria-label="Close"
                                        class="btn btn--reset">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Approve') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal--cancel" tabindex="-1" aria-labelledby="exampleModalLabel--cancel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body pt-5 p-md-5">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <img src="{{ asset('public/assets/admin/img/new-img/close-icon-dark.svg') }}" alt="">
                    </button>

                    <div class="d-flex justify-content-center mb-4">
                        <img width="75" height="75" src="{{ asset('public/assets/admin/img/icons/delete.png') }}"
                             class="rounded-circle" alt="">
                    </div>

                    <h3 class="text--title mb-6 font-medium text-center">
                        {{ translate('Are you sure, want to cancel the request?') }}</h3>
                    <form method="get" action="{{route('admin.rental.provider.approve-or-deny',[$store['id'],0])}}">
                        @csrf
                        <div class="form-floating">
                            <label for="add-your-note" class="font-medium input-label text--title">{{ translate('Cancellation Note') }}
                                <span class="form-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="Cancellation Note">
                                                            <i class="tio-info text--title opacity-60"></i>
                                                    </span>
                            </label>
                            <div class="mb-30">
                                                    <textarea
                                                        class="form-control h--90"
                                                        placeholder="{{ translate('Type your Cancellation Note') }}"
                                                        name="message"
                                                        id="add-your-note"
                                                        maxlength="60"
                                                        required
                                                    ></textarea>
                                <div id="char-count">0/60</div>
                            </div>
                            <input type="hidden" value="0" name="status">
                            <div class="d-flex justify-content-end gap-3">
                                <button type="button" data-dismiss="modal" aria-label="Close"
                                        class="btn btn--reset">{{ translate('Cancel') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('Deny') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script_2')
 <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/new-provider-details.js')}}"></script>
@endpush
