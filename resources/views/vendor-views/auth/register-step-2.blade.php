@extends('layouts.landing.app')
@section('title', translate('messages.vendor_registration'))
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/owl.min.css') }}"/>
@endpush
@section('content')
    <!-- Page Hero Banner -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.vendor') }} {{ translate('messages.registration') }}</h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">{{ translate('messages.home') }}</a> / {{ translate('messages.vendor') }} {{ translate('messages.registration') }}
            </div>
        </div>
    </section>

    <section class="reg-section">
        <div class="reg-container" style="max-width:1060px">

            <!-- Stepper -->
            <div class="stepper" style="display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:32px;flex-wrap:wrap">
                <div class="stepper-step completed">
                    <div class="stepper-circle">1</div>
                    <div class="stepper-label">{{ translate('General Info') }}</div>
                </div>
                <div class="stepper-connector"></div>
                <div class="stepper-step active">
                    <div class="stepper-circle">2</div>
                    <div class="stepper-label">{{ translate('Business Plan') }}</div>
                </div>
                <div class="stepper-connector"></div>
                <div class="stepper-step">
                    <div class="stepper-circle">3</div>
                    <div class="stepper-label">{{ translate('Complete') }}</div>
                </div>
            </div>


            <form action="{{ route('restaurant.business_plan') }}" class="reg-form js-validate" method="post">
                @csrf
                <input type="hidden" name="store_id" value="{{ $store_id }}">
                <div class="card __card mb-3">
                    <div class="card-header border-0">
                        <h5 class="card-title text-center">
                            {{ translate('Choose Your Business Plan') }}
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            @if (\App\CentralLogics\Helpers::commission_check())
                                <div class="col-sm-6">
                                    <label class="plan-check-item pb-3 pb-sm-0">
                                        <input id="commission-base" type="radio" name="business_plan" value="commission-base" class="d-none"
                                            checked>
                                        <div class="plan-check-item-inner">
                                            <div class="d-flex gap-3 justify-content-between align-items-center mb-10">
                                                <h5 class="mb-0">{{ translate('Commision_Base') }}</h5>
                                                <span class="checkmark">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                        fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                                        <path
                                                            d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0" />
                                                    </svg>
                                                </span>
                                            </div>
                                            <p>
                                                {{ translate('Vendor will pay') }} {{ $admin_commission }}%
                                                {{ translate('commission to') }} {{ $business_name }}
                                                {{ translate('from each order. You will get access of all the features and options  in vendor panel , app and interaction with user.') }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            @endif
                            <div class="col-sm-6">
                                <label class="plan-check-item">
                                    <input id="subscription-base" type="radio" name="business_plan" value="subscription-base" class="d-none">
                                    <div class="plan-check-item-inner">
                                        <div class="d-flex gap-3 justify-content-between align-items-center mb-10">
                                            <h5 class="mb-0">{{ translate('Subscription_Base') }}</h5>
                                            <span class="checkmark">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                                    fill="currentColor" class="bi bi-check2" viewBox="0 0 16 16">
                                                    <path
                                                        d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0" />
                                                </svg>
                                            </span>
                                        </div>
                                        <p>
                                            {{ translate('Run vendor by puchasing subsciption packages. You will have access the features of in vendor panel , app and interaction with user according to the subscription packages.') }}
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div id="subscription-plan" style="display:none">
                            <hr class="my-4">
                            <h5 class="card-title text-center mb-4">{{ translate('Choose Subscription Package') }}</h5>
                            <div class="plan-slider-wrap">
                            <div class="plan-slider owl-theme owl-carousel owl-refresh">

                                @forelse ($packages as $key=> $package)
                                    <label
                                        class="__plan-item {{ (count($packages) > 4 && $key == 2) || (count($packages) < 5 && $key == 1) ? 'active' : '' }} ">
                                        <input type="radio" name="package_id" value="{{ $package->id }}" class="d-none">
                                        <div class="inner-div">
                                            <div class="text-center">

                                                <h3 class="title">{{ $package->package_name }}</h3>
                                                <h2 class="price">
                                                    {{ \App\CentralLogics\Helpers::format_currency($package->price) }}</h2>
                                                <div class="day-count">{{ $package->validity }}
                                                    {{ translate('messages.days') }}</div>
                                            </div>
                                            <ul class="info">

                                                @if ($package->pos)
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.POS') }} </span>
                                                    </li>
                                                @endif
                                                @if ($package->mobile_app)
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.mobile_app') }} </span>
                                                    </li>
                                                @endif
                                                @if ($package->chat)
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.chatting_options') }} </span>
                                                    </li>
                                                @endif
                                                @if ($package->review)
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.review_section') }} </span>
                                                    </li>
                                                @endif
                                                @if ($package->self_delivery)
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.self_delivery') }} </span>
                                                    </li>
                                                @endif
                                                @if ($package->max_order == 'unlimited')
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ isset($module) && $module == 'rental' ?  translate('messages.Unlimited_trips') :translate('messages.Unlimited_Orders') }} </span>
                                                    </li>
                                                @else
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ $package->max_order }} {{ isset($module) && $module == 'rental' ?  translate('messages.trips') : translate('messages.Orders') }}
                                                        </span>
                                                    </li>
                                                @endif
                                                @if ($package->max_product == 'unlimited')
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ translate('messages.Unlimited_uploads') }} </span>
                                                    </li>
                                                @else
                                                    <li>
                                                        <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}"
                                                            class="check" alt="">
                                                        <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}"
                                                            class="check-white" alt=""> <span>
                                                            {{ $package->max_product }}
                                                            {{ translate('messages.uploads') }} </span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </label>

                                @empty
                                @endforelse

                            </div>
                            </div>{{-- /plan-slider-wrap --}}
                        </div>
                        <div class="terms-check mt-4">
                            <input type="checkbox" id="businessTerms" required />
                            <label for="businessTerms">{{ translate('messages.i_agree_to_the') }} <a href="{{ route('terms-and-conditions') }}" target="_blank">{{ translate('messages.terms_and_condition') }}</a> {{ translate('messages.and') }} <a href="{{ route('privacy-policy') }}" target="_blank">{{ translate('messages.privacy_policy') }}</a></label>
                        </div>
                        <div class="text-end pt-4 d-flex flex-wrap justify-content-end gap-3">
                            <button type="submit" class="btn-next" id="businessNextBtn" disabled>
                                <span class="btn-text">{{ translate('Next') }}</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span></span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

@endsection
@push('script_2')
    <script src="{{ asset('public/assets/landing/js/owl.min.js') }}"></script>
    <script>
        var planSliderReady = false;

        var owlConfig = {
            loop: false,
            margin: 20,
            responsiveClass: true,
            nav: true,
            navText: [
                '<span class="owl-nav-btn"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></span>',
                '<span class="owl-nav-btn"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span>'
            ],
            dots: true,
            autoHeight: false,
            startPosition: 1,
            responsive: {
                0:    { items: 1,   margin: 12, nav: false },
                480:  { items: 1.5, margin: 14, nav: false },
                640:  { items: 2,   margin: 16 },
                992:  { items: 3,   margin: 20 },
                1200: { items: 4,   margin: 20 }
            }
        };

        function initPlanSlider() {
            if (planSliderReady) return;
            planSliderReady = true;
            $('.plan-slider').owlCarousel(owlConfig);
        }

        function showSubscriptionPlan() {
            $('#subscription-plan').slideDown(300, function() {
                initPlanSlider();
                // refresh after slide to fix width calculations
                if (planSliderReady) {
                    $('.plan-slider').trigger('refresh.owl.carousel');
                }
            });
        }

        function hideSubscriptionPlan() {
            $('#subscription-plan').slideUp(200);
        }

        $(document).ready(function() {
            // Set initial state based on checked radio
            var checkedPlan = $('input[name="business_plan"]:checked').val();
            if (checkedPlan === 'subscription-base') {
                $('#subscription-plan').show();
                initPlanSlider();
            } else {
                $('#subscription-plan').hide();
            }

            // Business plan radio change
            $('input[name="business_plan"]').on('change', function() {
                if ($(this).val() === 'subscription-base') {
                    showSubscriptionPlan();
                } else {
                    hideSubscriptionPlan();
                }
            });

            // Package selection
            $('input[name="package_id"]').on('change', function() {
                $('.__plan-item').removeClass('active');
                $(this).closest('.__plan-item').addClass('active');
            });

            // Terms checkbox — enable/disable submit
            $('#businessTerms').on('change', function() {
                $('#businessNextBtn').prop('disabled', !this.checked);
            });

            // Form submit — show loader, prevent double-submit
            $('form.js-validate').on('submit', function() {
                var btn = $('#businessNextBtn');
                btn.prop('disabled', true);
                btn.find('.btn-text').addClass('d-none');
                btn.find('.btn-loader').removeClass('d-none');
            });
        });
    </script>
@endpush
