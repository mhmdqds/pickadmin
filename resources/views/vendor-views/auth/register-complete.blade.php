@extends('layouts.landing.app')
@section('title', translate('messages.vendor_registration'))
@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/view-pages/vendor-registration.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/select2.min.css') }}"/>
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
                <div class="stepper-step completed">
                    <div class="stepper-circle">2</div>
                    <div class="stepper-label">{{ translate('Business Plan') }}</div>
                </div>
                <div class="stepper-connector"></div>
                <div class="stepper-step active">
                    <div class="stepper-circle">3</div>
                    <div class="stepper-label {{  isset($payment_status) && $payment_status == 'fail' ? 'text-danger' : '' }}">{{ translate('Complete') }}</div>
                </div>
            </div>

            <div class="success-box">
                @if ( isset($payment_status) && $payment_status == 'fail')
                    <div class="check" style="background:#e74c3c">&#x2717;</div>
                    <h2>{{ translate('Transaction Failed!') }}</h2>
                @else
                    <div class="check">&#x2713;</div>
                    <h2>{{ translate('Congratulations!') }}</h2>
                @endif

                <p>
                    @if (isset($type) && $type == 'commission')
                        {{ translate('You\'ve opted for our commission-based plan. Admin will review the details and activate your account shortly.') }}
                    @elseif( isset($payment_status) && $payment_status == 'fail')
                        {{ translate('Sorry, Your Transaction can\'t be completed. Please choose another payment method.') }}
                    @else
                        {{ translate('Thank you for your subscription purchase! Your payment was successfully processed. Please note that your subscription will be activated once it has been approved by our Admin Team.') }}
                    @endif
                </p>

                @if ( isset($payment_status) && $payment_status == 'fail')
                    <a href="{{ route('restaurant.back',['store_id' => $store_id ?? null]) }}" class="btn-home" style="background:#e74c3c">{{ translate('Try_again') }}</a>
                @else
                    <a href="{{ route('home',['new_user'=> true]) }}" class="btn-home">{{ translate('Back to Home') }}</a>
                @endif
            </div>
        </div>
    </section>

    @endsection
    @push('script_2')
    <script>
        @if (! (isset($payment_status) && $payment_status == 'fail'))
        document.addEventListener("DOMContentLoaded", function() {
            var homeLink = document.getElementById('home-link');
            var newUrl = "{{ route('home',['new_user'=> true]) }}";
            homeLink.setAttribute('href', newUrl);
        });
        @endif
    </script>
    @endpush
