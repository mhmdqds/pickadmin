
<!DOCTYPE html>
<?php
    if(addon_published_status('RideShare')){
        $toggle_rider_registration = \App\CentralLogics\Helpers::get_data_settings(RIDE_SHARE_BUSINESS_SETTINGS, 'toggle_rider_registration')?->value ?? false;
        if($toggle_rider_registration == 1){
            $toggle_rider_registration = true;
        }else{
            $toggle_rider_registration = false;
        }
    } else {
        $toggle_rider_registration = false;
    }
    $landing_site_direction = session()->get('landing_site_direction');
    $country= \App\CentralLogics\Helpers::get_business_settings('country')  ;
    $countryCode= strtolower($country??'auto');
   $metaData=  \App\Models\DataSetting::where('type','admin_landing_page')->whereIn('key',['meta_title','meta_description','meta_image'])->get()->keyBy('key')??[];
?>
<html dir="{{ $landing_site_direction }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title')</title>
    @include('layouts.landing._seo')

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;600;700;900&family=Syne:wght@700;800&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/odometer.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/toastr.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/landing/css/landing.css') }}"/>
    <link rel="stylesheet" href="{{asset('public/assets/admin/intltelinput/css/intlTelInput.css')}}">

    @php($backgroundChange = \App\CentralLogics\Helpers::get_business_settings('backgroundChange') ?? [])
    @if (isset($backgroundChange['primary_1_hex']))
    <style>
        :root {
            --primary: {{ $backgroundChange['primary_1_hex'] }};
            --primary-dark: {{ $backgroundChange['primary_1_hex'] }};
            --primary-rgb: {{ $backgroundChange['primary_1_rgb'] ?? '255,107,0' }};
        }
    </style>
    @endif

    <link rel="icon" type="image/x-icon" href="{{\App\CentralLogics\Helpers::iconFullUrl()}}">
    @stack('css_or_js')
</head>

<body>

    @php($fixed_link = \App\Models\DataSetting::where(['key'=>'fixed_link','type'=>'admin_landing_page'])->first())
    @php($fixed_link = isset($fixed_link->value)?json_decode($fixed_link->value, true):null)

    <!-- Mobile menu overlay -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="close-mob" id="closeMob">&times;</button>
        <a href="{{route('home')}}" onclick="closeMobile()">{{ translate('messages.home') }}</a>
        <a href="{{route('about-us')}}" onclick="closeMobile()">{{ translate('messages.about_us') }}</a>
        <a href="{{route('privacy-policy')}}" onclick="closeMobile()">{{ translate('messages.privacy_policy') }}</a>
        <a href="{{route('terms-and-conditions')}}" onclick="closeMobile()">{{ translate('messages.terms_and_condition') }}</a>
        <a href="{{route('contact-us')}}" onclick="closeMobile()">{{ translate('messages.contact_us') }}</a>
        @if (isset($toggle_store_registration) && $toggle_store_registration)
            <a href="{{ route('restaurant.create') }}" onclick="closeMobile()">{{ translate('messages.vendor_registration') }}</a>
        @endif
        @if (isset($toggle_dm_registration) && $toggle_dm_registration)
            <a href="{{ route('deliveryman.create') }}" onclick="closeMobile()">{{ translate('messages.deliveryman_registration') }}</a>
        @endif
        @if (isset($toggle_rider_registration) && $toggle_rider_registration)
            <a href="{{ route('rider.create') }}" onclick="closeMobile()">{{ translate('messages.rider_registration') }}</a>
        @endif
        @if (isset($fixed_link) && isset($fixed_link['web_app_url_status']) && $fixed_link['web_app_url_status'] && !empty($fixed_link['web_app_url']))
            <a href="{{ $fixed_link['web_app_url'] }}" target="_blank" onclick="closeMobile()" class="mob-browse-web">{{ translate('Browse web') }}</a>
        @endif
    </div>

    <!-- Header -->
    <nav class="main-nav">
        <div class="container">
            <a href="{{route('home')}}" class="nav-logo">
                <img class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" src="{{ \App\CentralLogics\Helpers::logoFullUrl()}}" alt="image" />
            </a>
            <div class="nav-links">
                <a href="{{route('home')}}" class="{{ Request::is('/') ? 'active-link' : '' }}">{{ translate('messages.home') }}</a>
                <a href="{{route('about-us')}}" class="{{ Request::is('about-us') ? 'active-link' : '' }}">{{ translate('messages.about_us') }}</a>
                <a href="{{route('privacy-policy')}}" class="{{ Request::is('privacy-policy') ? 'active-link' : '' }}">{{ translate('messages.privacy_policy') }}</a>
                <a href="{{route('terms-and-conditions')}}" class="{{ Request::is('terms-and-conditions') ? 'active-link' : '' }}">{{ translate('messages.terms_and_condition') }}</a>
                <a href="{{route('contact-us')}}" class="{{ Request::is('contact-us') ? 'active-link' : '' }}">{{ translate('messages.contact_us') }}</a>
            </div>
            <div class="nav-right">
                @php( $local = session()->has('landing_local')?session('landing_local'):null)
                @php($lang = \App\CentralLogics\Helpers::get_business_settings('system_language') )
                @if ($lang)
                <div class="lang-sw" id="langSw">
                    <div class="lang-current" onclick="document.getElementById('langSw').classList.toggle('open')">
                        &#x1F310;
                        @foreach($lang as $data)
                            @if($data['code']==$local)
                                <span>{{ strtoupper($data['code']) }}</span>
                            @elseif(!$local && $data['default'] == true)
                                <span>{{ strtoupper($data['code']) }}</span>
                            @endif
                        @endforeach
                        <svg viewBox="0 0 10 10" fill="none"><path d="M2.5 3.75L5 6.25L7.5 3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </div>
                    <div class="lang-dd">
                        @foreach($lang as $key => $data)
                            @if($data['status']==1)
                                <a href="{{route('lang',[$data['code']])}}" class="{{ ($data['code']==$local || (!$local && $data['default'] == true)) ? 'active' : '' }}">
                                    {{ $data['code'] }}
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                @if (isset($fixed_link) && isset($fixed_link['web_app_url_status']) && $fixed_link['web_app_url_status'] && !empty($fixed_link['web_app_url']))
                <a href="{{ $fixed_link['web_app_url'] }}" target="_blank" class="btn-browse-web">{{ translate('Browse web') }}</a>
                @endif

                @if (isset($toggle_dm_registration) || isset($toggle_store_registration) || isset($toggle_rider_registration))
                <div class="join-wrap">
                    <button class="btn-join">{{ translate('Join us') }} <svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M2.5 3.75L5 6.25L7.5 3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></button>
                    <div class="join-dd">
                        @if (isset($toggle_store_registration) && $toggle_store_registration)
                        <a href="{{ route('restaurant.create') }}" class="{{ Request::is('vendor*') ? 'active' : '' }}">
                            <span class="ji" style="background:rgba(255,107,0,.1);color:#FF6B00;">&#x1F3EA;</span>
                            {{ translate('messages.vendor_registration') }}
                        </a>
                        @endif
                        @if (isset($toggle_dm_registration) && $toggle_dm_registration)
                        <a href="{{ route('deliveryman.create') }}" class="{{ Request::is('deliveryman*') ? 'active' : '' }}">
                            <span class="ji" style="background:rgba(0,190,101,.1);color:#00BE65;">&#x1F6F5;</span>
                            {{ translate('messages.deliveryman_registration') }}
                        </a>
                        @endif
                        @if (isset($toggle_rider_registration) && $toggle_rider_registration)
                        <a href="{{ route('rider.create') }}" class="{{ Request::is('rider*') ? 'active' : '' }}">
                            <span class="ji" style="background:rgba(0,121,227,.1);color:#0079E3;">&#x1F697;</span>
                            {{ translate('messages.rider_registration') }}
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                <button class="mob-btn" id="mobBtn" aria-label="Menu"><span></span><span></span><span></span></button>
            </div>
        </div>
    </nav>

    @yield('content')

    <!-- Footer -->
    <footer class="footer">
        @php($fixed_newsletter_title = \App\Models\DataSetting::where(['type' => 'admin_landing_page','key' => 'fixed_newsletter_title'])->first())
        @php($fixed_newsletter_title = isset($fixed_newsletter_title->value) ? $fixed_newsletter_title->value: null)
        @php($fixed_newsletter_sub_title = \App\Models\DataSetting::where(['type' => 'admin_landing_page','key' => 'fixed_newsletter_sub_title'])->first())
        @php($fixed_newsletter_sub_title = isset($fixed_newsletter_sub_title->value) ? $fixed_newsletter_sub_title->value: null)
        @php($fixed_footer_article_title = \App\Models\DataSetting::where(['type' => 'admin_landing_page','key' => 'fixed_footer_article_title'])->first())
        @php($fixed_footer_article_title = isset($fixed_footer_article_title->value) ? $fixed_footer_article_title->value: null)

        <div class="container">
            <!-- Newsletter -->
            <div class="newsletter-area">
                <h2>{{ $fixed_newsletter_title ?? translate('Sign Up to Our Newsletter') }}</h2>
                <p>{{ $fixed_newsletter_sub_title ?? translate('Receive Latest News, Updates and Many Other News Every Week') }}</p>
                <form class="nl-form" method="post" action="{{route('newsletter.subscribe')}}">
                    @csrf
                    <input type="email" name="email" placeholder="{{ translate('Enter your email address') }}" required />
                    <button type="submit">{{ translate('Subscribe') }}</button>
                </form>
            </div>

            <!-- Footer Grid -->
            <div class="footer-grid">
                <div class="f-brand">
                    <a href="{{route('home')}}" class="nav-logo">
                        <img class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" src="{{\App\CentralLogics\Helpers::logoFullUrl()}}" alt="image" />
                    </a>
                    <p>{{ $fixed_footer_article_title }}</p>
                    <div class="f-social">
                        @php($social_media = \App\Models\SocialMedia::where('status', 1)->get())
                        @if (isset($social_media))
                            @foreach ($social_media as $social)
                            <a href="{{ $social->link }}" target="_blank" aria-label="{{ $social->name }}">
                                <img src="{{ asset('public/assets/landing/img/footer/'. $social->name.'.svg') }}" alt="{{ $social->name }}">
                            </a>
                            @endforeach
                        @endif
                    </div>
                    @php($landing_page_links_footer = \App\Models\DataSetting::where(['type' => 'admin_landing_page','key' => 'download_user_app_links'])->first())
                    @php($landing_page_links_footer = isset($landing_page_links_footer->value) ? json_decode($landing_page_links_footer->value, true) : null)
                    @php($footer_playstore_url = \App\Models\BusinessSetting::where('key', 'app_url_android')->value('value'))
                    @php($footer_appstore_url = \App\Models\BusinessSetting::where('key', 'app_url_ios')->value('value'))
                    @if ((isset($landing_page_links_footer['playstore_url_status']) && $footer_playstore_url) || (isset($landing_page_links_footer['apple_store_url_status']) && $footer_appstore_url))
                    <div class="f-app-row">
                        @if (isset($landing_page_links_footer['playstore_url_status']) && $footer_playstore_url)
                        <a href="{{ $footer_playstore_url }}" target="_blank" class="f-app-link">
                            <svg viewBox="0 0 24 24"><path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 01-.61-.92V2.734a1 1 0 01.609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-1.4l2.834 1.638a1 1 0 010 1.726l-2.834 1.638-2.56-2.56 2.56-2.442zM5.864 1.458L16.8 7.79l-2.302 2.302-8.635-8.635z" fill="currentColor"/></svg>
                            <span class="fal-text"><span class="fal-sm">Get it on</span><span class="fal-lg">Google Play</span></span>
                        </a>
                        @endif
                        @if (isset($landing_page_links_footer['apple_store_url_status']) && $footer_appstore_url)
                        <a href="{{ $footer_appstore_url }}" target="_blank" class="f-app-link">
                            <svg viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" fill="currentColor"/></svg>
                            <span class="fal-text"><span class="fal-sm">Download on the</span><span class="fal-lg">App Store</span></span>
                        </a>
                        @endif
                    </div>
                    @endif
                </div>

                @php($landing_data_footer =\App\Models\DataSetting::where('type', 'admin_landing_page')->whereIn('key', ['shipping_policy_status','refund_policy_status','cancellation_policy_status'])->pluck('value','key')->toArray())
                <div>
                    <h5>{{translate("messages.Suppport")}}</h5>
                    <div class="f-links">
                        <a href="{{route('privacy-policy')}}">{{ translate('messages.privacy_policy') }}</a>
                        <a href="{{route('terms-and-conditions')}}">{{ translate('messages.terms_and_condition') }}</a>
                        @if (isset($landing_data_footer['refund_policy_status']) && $landing_data_footer['refund_policy_status'] == 1)
                        <a href="{{route('refund')}}">{{ translate('messages.Refund Policy') }}</a>
                        @endif
                        @if (isset($landing_data_footer['shipping_policy_status']) && $landing_data_footer['shipping_policy_status'] == 1)
                        <a href="{{route('shipping-policy')}}">{{ translate('messages.Shipping Policy') }}</a>
                        @endif
                        @if (isset($landing_data_footer['cancellation_policy_status']) && $landing_data_footer['cancellation_policy_status'] == 1)
                        <a href="{{route('cancelation')}}">{{ translate('messages.Cancelation Policy') }}</a>
                        @endif
                    </div>
                </div>

                <div>
                    <h5>{{translate("messages.Contact_Us")}}</h5>
                    <div class="f-contact">
                        <a href="#">&#x1F4CD; {{ \App\CentralLogics\Helpers::get_settings('address') }}</a>
                        <a href="mailto:{{ \App\CentralLogics\Helpers::get_settings('email_address') }}">&#x2709;&#xFE0F; {{ \App\CentralLogics\Helpers::get_settings('email_address') }}</a>
                        <a href="tel:{{ \App\CentralLogics\Helpers::get_settings('phone') }}">&#x1F4DE; {{ \App\CentralLogics\Helpers::get_settings('phone') }}</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom-bar">
                &copy; {{ \App\CentralLogics\Helpers::get_settings('footer_text') }}
                by {{ \App\CentralLogics\Helpers::get_settings('business_name') }}
            </div>
        </div>
    </footer>

    <script src="{{ asset('public/assets/landing/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('public/assets/landing/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('public/assets/landing/js/viewport.jquery.js') }}"></script>
    <script src="{{ asset('public/assets/landing/js/odometer.min.js') }}"></script>
    <script src="{{ asset('public/assets/admin/js/toastr.js') }}"></script>
    <script src="{{ asset('public/assets/admin/intltelinput/js/intlTelInput.min.js')}}"></script>
    {!! Toastr::message() !!}
    @if ($errors->any())
        <script>
            @foreach($errors->all() as $error)
            toastr.error('{{$error}}', Error, {
                CloseButton: true,
                ProgressBar: true
            });
            @endforeach
        </script>
    @endif

    @stack('script_2')

    <script>
        "use strict";
        // Init Bootstrap tooltips
        $(function(){ $('[data-toggle="tooltip"]').tooltip(); });

        // Mobile menu
        document.getElementById('mobBtn').addEventListener('click',()=>document.getElementById('mobileMenu').classList.add('open'));
        document.getElementById('closeMob').addEventListener('click',()=>document.getElementById('mobileMenu').classList.remove('open'));
        function closeMobile(){document.getElementById('mobileMenu').classList.remove('open')}

        // Language dropdown close on outside click
        document.addEventListener('click',e=>{const sw=document.getElementById('langSw');if(sw&&!sw.contains(e.target))sw.classList.remove('open')})
    </script>

    <script>
        "use strict";
        function initTelInputs() {
            const inputs = document.querySelectorAll('input[type="tel"]');
            inputs.forEach(input => {
                const iti = window.intlTelInput(input, {
                    initialCountry: "{{$countryCode}}",
                    utilsScript: "{{ asset('public/assets/admin/intltelinput/js/utils.js') }}",
                    autoInsertDialCode: true,
                    nationalMode: false,
                    formatOnDisplay: false,
                    strictMode: true,
                    @if (\App\CentralLogics\Helpers::get_business_settings('country_picker_status') != 1)
                        onlyCountries: ["{{$countryCode}}"],
                    @endif
                });
                const restoreDialCode = () => {
                    if (input.value.trim() === '') {
                        input.value = '+' + iti.getSelectedCountryData().dialCode;
                    }
                };
                input.addEventListener('blur', restoreDialCode);
                input.closest('form')?.addEventListener('submit', restoreDialCode);
            });
            $(document).off('keyup.telinput').on('keyup.telinput', 'input[type="tel"]', function () {
                const iti = window.intlTelInputGlobals.getInstance(this);
                if (!iti) return;
                let val = $(this).val();
                if (val.trim() === '') {
                    val = '+' + iti.getSelectedCountryData().dialCode;
                } else {
                    const plus = val.startsWith('+') ? '+' : '';
                    val = plus + val.replace(/[^\d]/g, '');
                }
                $(this).val(val);
            });
        }
        initTelInputs();
    </script>

</body>
</html>
