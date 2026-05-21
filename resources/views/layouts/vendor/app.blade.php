<!DOCTYPE html>
<?php
if (getEnvMode() == 'demo') {
    $site_direction = session()->get('site_direction_vendor');
} else {
    $site_direction = session()->has('vendor_site_direction') ? session()->get('vendor_site_direction') : 'ltr';
}


$countryCode = \App\CentralLogics\Helpers::get_business_settings('country') ?? 'auto';
$moduleType = \App\CentralLogics\Helpers::get_store_data()->module_type;
$storeDataForBadge = \App\CentralLogics\Helpers::get_store_data();
$verifiedBadgePopupShow = (bool) ($storeDataForBadge?->storeConfig?->verified_seller && !($storeDataForBadge?->storeConfig?->has_seen_verified_badge_popup));
$verifiedBadgePopupLabel = isset($moduleType) && $moduleType == 'rental' ? translate('messages.provider') : translate('messages.store');

?>
{{-- {{ dd($countryCode) }} --}}
<html dir="{{ $site_direction }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ $site_direction === 'rtl' ? 'active' : '' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" id="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title>@yield('title')</title>
    <!-- Favicon -->
    @php($logo = \App\Models\BusinessSetting::where(['key' => 'icon'])->first())
    <link rel="shortcut icon" href="">
    <link rel="icon" type="image/x-icon"
        href="{{\App\CentralLogics\Helpers::get_full_url('business', $logo?->value ?? '', $logo?->storage[0]?->value ?? 'public', 'favicon')}}">
    <!-- Font -->
    <link href="{{asset('public/assets/admin/css/fonts.css')}}" rel="stylesheet">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/emogi-area.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/style.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/intltelinput/css/intlTelInput.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/owl.min.css')}}">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/upload-single-image.css')}}">
    @if(addon_published_status('ReelsModule'))
        <link rel="stylesheet" href="{{ asset('Modules/ReelsModule/public/assets/css/reels.css') }}">
    @endif
    @stack('css_or_js')

    <script
        src="{{asset('public/assets/admin')}}/vendor/hs-navbar-vertical-aside/hs-navbar-vertical-aside-mini-cache.js"></script>
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
</head>

<body class="footer-offset">
    @if (getEnvMode() == 'demo')
        <div class="direction-toggle">
            <i class="tio-settings"></i>
            <span></span>
        </div>
    @endif
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div id="loading" class="initial-hidden">
                    <div class="loading-inner">
                        <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{--loader--}}

    <!-- Builder -->
    @include('layouts.vendor.partials._front-settings')
    <!-- End Builder -->

    <!-- JS Preview mode only -->
    @include('layouts.vendor.partials._header')

    @if(isset($moduleType) && $moduleType == 'rental')
        @include("rental::provider.partials._sidebar_{$moduleType}")
    @else
        @include('layouts.vendor.partials._sidebar')
    @endif
    <!-- END ONLY DEV -->

    <main id="content" role="main" class="main pointer-event">
        <!-- Content -->
        @yield('content')

        <!-- End Content -->

        <!-- Footer -->
        @include('layouts.vendor.partials._footer')
        <!-- End Footer -->

        <div class="d-none" id="text-validate-translate" data-required="{{ translate('this_field_is_required') }}"
            data-something-went-wrong="{{ translate('something_went_wrong!') }}"
            data-max-limit-crossed="{{ translate('max_limit_crossed') }}"
            data-file-size-larger="{{ translate('file_size_is_larger') }}"
            data-passwords-do-not-match="{{ translate('passwords_do_not_match') }}"
            data-valid-email="{{ translate('please_enter_a_valid_email') }}"
            data-password-validation="{{ translate('password_must_be_8+_chars_with_upper,_lower,_number_&_symbol') }}">
        </div>


        <div class="modal fade" id="toggle-modal">
            <div class="modal-dialog status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="max-349 mx-auto mb-20">
                            <div>
                                <div class="text-center">
                                    <img id="toggle-image" alt="" class="mb-20">
                                    <h5 class="modal-title" id="toggle-title"></h5>
                                </div>
                                <div class="text-center" id="toggle-message">
                                </div>
                            </div>
                            <div class="btn--container justify-content-center">
                                <button type="button" id="toggle-ok-button"
                                    class="btn btn--primary min-w-120 confirm-Toggle"
                                    data-dismiss="modal">{{translate('Ok')}}</button>
                                <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120"
                                    data-dismiss="modal">
                                    {{translate("Cancel")}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="toggle-status-modal">
            <div class="modal-dialog status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="max-349 mx-auto mb-20">
                            <div>
                                <div class="text-center">
                                    <img id="toggle-status-image" alt="" class="mb-20">
                                    <h5 class="modal-title" id="toggle-status-title"></h5>
                                </div>
                                <div class="text-center" id="toggle-status-message">
                                </div>
                            </div>
                            <div class="btn--container justify-content-center">
                                <button type="button" id="toggle-status-ok-button"
                                    class="btn btn--primary min-w-120 confirm-Status-Toggle"
                                    data-dismiss="modal">{{translate('Ok')}}</button>
                                <button id="reset_btn" type="reset" class="btn btn--cancel min-w-120"
                                    data-dismiss="modal">
                                    {{translate("Cancel")}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="popup-modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12">
                                <div class="text-center">

                                    <h2 class="update_notification_text">
                                        <i class="tio-shopping-cart-outlined"></i>
                                        {{translate('messages.You have new order, Check Please.')}}
                                    </h2>
                                    <hr>
                                    <button
                                        class="btn btn-primary check-order">{{translate('messages.Ok, let me check')}}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.partials._video-preview-modal')

        @if ($verifiedBadgePopupShow)
            <div class="modal fade" id="verified-badge-popup-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0 pb-0">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body pt-0 pb-4 px-4">
                            <div class="text-center">
                                <img src="{{ asset('public/assets/admin/img/badge-big.png') }}" alt="Verified badge"
                                    class="mb-3" style="max-width: 110px;">
                                <h3 class="mb-2">{{ translate('Congratulations!') }}</h3>
                                <p class="mb-0">{{ translate('You have received a verified badge.') }}
                                    {{ translate('It will appear next to your') }} {{ $verifiedBadgePopupLabel }}
                                    {{ translate('name to build customer trust.') }}
                                </p>
                            </div>
                        </div>
                        <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                            <button type="button" class="btn btn--primary min-w-120px"
                                data-dismiss="modal">{{ translate('Okay') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(addon_published_status('ReelsModule'))
            <script src="{{ asset('Modules/ReelsModule/public/assets/js/reel-upload.js') }}"></script>
        @endif


        <div class="modal fade" id="new-dynamic-submit-model">
            <div class="modal-dialog modal-dialog-centered status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="max-349 mx-auto mb-20">
                            <div>
                                <div class="text-center">
                                    <img id="image-src" class="mb-20">
                                    <h5 class="modal-title" id="toggle-title"></h5>
                                </div>
                                <div class="text-center" id="toggle-message">
                                    <h3 id="modal-title"></h3>
                                    <div id="modal-text"></div>
                                </div>

                            </div>
                            <div class="mb-4 d-none" id="note-data">
                                <textarea class="form-control" placeholder="{{ translate('your_note_here') }}"
                                    id="get-text-note" cols="5"></textarea>
                            </div>
                            <div class="btn--container justify-content-center">
                                <div id="hide-buttons">
                                    <div class="d-flex justify-content-center flex-wrap gap-3">
                                        <button data-dismiss="modal" id="cancel_btn_text"
                                            class="btn btn--cancel min-w-120">{{translate("Not_Now")}}</button>
                                        <button type="button" id="new-dynamic-ok-button"
                                            class="btn btn-primary confirm-model min-w-120">{{translate('Yes')}}</button>
                                    </div>
                                </div>

                                <button data-dismiss="modal" type="button" id="new-dynamic-ok-button-show"
                                    class="btn btn--primary  d-none min-w-120">{{translate('Okay')}}</button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!--- Global Image -->
        <div id="imageModal" class="imageModal modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header justify-content-end gap-3 border-0 p-2">
                        <button type="button"
                            class="modal_img-btn border-0 btn-circle rounded-circle bg-section2 shadow-none fs-8 m-0"
                            data-dismiss="modal" aria-label="Close">
                            <i class="tio-clear"></i>
                        </button>
                    </div>
                    <div class="modal-body text-center p-3 pt-0">
                        <div class="imageModal_img_wrapper">
                            <img src="" class="img-fluid imageModal_img" alt="{{ translate('Preview_Image') }}">
                            <div class="imageModal_btn_wrapper m-1">
                                <a href="javascript:" class="btn icon-btn px-1 py-1 download_btn"
                                    title="{{ translate('Download') }}" download>
                                    <i class="tio-arrow-large-downward"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-none" id="default-text-data"
            data-default-image-src="{{ asset('public/assets/admin/img/upload-img.png') }}"></div>
    </main>
    <!-- ========== END MAIN CONTENT ========== -->

    <!-- ========== END SECONDARY CONTENTS ========== -->
    <script src="{{asset('public/assets/admin')}}/js/custom.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/firebase.min.js"></script>
    <!-- JS Implementing Plugins -->

    @stack('script')

    <!-- JS Front -->
    <script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/emogi-area.js"></script>
    <script src="{{asset('public/assets/admin/js/owl.min.js')}}"></script>
    <script src="{{asset('public/assets/admin/js/app-blade/vendor.js')}}"></script>
    {!! Toastr::message() !!}
    <script src="{{asset('public/assets/admin/intltelinput/js/intlTelInput.min.js')}}"></script>
    <script src="{{asset('public/assets/admin/js/form-validate.js')}}"></script>
    <script src="{{asset('public/assets/admin/js/upload-single-image.js')}}"></script>


    @if ($errors->any())

        <script>
            "use strict";
            @foreach ($errors->all() as $error)
                toastr.error('{{ translate($error) }}', Error, {
                    CloseButton: true,
                    ProgressBar: true
                });
            @endforeach
        </script>
    @endif

    @stack('script_2')
    <audio id="myAudio">
        <source src="{{asset('public/assets/admin/sound/notification.mp3')}}" type="audio/mpeg">
    </audio>
    <script src="{{asset('public/assets/admin/js/view-pages/common.js')}}"></script>
    <script src="{{asset('public/assets/admin/js/keyword-highlighted.js')}}"></script>
    <script src="{{ asset('public/assets/admin') }}/js/offcanvas.js"></script>


    <script>
        const container = document.querySelector('.tabs-inner');
        const btnPrevWrap = document.querySelector('.button-prev');
        const btnNextWrap = document.querySelector('.button-next');
        const item = document.querySelector('.tabs-slide_items');

        document.querySelectorAll('.tabs-slide_items').forEach(el => {
            el.style.flex = '0 0 auto';
        });
        function updateArrows() {
            if (!container || !btnPrevWrap || !btnNextWrap) return;

            const hasOverflow = container.scrollWidth > container.clientWidth;
            if (!hasOverflow) {
                btnPrevWrap.style.display = 'none';
                btnNextWrap.style.display = 'none';
                return;
            }
            const scrollLeft = container.scrollLeft;
            const maxScroll = container.scrollWidth - container.clientWidth;

            if (scrollLeft > 2) {
                btnPrevWrap.style.display = 'flex';
            } else {
                btnPrevWrap.style.display = 'none';
            }

            if (scrollLeft < maxScroll - 2) {
                btnNextWrap.style.display = 'flex';
            } else {
                btnNextWrap.style.display = 'none';
            }
        }
        document.querySelector('.btn-click-prev')?.addEventListener('click', () => {
            const itemWidth = item?.offsetWidth || 100;
            container.scrollBy({ left: -itemWidth, behavior: 'smooth' });
        });
        document.querySelector('.btn-click-next')?.addEventListener('click', () => {
            const itemWidth = item?.offsetWidth || 100;
            container.scrollBy({ left: itemWidth, behavior: 'smooth' });
        });
        if (container) {
            container.addEventListener('scroll', updateArrows);
            ['load', 'resize'].forEach(evt => window.addEventListener(evt, updateArrows));
            new MutationObserver(updateArrows).observe(container, { childList: true, subtree: true });
            new ResizeObserver(updateArrows).observe(container);
        }
        // Initial update
        updateArrows();

    </script>

    <script>
        var audio = document.getElementById("myAudio");

        function playAudio() {
            audio.play();
        }

        function pauseAudio() {
            audio.pause();
        }
        "use strict";


        $(window).on('load', function () {
            $('main > .container-fluid.content').prepend($('#renew-badge'));
        })



        $(document).on('ready', function () {
            // $('body').css('overflow','')
            $(".direction-toggle").on("click", function () {
                if ($('html').hasClass('active')) {
                    $('html').removeClass('active')
                    setDirection(1);
                } else {
                    setDirection(0);
                    $('html').addClass('active')
                }
            });
            if ($('html').attr('dir') === "rtl") {
                $(".direction-toggle").find('span').text('Toggle LTR')
            } else {
                $(".direction-toggle").find('span').text('Toggle RTL')
            }

            function setDirection(status) {
                if (status === 1) {
                    $("html").attr('dir', 'ltr');
                    $(".direction-toggle").find('span').text('Toggle RTL')
                } else {
                    $("html").attr('dir', 'rtl');
                    $(".direction-toggle").find('span').text('Toggle LTR')
                }
                $.get({
                    url: '{{ route('vendor.site_direction') }}',
                    dataType: 'json',
                    data: {
                        status: status,
                    },
                    success: function () {
                    },

                });
            }
        });


        function route_alert(route, message) {
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    location.href = route;
                }
            })
        }

        $('.form-alert').on('click', function () {
            let id = $(this).data('id')
            let message = $(this).data('message')
            Swal.fire({
                title: '{{ translate('messages.Are you sure?') }}',
                text: message,
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#FC6A57',
                cancelButtonText: '{{ translate('messages.no') }}',
                confirmButtonText: '{{ translate('messages.Yes') }}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $('#' + id).submit()
                }
            })
        })


        function set_filter(url, id, filter_by) {
            let nurl = new URL(url);
            nurl.searchParams.set(filter_by, id);
            location.href = nurl;
        }

        @php($fcm_credentials = \App\CentralLogics\Helpers::get_business_settings('fcm_credentials'))
        let firebaseConfig = {
            apiKey: "{{isset($fcm_credentials['apiKey']) ? $fcm_credentials['apiKey'] : ''}}",
            authDomain: "{{isset($fcm_credentials['authDomain']) ? $fcm_credentials['authDomain'] : ''}}",
            projectId: "{{isset($fcm_credentials['projectId']) ? $fcm_credentials['projectId'] : ''}}",
            storageBucket: "{{isset($fcm_credentials['storageBucket']) ? $fcm_credentials['storageBucket'] : ''}}",
            messagingSenderId: "{{isset($fcm_credentials['messagingSenderId']) ? $fcm_credentials['messagingSenderId'] : ''}}",
            appId: "{{isset($fcm_credentials['appId']) ? $fcm_credentials['appId'] : ''}}",
            measurementId: "{{isset($fcm_credentials['measurementId']) ? $fcm_credentials['measurementId'] : ''}}"
        };
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        function startFCM() {
            messaging
                .requestPermission()
                .then(function () {
                    return messaging.getToken();
                })
                .then(function (token) {
                    @php($store_id = \App\CentralLogics\Helpers::get_store_id())
                    // Send the token to your backend to subscribe to topic
                    subscribeTokenToBackend(token, 'store_panel_{{$store_id}}_message');
                }).catch(function (error) {
                    console.error('Error getting permission or token:', error);
                });
        }

        function subscribeTokenToBackend(token, topic) {
            fetch('{{url('/')}}/subscribeToTopic', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ token: token, topic: topic })
            }).then(response => {
                if (response.status < 200 || response.status >= 400) {
                    return response.text().then(text => {
                        throw new Error(`Error subscribing to topic: ${response.status} - ${text}`);
                    });
                }
                console.log(`Subscribed to "${topic}"`);
            }).catch(error => {
                console.error('Subscription error:', error);
            });
        }
        function getUrlParameter(sParam) {
            let sPageURL = window.location.search.substring(1);
            let sURLletiables = sPageURL.split('&');
            for (let i = 0; i < sURLletiables.length; i++) {
                let sParameterName = sURLletiables[i].split('=');
                if (sParameterName[0] === sParam) {
                    return sParameterName[1];
                }
            }
        }

        function conversationList() {
            $.ajax({
                url: "{{ route('vendor.message.list') }}",
                success: function (data) {
                    $('#conversation-list').empty();
                    $("#conversation-list").append(data.html);
                    let user_id = getUrlParameter('user');
                    $('.customer-list').removeClass('conv-active');
                    $('#customer-' + user_id).addClass('conv-active');
                }
            })
        }

        function conversationView() {
            let conversation_id = getUrlParameter('conversation');
            let user_id = getUrlParameter('user');
            let url = '{{url('/')}}/vendor-panel/message/view/' + conversation_id + '/' + user_id;
            $.ajax({
                url: url,
                success: function (data) {
                    $('#view-conversation').html(data.view);
                }
            })
        }

        @php($order_notification_type = \App\CentralLogics\Helpers::get_business_settings('order_notification_type') ?? 'firebase')
        let order_type = 'all';
        let is_trip = false;
        messaging.onMessage(function (payload) {
            if (payload.data.order_id && payload.data.type === 'new_order') {
                @if(\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'firebase')
                    order_type = payload.data.order_type
                    if (order_type === 'trip') {
                        document.querySelector('.update_notification_text').textContent = "{{translate('messages.You have new trip, Check Please.')}}";
                        is_trip = true;
                    }
                    playAudio();
                    $('#popup-modal').appendTo("body").modal('show');
                @endif
            } else if (payload.data.type === 'message') {
                if (window.location.href.includes('message/list?conversation')) {
                    let conversation_id = getUrlParameter('conversation');
                    let user_id = getUrlParameter('user');
                    let url = '{{url('/')}}/vendor-panel/message/view/' + conversation_id + '/' + user_id;
                    $.ajax({
                        url: url,
                        success: function (data) {
                            $('#view-conversation').html(data.view);
                        }
                    })
                }
                toastr.success('{{ translate('messages.New message arrived') }}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                if ($('#conversation-list').scrollTop() === 0) {
                    conversationList();
                }
            }
        });

        @if(\App\CentralLogics\Helpers::employee_module_permission_check('order') && $order_notification_type == 'manual')
            setInterval(function () {
                $.get({
                    url: '{{route('vendor.get-store-data')}}',
                    dataType: 'json',
                    success: function (response) {
                        let data = response.data;

                        if (data.order_type === 'trip') {
                            document.querySelector('.update_notification_text').textContent = "{{translate('messages.You have new trip, Check Please.')}}";
                            is_trip = true;
                        }

                        if (data.new_pending_order > 0) {
                            order_type = 'pending';
                            playAudio();
                            $('#popup-modal').appendTo("body").modal('show');
                        }
                        else if (data.new_confirmed_order > 0) {
                            order_type = 'confirmed';
                            playAudio();
                            $('#popup-modal').appendTo("body").modal('show');
                        }
                    },
                });
            }, 10000);
        @endif

            @if ($verifiedBadgePopupShow)
                let verifiedBadgePopupSeen = false;
                $(window).on('load', function () {
                    $('#verified-badge-popup-modal').appendTo("body").modal('show');
                });
                $(document).on('hidden.bs.modal', '#verified-badge-popup-modal', function () {
                    if (verifiedBadgePopupSeen) {
                        return;
                    }
                    verifiedBadgePopupSeen = true;
                    $.ajax({
                        url: '{{ route('vendor.verified-badge-popup-seen') }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        }
                    });
                });
            @endif

        $('.check-order').on('click', function () {
            if (order_type) {
                if (is_trip === true) {
                    location.href = '{{url('/')}}/vendor-panel/trip?status=all';
                } else {
                    location.href = '{{url('/')}}/vendor-panel/order/list/' + order_type;

                }
            }
        });
        startFCM();
        conversationList();
        if (getUrlParameter('conversation')) {
            conversationView();
        }

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

        //search option
        $(document).ready(function () {
            $('#searchForm input[name="search"]').keyup(function () {
                var searchKeyword = $(this).val().trim();

                if (searchKeyword.length >= 1) {
                    $.ajax({
                        type: 'POST',
                        url: $('#searchForm').attr('action'),
                        data: { search: searchKeyword, _token: $('input[name="_token"]').val() },
                        success: function (response) {
                            if (response.length === 0) {
                                $('#searchResults').html('<div class="fs-16 fw-500 mb-2">' + @json(translate('Search Result')) + '</div>' +
                                    '<div class="search-list h-300 d-flex flex-column gap-2 justify-content-center align-items-center fs-16">' +
                                    '<img width="30" src="' + @json(asset('/public/assets/admin/img/no-search-found.png')) + '" alt="">' + ' ' +
                                    @json(translate('No result found')) +
                                    '</div>');

                            } else {
                                var resultHtml = '';
                                response.forEach(function (route) {
                                    var separator = route.fullRoute.includes('?') ? '&' : '?';
                                    var fullRouteWithKeyword = route.fullRoute + separator + 'keyword=' + encodeURIComponent(searchKeyword);

                                    var keywordRegex = searchKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                                    keywordRegex = new RegExp('(' + keywordRegex + ')', 'gi');
                                    var highlightedRouteName = route.routeName.replace(keywordRegex, '<mark  class="p-0">$1</mark>');
                                    var highlightedURI = route.URI.replace(keywordRegex, '<mark  class="p-0">$1</mark>');
                                    resultHtml += '<a href="' + fullRouteWithKeyword + '" class="search-list-item d-flex flex-column" data-route-name="' + route.routeName + '" data-route-uri="' + route.URI + '" data-route-full-url="' + route.fullRoute + '" aria-current="true">';
                                    resultHtml += '<h5>' + highlightedRouteName + '</h5>';
                                    resultHtml += '<p class="text-muted fs-12 mb-0">' + highlightedURI + '</p>';
                                    resultHtml += '</a>';
                                });
                                $('#searchResults').html('<div class="fs-16 fw-500 mb-2">' + @json(translate('Search Result')) + '</div>' + '<div class="search-list d-flex flex-column">' + resultHtml + '</div>');

                                $('.search-list-item').click(function () {
                                    var routeName = $(this).data('route-name');
                                    var routeUri = $(this).data('route-uri');
                                    var routeFullUrl = $(this).data('route-full-url');

                                    $.ajax({
                                        type: 'POST',
                                        url: '{{ route('vendor.store.clicked.route') }}',
                                        data: {
                                            routeName: routeName,
                                            routeUri: routeUri,
                                            routeFullUrl: routeFullUrl,
                                            searchKeyword: searchKeyword,
                                            _token: $('input[name="_token"]').val()
                                        },
                                        success: function (response) {
                                            console.log(response.message);
                                        },
                                        error: function (xhr, status, error) {
                                            console.error(xhr.responseText);
                                        }
                                    });
                                });
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr.responseText);
                        }
                    });
                } else {
                    $('#searchResults').html('<div class="text-center text-muted py-5">{{translate('Write something to search.')}}.</div>');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.ctrlKey && event.key === 'k') {
                event.preventDefault();
                document.getElementById('modalOpener').click();
            }
        });

        $(document).ready(function () {
            $("#staticBackdrop").on("shown.bs.modal", function () {
                $(this).find("#searchForm input[type=search]").val('');
                $('#searchResults').html('<div class="text-center text-muted py-5">{{translate('Loading recent searches')}}...</div>');
                $(this).find("#searchForm input[type=search]").focus();

                $.ajax({
                    type: 'GET',
                    url: '{{ route('vendor.recent.search') }}',
                    success: function (response) {
                        if (response.length === 0) {
                            $('#searchResults').html('<div class="text-center text-muted py-5">{{translate('It appears that you have not yet searched.')}}.</div>');
                        } else {
                            var resultHtml = '';
                            response.forEach(function (route) {
                                resultHtml += '<a href="' + route.route_full_url + '" class="search-list-item d-flex flex-column" data-route-name="' + route.route_name + '" data-route-uri="' + route.route_uri + '" data-route-full-url="' + route.route_full_url + '" aria-current="true">';
                                resultHtml += '<h5>' + route.route_name + '</h5>';
                                resultHtml += '<p class="text-muted fs-12  mb-0">' + route.route_uri + '</p>';
                                resultHtml += '</a>';
                            });
                            $('#searchResults').html('<div class="recent-search fs-16 fw-500 animate">' +
                                @json(translate('Recent Search')) + '<div class="search-list d-flex flex-column mt-2">' + resultHtml + '</div></div>');

                            $('.search-list-item').click(function () {
                                var routeName = $(this).data('route-name');
                                var routeUri = $(this).data('route-uri');
                                var routeFullUrl = $(this).data('route-full-url');
                                var searchKeyword = $('input[type=search]').val().trim();

                                $.ajax({
                                    type: 'POST',
                                    url: '{{ route('vendor.store.clicked.route') }}',
                                    data: {
                                        routeName: routeName,
                                        routeUri: routeUri,
                                        routeFullUrl: routeFullUrl,
                                        searchKeyword: searchKeyword,
                                        _token: $('input[name="_token"]').val()
                                    },
                                    success: function (response) {
                                        console.log(response.message);
                                    },
                                    error: function (xhr, status, error) {
                                        console.error(xhr.responseText);
                                    }
                                });
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error(xhr.responseText);
                        $('#searchResults').html('<div class="text-center text-muted py-5">{{translate('Error loading recent searches')}}.</div>');
                    }
                });
            });
        });

        $("#staticBackdrop").on("hidden.bs.modal", function () {
            $('#searchResults').empty();
        });

        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('search', function () {
            if (!this.value.trim()) {
                $('#searchResults').html('<div class="text-center text-muted py-5"></div>');
            }
        });

        $('#searchForm').submit(function (event) {
            event.preventDefault();
        });


    </script>


    <!-- IE Support -->
    <script>
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>
</body>

</html>