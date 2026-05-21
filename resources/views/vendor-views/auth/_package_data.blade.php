<div class="plan-slider owl-theme owl-carousel owl-refresh">
    @forelse ($packages as $key=> $package)
        <label
            class="__plan-item {{ (count($packages) > 4 && $key == 2) || (count($packages) < 5 && $key == 1) ? 'active' : '' }} ">
            <input type="radio" name="package_id"  {{ (count($packages) > 4 && $key == 2) || (count($packages) < 5 && $key == 1) ? 'checked' : '' }} id="package_id{{ $key }}" value="{{ $package->id }}"
                class="d-none">
            <div class="inner-div">
                <div class="text-center">

                    <h3 class="title">{{ $package->package_name }}</h3>
                    <h2 class="price">
                        {{ \App\CentralLogics\Helpers::format_currency($package->price) }}
                    </h2>
                    <div class="day-count">{{ $package->validity }}
                        {{ translate('messages.days') }}</div>
                </div>
                <ul class="info">

                    @if ($package->pos)
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.POS') }} </span>
                        </li>
                    @endif
                    @if ($package->mobile_app)
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.mobile_app') }} </span>
                        </li>
                    @endif
                    @if ($package->chat)
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.chatting_options') }} </span>
                        </li>
                    @endif
                    @if ($package->review)
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.review_section') }} </span>
                        </li>
                    @endif
                    @if ($package->self_delivery)
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.self_delivery') }} </span>
                        </li>
                    @endif
                    @if ($package->max_order == 'unlimited')
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ isset($module) && $module == 'rental' ?  translate('messages.Unlimited_Trips') : translate('messages.Unlimited_Orders') }} </span>
                        </li>
                    @else
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ $package->max_order }}
                                {{ isset($module) && $module == 'rental' ?  translate('messages.Trips') : translate('messages.Orders') }} </span>
                        </li>
                    @endif
                    @if ($package->max_product == 'unlimited')
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
                                {{ translate('messages.Unlimited_uploads') }} </span>
                        </li>
                    @else
                        <li>
                            <img src="{{ asset('/public/assets/landing/img/check-1.svg') }}" class="check"
                                alt="">
                            <img src="{{ asset('/public/assets/landing/img/check-2.svg') }}" class="check-white"
                                alt=""> <span>
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
<script>
    // Deferred init — owl carousel may not be loaded yet when this partial renders
    window._initPackageSlider = function() {
        if (typeof jQuery === 'undefined' || typeof jQuery.fn.owlCarousel === 'undefined') return;
        if (jQuery('.plan-slider').data('owl.carousel')) return; // already initialized

        jQuery('.plan-slider').owlCarousel({
            loop: false,
            margin: 20,
            responsiveClass: true,
            nav: false,
            dots: true,
            autoHeight: false,
            stagePadding: 0,
            startPosition: 1,
            responsive: {
                0:    { items: 1, margin: 12, stagePadding: 30 },
                480:  { items: 1, margin: 14, stagePadding: 50 },
                640:  { items: 2, margin: 16 },
                992:  { items: 3, margin: 20 },
                1200: { items: 4, margin: 20 }
            }
        });
    };
</script>
