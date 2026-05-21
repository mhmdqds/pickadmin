@extends('layouts.admin.app')

@section('title',translate('messages.admin_landing_page'))

@section('content')
<div class="content container-fluid">
    <div class="page-header pb-0">
        <div class="d-flex flex-wrap justify-content-between">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/landing.png')}}" class="w--20" alt="">
                </span>
                <span>
                    {{ translate('messages.admin_landing_pages') }}
                </span>
            </h1>
            <div class="text--primary-2 py-1 d-flex flex-wrap align-items-center" type="button" data-toggle="modal" data-target="#how-it-works">
                <strong class="mr-2">{{translate('See_how_it_works!')}}</strong>
                <div>
                    <i class="tio-info-outined"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="mb-20 mt-2">
        <div class="js-nav-scroller hs-nav-scroller-horizontal">
            @include('admin-views.business-settings.landing-page-settings.top-menu-links.admin-landing-page-links')
        </div>
    </div>

    @php($backgroundChange = \App\Models\BusinessSetting::where(['key' => 'backgroundChange'])->first())
    @php($backgroundChange = isset($backgroundChange->value) ? json_decode($backgroundChange->value, true) : null)
    @php($currentColor = $backgroundChange['primary_1_hex'] ?? '#EF7822')
    @php($defaultColor = '#EF7822')

    <div class="card my-2">
        <div class="card-header">
            <h5 class="card-title d-flex align-items-center gap-2 mb-0">
                <i class="tio-color-bucket" style="font-size:1.2rem"></i>
                {{ translate('Primary Color') }}
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted mb-4" style="font-size:.875rem">
                {{ translate('This color will be applied to buttons, links, accents and other primary elements across the landing page.') }}
            </p>

            <form action="{{ route('admin.business-settings.admin-landing-page-settings', 'background-color') }}" method="POST">
                @csrf

                <div class="d-flex align-items-center gap-4 mb-4">
                    {{-- Color Swatch --}}
                    <div>
                        <label for="header-bg" style="display:block;width:72px;height:72px;border-radius:12px;border:2px solid #e7eaf3;cursor:pointer;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.08);position:relative">
                            <input id="header-bg" name="header-bg" type="color" value="{{ $currentColor }}"
                                   style="position:absolute;inset:0;width:100%;height:100%;border:none;padding:0;cursor:pointer;opacity:0" required>
                            <div id="colorSwatch" style="width:100%;height:100%;background:{{ $currentColor }};transition:background .15s"></div>
                        </label>
                    </div>

                    {{-- Hex Input --}}
                    <div>
                        <label class="form-label mb-1" style="font-size:.8rem;font-weight:600;color:#8c98a4">{{ translate('HEX Code') }}</label>
                        <div class="input-group" style="max-width:200px">
                            <span class="input-group-text" style="background:#f8fafd;border-color:#e7eaf3;font-weight:700;color:#8c98a4">#</span>
                            <input type="text" id="hexInput" class="form-control" value="{{ ltrim($currentColor, '#') }}"
                                   maxlength="6" pattern="[0-9A-Fa-f]{6}" placeholder="EF7822"
                                   style="font-family:monospace;font-size:.9rem;font-weight:600;letter-spacing:.05em;border-color:#e7eaf3">
                        </div>
                    </div>
                </div>

                {{-- Preset Colors --}}
                <div class="mb-4">
                    <label class="form-label mb-2" style="font-size:.8rem;font-weight:600;color:#8c98a4">{{ translate('Quick Presets') }}</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['#279B59','#EF7822','#4CAF50','#2196F3','#9C27B0','#E91E63','#FF5722','#009688','#3F51B5','#FF9800','#607D8B'] as $preset)
                        <button type="button" class="color-preset {{ strtoupper($currentColor) === $preset ? 'active' : '' }}" data-color="{{ $preset }}"
                                style="width:32px;height:32px;border-radius:8px;border:2px solid transparent;background:{{ $preset }};cursor:pointer;transition:.2s;padding:0;outline:none"
                                title="{{ $preset }}"></button>
                        @endforeach
                    </div>
                </div>

                <hr style="border-color:#f0f2f6">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size:.85rem">{{ translate('Current color') }}:</span>
                        <span id="currentColorBadge" style="display:inline-flex;align-items:center;gap:6px;padding:4px 12px;border-radius:6px;background:#f8fafd;border:1px solid #e7eaf3;font-family:monospace;font-weight:600;font-size:.85rem">
                            <span id="badgeDot" style="width:12px;height:12px;border-radius:50%;background:{{ $currentColor }}"></span>
                            <span id="badgeHex">{{ $currentColor }}</span>
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" id="resetColor" class="btn btn-outline-secondary" style="min-width:100px">
                            <i class="tio-refresh mr-1"></i> {{ translate('Reset') }}
                        </button>
                        <button type="submit" class="btn btn--primary" style="min-width:120px">
                            {{ translate('messages.submit') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- How it Works -->
@include('admin-views.business-settings.landing-page-settings.partial.how-it-work')

@endsection

@push('script_2')
<style>
    .color-preset:hover { transform: scale(1.15); box-shadow: 0 2px 8px rgba(0,0,0,.15) }
    .color-preset.active { border-color: #1a2b49 !important; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1a2b49 }
</style>
<script>
    "use strict";
    $(document).ready(function() {
        var defaultColor = '{{ $currentColor }}';
        var colorInput = $('#header-bg');
        var hexInput = $('#hexInput');
        var swatch = $('#colorSwatch');
        var badgeDot = $('#badgeDot');
        var badgeHex = $('#badgeHex');

        function setColor(hex) {
            hex = hex.toUpperCase();
            colorInput.val(hex);
            hexInput.val(hex.replace('#', ''));
            swatch.css('background', hex);
            badgeDot.css('background', hex);
            badgeHex.text(hex);
            $('.color-preset').removeClass('active').css('border-color', 'transparent');
            $('.color-preset[data-color="' + hex + '"]').addClass('active').css('border-color', '#1a2b49');
        }

        colorInput.on('input', function() {
            setColor($(this).val());
        });

        hexInput.on('input', function() {
            var val = $(this).val().replace(/[^0-9A-Fa-f]/g, '').substring(0, 6);
            $(this).val(val);
            if (val.length === 6) {
                setColor('#' + val);
            }
        });

        $('.color-preset').on('click', function() {
            setColor($(this).data('color'));
        });

        $('#resetColor').on('click', function() {
            setColor(defaultColor);
        });
    });
</script>
@endpush
