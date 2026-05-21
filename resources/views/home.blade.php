@extends('layouts.landing.app')
@php($business_name = \App\CentralLogics\Helpers::get_business_settings('business_name'))
@section('title', translate('messages.landing_page') . ' | ' . $business_name != 'null' ? $business_name : 'Sixam Mart')
@section('content')

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>{!! \App\CentralLogics\Helpers::highlight($landing_data['fixed_header_title']) !!}</h1>
            <div class="hero-logo">
                <img class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" src="{{ \App\CentralLogics\Helpers::logoFullUrl()}}" alt="">
            </div>
            <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['fixed_header_sub_title']) !!}</p>
            @php($toggle_store_registration_hero = \App\CentralLogics\Helpers::get_business_settings('toggle_store_registration'))
            @php($toggle_dm_registration_hero = \App\CentralLogics\Helpers::get_business_settings('toggle_dm_registration'))
            <div class="hero-btns">
                @if ($toggle_store_registration_hero)
                <a href="{{ route('restaurant.create') }}" class="hero-btn hero-btn--vendor">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    {{ translate('Join as a Vendor') }}
                </a>
                @endif
                @if ($toggle_dm_registration_hero)
                <a href="{{ route('deliveryman.create') }}" class="hero-btn hero-btn--delivery">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    {{ translate('Join as a Delivery Partner') }}
                </a>
                @endif
                @if (addon_published_status('RideShare'))
                    @php($toggle_rider_reg = \App\CentralLogics\Helpers::get_data_settings(RIDE_SHARE_BUSINESS_SETTINGS, 'toggle_rider_registration')?->value ?? false)
                    @if ($toggle_rider_reg == 1)
                    <a href="{{ route('rider.create') }}" class="hero-btn hero-btn--delivery">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18 10l-2-4H7L5 10l-2.5 1.1C1.7 11.3 1 12.1 1 13v3c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                        {{ translate('Join as a Rider') }}
                    </a>
                    @endif
                @endif
            </div>
        </div>
        <div class="hero-illustration">
            @include('layouts.landing._hero-illustration')
        </div>
    </section>

    <!-- Services -->
    @php($modules = \App\Models\Module::Active()->get())
    @if($modules && count($modules) > 0)
    <section class="services-section">
        <div class="container">
            <div class="sec-header">
                <h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['fixed_module_title']) !!}</h2>
                <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['fixed_module_sub_title']) !!}</p>
            </div>
            <div class="slider-wrap svc-slider-wrap">
                <div class="svc-icons">
                    @foreach ($modules as $key => $item)
                    <button class="svc-icon-btn {{ $key == 0 ? 'active' : '' }}" data-svc="svc-{{ $key }}">
                        <span class="ico"><img class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}" src="{{ $item['icon_full_url'] ?? asset('public/assets/admin/img/100x100/2.png') }}" alt="{{ $item->module_name }}" /></span>
                        <span class="lbl">{{ translate("messages.{$item->module_name}") }}</span>
                    </button>
                    @endforeach
                </div>
                <div class="slider-nav">
                    <button class="slider-arrow slider-prev" data-target=".svc-icons">&#8592;</button>
                    <button class="slider-arrow slider-next" data-target=".svc-icons">&#8594;</button>
                </div>
            </div>
            <div class="svc-panels">
                @foreach ($modules as $key => $item)
                <div class="svc-panel {{ $key == 0 ? 'active' : '' }}" data-panel="svc-{{ $key }}">
                    <div class="svc-text">
                        <div class="venture-content-box">
                            {!! $item->description ?? '' !!}
                        </div>
                    </div>
                    <div class="svc-img">
                        <div class="img-card">
                            <img src="{{ $item['thumbnail_full_url'] ?? asset('public/assets/admin/img/100x100/2.png') }}" class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/100x100/2.png') }}" alt="{{ $item->module_name }}" />
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="svc-nav">
                <button class="svc-arrow" id="svcPrev">&#8592;</button>
                <button class="svc-arrow" id="svcNext">&#8594;</button>
            </div>
        </div>
    </section>
    @endif

    <!-- Marquee -->
    @php($promo_banners = $landing_data['promotional_banners'])
    @if(isset($promo_banners) && count($promo_banners) > 0)
    <section class="marquee">
        <div class="marquee-track">
            @foreach ($promo_banners as $item)
            <span class="mq-item"><span class="dot"></span><span class="find">{{ $item['title'] ?? '' }}</span> {{ $item['sub_title'] ?? '' }}</span>
            @endforeach
            @foreach ($promo_banners as $item)
            <span class="mq-item"><span class="dot"></span><span class="find">{{ $item['title'] ?? '' }}</span> {{ $item['sub_title'] ?? '' }}</span>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Features -->
    @php($feature = $landing_data['features'])
    @if (isset($feature) && count($feature) > 0)
    <section class="features">
        <div class="container">
            <div class="sec-header">
                <h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['feature_title']) !!}</h2>
                <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['feature_short_description']) !!}</p>
            </div>
            <div class="slider-wrap">
                <div class="feat-grid">
                    @foreach ($feature as $item)
                    <div class="feat-card">
                        <span class="feat-ico"><img src="{{ $item['image_full_url'] }}" alt="{{ $item['title'] ?? '' }}" /></span>
                        <h4>{{ $item['title'] ?? '' }}</h4>
                        <p>{{ $item['sub_title'] ?? '' }}</p>
                    </div>
                    @endforeach
                </div>
                <div class="slider-nav">
                    <button class="slider-arrow slider-prev" data-target=".feat-grid">&#8592;</button>
                    <button class="slider-arrow slider-next" data-target=".feat-grid">&#8594;</button>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Zones -->
    @if ($landing_data['available_zone_status'] && $landing_data['available_zone_list'])
    <section class="zones">
        <div class="container">
            <div class="zone-banner">
                <div class="zone-img-side">
                    <img src="{{ $landing_data['available_zone_image_full_url'] }}" alt="Delivery Zone Map" />
                </div>
                <div class="zone-text-side">
                    <h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['available_zone_title']) !!}</h2>
                    <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['available_zone_short_description']) !!}</p>
                    <div class="zone-tags">
                        @foreach ($landing_data['available_zone_list'] as $zone)
                            @if (count($zone['modules']->toArray()) > 0)
                            <div class="zone-tag" data-toggle="tooltip" data-placement="top" title="{{ count($zone['modules']->toArray()) > 0 ? implode(', ', $zone['modules']->toArray()) . ' ' . translate('are_available.') : translate('right_now_no_module_available.') }}"><span class="zone-dot"></span> {{ $zone['display_name'] }}</div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Referral -->
    <section class="referral">
        <div class="container">
            <div class="refer-card">
                <div><h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['fixed_referal_title']) !!}</h2></div>
            </div>
        </div>
    </section>

    <!-- Earn -->
    <section class="earn">
        <div class="container">
            <div class="earn-top">
                <h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['earning_title']) !!}</h2>
                <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['earning_sub_title']) !!}</p>
            </div>
            <div class="slider-wrap">
            <div class="earn-grid">
                <!-- Seller Card -->
                <div class="earn-card">
                    @if(!empty($landing_data['seller_card_image']))
                        <div class="earn-card-icon"><img src="{{ $landing_data['seller_card_image'] }}" alt="" style="width:44px;height:44px;object-fit:contain"></div>
                    @else
                        <div class="earn-card-icon">&#x1F3EA;</div>
                    @endif
                    <h3>{!! \App\CentralLogics\Helpers::highlight($landing_data['seller_card_title'] ?? translate('messages.Become a best') . ' $' . translate('messages.Seller') . '$') !!}</h3>
                    <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['seller_card_subtitle'] ?? translate('Grow your business with us. Reach thousands of customers and manage orders effortlessly.')) !!}</p>
                    @php($join_as_seller = $landing_data['seller_app_earning_links'])
                    <div class="earn-app-row">
                        @if (isset($join_as_seller['playstore_url_status']) && $join_as_seller['playstore_url_status'] == '1')
                        <a href="{{ isset($join_as_seller['playstore_url']) ? $join_as_seller['playstore_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/google-play.png') }}" alt="Google Play" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Get it on') }}</span><span class="ab-lg">{{ translate('Google Play') }}</span></span>
                        </a>
                        @endif
                        @if (isset($join_as_seller['apple_store_url_status']) && $join_as_seller['apple_store_url_status'] == '1')
                        <a href="{{ isset($join_as_seller['apple_store_url']) ? $join_as_seller['apple_store_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/apple-store.png') }}" alt="App Store" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Download on the') }}</span><span class="ab-lg">{{ translate('App Store') }}</span></span>
                        </a>
                        @endif
                    </div>
                </div>
                <!-- Deliveryman Card -->
                <div class="earn-card">
                    @if(!empty($landing_data['dm_card_image']))
                        <div class="earn-card-icon"><img src="{{ $landing_data['dm_card_image'] }}" alt="" style="width:44px;height:44px;object-fit:contain"></div>
                    @else
                        <div class="earn-card-icon">&#x1F6F5;</div>
                    @endif
                    <h3>{!! \App\CentralLogics\Helpers::highlight($landing_data['dm_card_title'] ?? translate('messages.Become a smart') . ' $' . translate('messages.Deliveryman') . '$') !!}</h3>
                    <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['dm_card_subtitle'] ?? translate('Deliver on your own schedule. Earn competitive pay with flexible hours and easy-to-use tools.')) !!}</p>
                    @php($join_as_dm = $landing_data['dm_app_earning_links'])
                    <div class="earn-app-row">
                        @if (isset($join_as_dm['playstore_url_status']) && $join_as_dm['playstore_url_status'] == '1')
                        <a href="{{ isset($join_as_dm['playstore_url']) ? $join_as_dm['playstore_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/google-play.png') }}" alt="Google Play" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Get it on') }}</span><span class="ab-lg">{{ translate('Google Play') }}</span></span>
                        </a>
                        @endif
                        @if (isset($join_as_dm['apple_store_url_status']) && $join_as_dm['apple_store_url_status'] == '1')
                        <a href="{{ isset($join_as_dm['apple_store_url']) ? $join_as_dm['apple_store_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/apple-store.png') }}" alt="App Store" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Download on the') }}</span><span class="ab-lg">{{ translate('App Store') }}</span></span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Rider Card -->
                @if(addon_published_status('RideShare'))
                <div class="earn-card">
                    @if(!empty($landing_data['rider_card_image']))
                        <div class="earn-card-icon"><img src="{{ $landing_data['rider_card_image'] }}" alt="" style="width:44px;height:44px;object-fit:contain"></div>
                    @else
                        <div class="earn-card-icon">&#x1F697;</div>
                    @endif
                    <h3>{!! \App\CentralLogics\Helpers::highlight($landing_data['rider_card_title'] ?? translate('messages.Become a smart') . ' $' . translate('messages.Rider') . '$') !!}</h3>
                    <p>{!! \App\CentralLogics\Helpers::highlight($landing_data['rider_card_subtitle'] ?? translate('Drive and earn on your own terms. Flexible rides, great pay, and easy-to-use tools.')) !!}</p>
                    @php($join_as_rider = $landing_data['rider_app_earning_links'])
                    <div class="earn-app-row">
                        @if (isset($join_as_rider['playstore_url_status']) && $join_as_rider['playstore_url_status'] == '1')
                        <a href="{{ isset($join_as_rider['playstore_url']) ? $join_as_rider['playstore_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/google-play.png') }}" alt="Google Play" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Get it on') }}</span><span class="ab-lg">{{ translate('Google Play') }}</span></span>
                        </a>
                        @endif
                        @if (isset($join_as_rider['apple_store_url_status']) && $join_as_rider['apple_store_url_status'] == '1')
                        <a href="{{ isset($join_as_rider['apple_store_url']) ? $join_as_rider['apple_store_url'] : '' }}" class="app-btn">
                            <img src="{{ asset('/public/assets/landing/img/apple-store.png') }}" alt="App Store" />
                            <span class="ab-text"><span class="ab-sm">{{ translate('Download on the') }}</span><span class="ab-lg">{{ translate('App Store') }}</span></span>
                        </a>
                        @endif
                    </div>
                </div>
                @endif
            </div>
                <div class="slider-nav">
                    <button class="slider-arrow slider-prev" data-target=".earn-grid">&#8592;</button>
                    <button class="slider-arrow slider-next" data-target=".earn-grid">&#8594;</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Special -->
    @php($special = $landing_data['criterias'])
    @if ($special && count($special) > 0)
    <section class="special">
        <div class="container"><div class="sec-header"><h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['why_choose_title']) !!}</h2></div></div>
        <div class="sp-track">
            @foreach ($special as $item)
                @if ($item->status == '1')
                <div class="sp-card">
                    <span class="sp-ico"><img src="{{ $item['image_full_url'] }}" alt="{{ $item['title'] }}" class="onerror-image" data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}" /></span>
                    <h4>{{ $item['title'] }}</h4>
                    <p>{{ $item['sub_title'] ?? '' }}</p>
                </div>
                @endif
            @endforeach
        </div>
        <div class="sp-dots"></div>
    </section>
    @endif

    <!-- Stats -->
    @php($counter = $landing_data['counter_section'])
    @if (isset($counter) && $counter['status'] == '1')
    <section class="stats">
        <div class="container">
            <div class="slider-wrap">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </div>
                    <div class="stat-num" data-t="{{ $counter['app_download_count_numbers'] ?? 0 }}">0<span class="plus">+</span></div>
                    <div class="stat-label">{{ translate('messages.Download') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </div>
                    <div class="stat-num" data-t="{{ $counter['seller_count_numbers'] ?? 0 }}">0<span class="plus">+</span></div>
                    <div class="stat-label">{{ translate('messages.Seller') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>
                    <div class="stat-num" data-t="{{ $counter['deliveryman_count_numbers'] ?? 0 }}">0<span class="plus">+</span></div>
                    <div class="stat-label">{{ translate('messages.Deliveryman') }}</div>
                </div>
                @if(addon_published_status('RideShare'))
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div class="stat-num" data-t="{{ $counter['rider_count_numbers'] ?? 0 }}">0<span class="plus">+</span></div>
                    <div class="stat-label">{{ translate('messages.Rider') }}</div>
                </div>
                @endif
                <div class="stat-card">
                    <div class="stat-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                    </div>
                    <div class="stat-num" data-t="{{ $counter['customer_count_numbers'] ?? 0 }}">0<span class="plus">+</span></div>
                    <div class="stat-label">{{ translate('messages.customer') }}</div>
                </div>
            </div>
                <div class="slider-nav">
                    <button class="slider-arrow slider-prev" data-target=".stats-grid">&#8592;</button>
                    <button class="slider-arrow slider-next" data-target=".stats-grid">&#8594;</button>
                </div>
            </div>
            <div class="stats-note">{{ translate('messages.Still increasing') }}</div>
        </div>
    </section>
    @endif

    <!-- CTA / Download App -->
    @php($landing_page_links = $landing_data['download_user_app_links'])
    @if (
        (isset($landing_page_links['playstore_url_status']) && $landing_page_links['playstore_url_status'] == '1') ||
            (isset($landing_page_links['apple_store_url_status']) && $landing_page_links['apple_store_url_status'] == '1'))
    <section class="cta-manage">
        <div class="container">
            <div class="cta-box">
                <div class="cta-info">
                    <h2>{{ translate('Lets') }} {{ translate('Manage_your_business') }} <span class="hl">{{ translate('Smartly_or_Earn') }}</span></h2>
                    <button class="cta-user-btn">{{ translate('User App') }}</button>
                    <div class="cta-app-row">
                        @if (isset($landing_page_links['playstore_url_status']) && $landing_page_links['playstore_url_status'] == '1')
                        <a href="{{ $landing_page_links['playstore_url'] }}" class="cta-app-link">
                            <img src="{{ asset('/public/assets/landing/img/google-play.png') }}" alt="Google Play" style="filter:brightness(10)" />
                            <span class="cal-text"><span class="cal-sm">{{ translate('Get it on') }}</span><span class="cal-lg">{{ translate('Google Play') }}</span></span>
                        </a>
                        @endif
                        @if (isset($landing_page_links['apple_store_url_status']) && $landing_page_links['apple_store_url_status'] == '1')
                        <a href="{{ $landing_page_links['apple_store_url'] }}" class="cta-app-link">
                            <img src="{{ asset('/public/assets/landing/img/apple-store.png') }}" alt="Apple Store" style="filter:brightness(10)" />
                            <span class="cal-text"><span class="cal-sm">{{ translate('Download on the') }}</span><span class="cal-lg">{{ translate('Apple Store') }}</span></span>
                        </a>
                        @endif
                    </div>
                </div>
                <div class="cta-visual">
                    <div class="cta-phone">
                        <img src="{{ \App\CentralLogics\Helpers::get_full_url('download_user_app_image', isset($landing_data['download_user_app_image']) ? $landing_data['download_user_app_image'] : null, isset($landing_data['download_user_app_image_storage']) ? $landing_data['download_user_app_image_storage'] : 'public') }}" alt="User App" />
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif

    <!-- Testimonials -->
    @php($testimonial = $landing_data['testimonials'])
    @if ($testimonial && count($testimonial) > 0)
    <section class="testimonials">
        <div class="container">
            <div class="sec-header"><h2>{!! \App\CentralLogics\Helpers::highlight($landing_data['testimonial_title']) !!}</h2></div>
            <div class="slider-wrap">
                <div class="test-grid">
                    @foreach ($testimonial as $data)
                    <div class="test-card">
                        <div class="qm">&ldquo;</div>
                        <div class="test-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div>
                        <p class="test-text">{{ $data['review'] }}</p>
                        <div class="test-author">
                            <div class="test-av"><img src="{{ $data['reviewer_image_full_url'] }}" alt="{{ $data['name'] }}" /></div>
                            <div>
                                <div class="test-name">{{ $data['name'] }}</div>
                                <div class="test-role">{{ $data['designation'] }}</div>
                                @if (isset($data['company_image']))
                                <div class="test-company"><img src="{{ $data['company_image_full_url'] }}" alt="" /></div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="slider-nav">
                    <button class="slider-arrow slider-prev" data-target=".test-grid">&#8592;</button>
                    <button class="slider-arrow slider-next" data-target=".test-grid">&#8594;</button>
                </div>
            </div>
        </div>
    </section>
    @endif

    @if (isset($new_user) && $new_user == true)
    <div class="modal fade show" id="welcome-modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0">
                <div class="modal-header border-0 pt-4 px-4">
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-sm-5 pb-5">
                    <div class="text-center">
                        <img src="{{ asset('/public/assets/landing/img/welcome.svg') }}" class="mw-100 mb-3 mx-auto d-block" alt="">
                        <h5 class="mb-3">{{ translate('Welcome_to') }} {{ $business_name }}!</h5>
                        <p class="m-0 mb-4">{{ translate('Thanks for joining us! Your registration is under review. Hang tight, we\'ll notify you once approved!') }}</p>
                        <button type="button" class="border-0 outline-0 shadow-none btn rounded-pill px-4" style="background:var(--green);color:#fff" data-bs-dismiss="modal">{{ translate('okay') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection
@push('script_2')
<script>
    "use strict";
    // Service tabs + prev/next
    const svcBtns=document.querySelectorAll('.svc-icon-btn'),svcPanels=document.querySelectorAll('.svc-panel');
    let svcIdx=0;
    function showSvc(i){svcIdx=i;svcBtns.forEach(b=>b.classList.remove('active'));svcPanels.forEach(p=>p.classList.remove('active'));if(svcBtns[i])svcBtns[i].classList.add('active');if(svcPanels[i])svcPanels[i].classList.add('active');if(svcBtns[i])svcBtns[i].scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'})}
    svcBtns.forEach((b,i)=>b.addEventListener('click',()=>showSvc(i)));
    const svcCount=svcBtns.length;
    const prevBtn=document.getElementById('svcPrev');
    const nextBtn=document.getElementById('svcNext');
    if(prevBtn)prevBtn.addEventListener('click',()=>showSvc((svcIdx-1+svcCount)%svcCount));
    if(nextBtn)nextBtn.addEventListener('click',()=>showSvc((svcIdx+1)%svcCount));

    // Stats counter with IntersectionObserver
    function formatStatNum(n){
        if(n>=1000000) return (n/1000000).toFixed(n%1000000===0?0:1).replace(/\.0$/,'')+'M';
        if(n>=100000) return (n/1000).toFixed(n%1000===0?0:1).replace(/\.0$/,'')+'K';
        return n.toLocaleString();
    }
    let counted=false;
    const statsEl=document.querySelector('.stats');
    if(statsEl){
        new IntersectionObserver(entries=>{entries.forEach(e=>{if(e.isIntersecting&&!counted){counted=true;document.querySelectorAll('.stat-num').forEach(c=>{const t=+c.dataset.t,dur=1800,s=performance.now();const tick=now=>{const p=Math.min((now-s)/dur,1);const v=Math.round((1-Math.pow(1-p,4))*t);c.innerHTML=formatStatNum(v)+'<span class="plus"> +</span>';if(p<1)requestAnimationFrame(tick)};requestAnimationFrame(tick)})}})},{threshold:.3}).observe(statsEl);
    }

    // Drag scroll for all scrollable sections
    function initDragScroll(el){
        if(!el)return;
        let dn=false,sx,sl;
        el.addEventListener('mousedown',e=>{dn=true;el.style.cursor='grabbing';sx=e.pageX-el.offsetLeft;sl=el.scrollLeft});
        el.addEventListener('mouseleave',()=>{dn=false;el.style.cursor='grab'});
        el.addEventListener('mouseup',()=>{dn=false;el.style.cursor='grab'});
        el.addEventListener('mousemove',e=>{if(!dn)return;e.preventDefault();el.scrollLeft=sl-(e.pageX-el.offsetLeft-sx)*1.5});
        let tx=0,tl=0;
        el.addEventListener('touchstart',e=>{tx=e.touches[0].pageX;tl=el.scrollLeft},{passive:true});
        el.addEventListener('touchmove',e=>{el.scrollLeft=tl+(tx-e.touches[0].pageX)},{passive:true});
    }
    initDragScroll(document.querySelector('.svc-icons'));
    initDragScroll(document.querySelector('.sp-track'));
    initDragScroll(document.querySelector('.feat-grid'));
    initDragScroll(document.querySelector('.earn-grid'));
    initDragScroll(document.querySelector('.stats-grid'));
    initDragScroll(document.querySelector('.test-grid'));

    // sp-track: overflow check, dot pagination
    (function(){
        var track=document.querySelector('.sp-track');
        var dotsWrap=document.querySelector('.sp-dots');
        if(!track||!dotsWrap)return;

        function checkOverflow(){
            track.classList.toggle('is-overflowing',track.scrollWidth>track.clientWidth+2);
            buildDots();
        }

        function buildDots(){
            dotsWrap.innerHTML='';
            var cards=track.querySelectorAll('.sp-card');
            if(!track.classList.contains('is-overflowing')||cards.length<2)return;
            cards.forEach(function(_,i){
                var dot=document.createElement('button');
                dot.className='sp-dot';
                dot.setAttribute('type','button');
                dot.addEventListener('click',function(){
                    cards[i].scrollIntoView({behavior:'smooth',inline:'center',block:'nearest'});
                });
                dotsWrap.appendChild(dot);
            });
            updateActiveDot();
        }

        function updateActiveDot(){
            var cards=track.querySelectorAll('.sp-card');
            var dots=dotsWrap.querySelectorAll('.sp-dot');
            if(!dots.length)return;
            var trackRect=track.getBoundingClientRect();
            var center=trackRect.left+trackRect.width/2;
            var closest=0,minDist=Infinity;
            cards.forEach(function(card,i){
                var rect=card.getBoundingClientRect();
                var dist=Math.abs(rect.left+rect.width/2-center);
                if(dist<minDist){minDist=dist;closest=i}
            });
            dots.forEach(function(d,i){d.classList.toggle('active',i===closest)});
        }

        track.addEventListener('scroll',updateActiveDot);
        window.addEventListener('resize',checkOverflow);
        checkOverflow();
    })();

    // Slider arrow buttons + visibility (RTL compatible)
    const isRtl=document.documentElement.dir==='rtl';
    function getScrollPos(el){return isRtl?-el.scrollLeft:el.scrollLeft}
    function getMaxScroll(el){return el.scrollWidth-el.clientWidth}

    function updateSliderArrows(track){
        if(!track)return;
        const wrap=track.closest('.slider-wrap');
        if(!wrap)return;
        const prev=wrap.querySelector('.slider-prev');
        const next=wrap.querySelector('.slider-next');
        const overflows=track.scrollWidth>track.clientWidth+2;
        const pos=getScrollPos(track);
        const max=getMaxScroll(track);
        if(prev){
            if(overflows&&pos>2)prev.classList.add('visible');
            else prev.classList.remove('visible');
        }
        if(next){
            if(overflows&&pos<max-2)next.classList.add('visible');
            else next.classList.remove('visible');
        }
    }
    document.querySelectorAll('.slider-wrap').forEach(wrap=>{
        const prev=wrap.querySelector('.slider-prev');
        const next=wrap.querySelector('.slider-next');
        const target=prev?document.querySelector(prev.dataset.target):null;
        if(!target)return;
        updateSliderArrows(target);
        target.addEventListener('scroll',()=>updateSliderArrows(target));
        window.addEventListener('resize',()=>updateSliderArrows(target));
        if(prev)prev.addEventListener('click',()=>{
            const card=target.querySelector(':scope > *');const w=card?card.offsetWidth+14:300;
            target.scrollBy({left:isRtl?w:-w,behavior:'smooth'});
        });
        if(next)next.addEventListener('click',()=>{
            const card=target.querySelector(':scope > *');const w=card?card.offsetWidth+14:300;
            target.scrollBy({left:isRtl?-w:w,behavior:'smooth'});
        });
    });

    // Welcome modal
    @if (isset($new_user) && $new_user == true)
        $(document).ready(function(){
            $('#welcome-modal').modal('show');
            var url = new URL(window.location);
            url.searchParams.delete('new_user');
            window.history.replaceState({}, '', url.toString());
        });
    @endif
</script>
@endpush
