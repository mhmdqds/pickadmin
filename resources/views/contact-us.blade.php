@extends('layouts.landing.app')

@section('title', translate('messages.contact_us'))

@section('content')
    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.contact_us') }}</h1>
            <div class="breadcrumb">
                <a href="{{route('home')}}">{{ translate('messages.home') }}</a> / {{ translate('messages.contact_us') }}
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-grid">
                <!-- Left: Contact Info Cards -->
                <div class="contact-info">
                    <!-- Phone -->
                    <div class="contact-card">
                        <div class="cc-icon">
                            <svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <h4>{{ translate('messages.Call_Us') }}</h4>
                            <p><a href="tel:{{ \App\CentralLogics\Helpers::get_settings('phone') }}">{{ \App\CentralLogics\Helpers::get_settings('phone') }}</a></p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="contact-card">
                        <div class="cc-icon">
                            <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 6"/></svg>
                        </div>
                        <div>
                            <h4>{{ translate('messages.Email') }}</h4>
                            <p><a href="mailto:{{ \App\CentralLogics\Helpers::get_settings('email_address') }}">{{ \App\CentralLogics\Helpers::get_settings('email_address') }}</a></p>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="contact-card">
                        <div class="cc-icon">
                            <svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <h4>{{ translate('messages.Address') }}</h4>
                            @php($default_location = \App\CentralLogics\Helpers::get_settings('default_location'))
                            <p><a href="https://www.google.com/maps/search/?api=1&query={{ data_get($default_location,'lat',0)}},{{ data_get($default_location,'lng',0)}}" target="_blank">{{ \App\CentralLogics\Helpers::get_settings('address') }}</a></p>
                        </div>
                    </div>

                    <!-- Hours -->
                    <div class="contact-card">
                        <div class="cc-icon">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <h4>{{ translate('messages.Time') }}</h4>
                            <p>{{ translate(\App\CentralLogics\Helpers::get_settings('opening_day')) }} - {{ translate(\App\CentralLogics\Helpers::get_settings('closing_day')) }}, {{ \App\CentralLogics\Helpers::time_format(\App\CentralLogics\Helpers::get_settings('opening_time')) }} - {{ \App\CentralLogics\Helpers::time_format(\App\CentralLogics\Helpers::get_settings('closing_time')) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Contact Form -->
                <div class="contact-form-card">
                    <h3>{{ translate('Send us a Message') }}</h3>
                    <p>{{ translate('We\'d love to hear from you. Fill out the form below and we\'ll get back to you as soon as possible.') }}</p>
                    <form method="post" action="{{route('send-message')}}" id="form-id">
                        @csrf
                        <div class="cf-row">
                            <div class="cf-group">
                                <label for="name">{{ translate('Name') }}</label>
                                <input type="text" id="name" name="name" placeholder="{{ translate('Your Name') }}" required>
                            </div>
                            <div class="cf-group">
                                <label for="email">{{ translate('Email') }}</label>
                                <input type="email" id="email" name="email" placeholder="{{ translate('Email') }}" required>
                            </div>
                        </div>
                        <div class="cf-group">
                            <label for="subject">{{ translate('Subject') }}</label>
                            <input type="text" id="subject" name="subject" placeholder="{{ translate('Subject') }}" required>
                        </div>
                        <div class="cf-group">
                            <label for="message">{{ translate('Message') }}</label>
                            <textarea id="message" name="message" placeholder="{{ translate('Message') }}" required></textarea>
                        </div>
                        <div class="cf-group">
                            @include('admin-views.partials._recaptcha')
                        </div>
                        <button type="submit" class="cf-submit" id="signInBtn">
                            <span>{{ translate('messages.Send_Message') }}</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
