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
            @php($language=\App\Models\BusinessSetting::where('key','language')->first())
            @php($language = $language->value ?? null)
            @php($defaultLang = 'en')

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
                    <div class="stepper-label text-danger">{{ translate('Complete') }}</div>
                </div>
            </div>

            <div class="success-box">
                <div class="check" style="background:#e74c3c">&#x2717;</div>
                <h2>{{ translate('Transaction Failed!') }}</h2>
                <p>{{ translate('Sorry, Your Transaction can\'t be completed. Please choose another payment method or try again.') }}</p>
                <a href="" class="btn-home" style="background:#e74c3c">{{ translate('Try Again') }}</a>
            </div>
        </div>
    </section>

    @endsection
    @push('script_2')

    @endpush
