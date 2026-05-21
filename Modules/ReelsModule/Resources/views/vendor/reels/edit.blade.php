@extends('layouts.vendor.app')

@section('title', translate('messages.Edit_Reels'))
@section('vendor_reels', 'active')
@section('vendor_reels_list', 'active')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('public/assets/admin/vendor/daterangepicker/daterangepicker.css') }}"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <h2 class="fs-20 text-capitalize lh-1 mb-20">{{ translate('messages.Edit_Reels') }}</h2>

        <form id="reel-form" action="{{ route('vendor.reels.update', $reel->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('reelsmodule::vendor.reels.partials._form', ['isEdit' => true])
        </form>
    </div>
@endsection

@push('script_2')
    <script type="text/javascript" src="{{ asset('public/assets/admin/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('public/assets/admin/js/daterangepicker.min.js') }}"></script>
    <script>
        "use strict";
        function parseDateRange(value) {
            if (!value) {
                return null;
            }

            const parts = value.split(' - ').map(part => part.trim());
            if (parts.length !== 2) {
                return null;
            }

            const start = moment(parts[0], 'MM/DD/YYYY', true);
            const end = moment(parts[1], 'MM/DD/YYYY', true);

            return start.isValid() && end.isValid() ? { start, end } : null;
        }

        function initReelDateRange() {
            const $dates = $('input[name="dates"]');
            if (!$dates.length) return;
            const currentValue = $dates.val() || $dates.data('initial-value') || '';
            const range = parseDateRange(currentValue);
            const startMoment = range && range.start ? range.start : moment().startOf('day');
            const endMoment = range && range.end ? range.end : moment().endOf('day');
            const minMoment = range && range.start && range.start.isBefore(moment(), 'day')
                ? range.start.clone().startOf('day')
                : moment().startOf('day');
            $dates.daterangepicker({
                drops: 'up',
                opens: 'right',
                startDate: startMoment,
                endDate: endMoment,
                minDate: minMoment,
                autoUpdateInput: false,
                autoApply: false,
                alwaysShowCalendars: true,
                locale: { format: 'MM/DD/YYYY', cancelLabel: 'Clear' },
            });
            if (range) {
                const picker = $dates.data('daterangepicker');
                if (picker) {
                    picker.setStartDate(range.start);
                    picker.setEndDate(range.end);
                    picker.updateView();
                    picker.updateCalendars();
                }
                $dates.val(currentValue).data('last-value', currentValue);
            }
            $dates.on('apply.daterangepicker', function (ev, picker) {
                const value = picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY');
                $(this).val(value).data('last-value', value);
                clearReelDateValidation(this);
            });
            $dates.on('cancel.daterangepicker', function () {
                $(this).val('').data('last-value', '');
            });
        }
        function clearReelDateValidation(input) {
            if (window.FormValidation) {
                window.FormValidation.clearError(input);
            }
            $(input).removeClass('is-invalid');
        }
        function toggleAlwaysVisibleState(isInitialLoad = false) {
            const $dates = $('#dates');
            const dates = $dates[0];
            const checked = $('#is_always_visible').is(':checked');
            const preservedValue = $dates.data('last-value') || $dates.data('initial-value') || '';
            if (!isInitialLoad && checked && $dates.val()) $dates.data('last-value', $dates.val());
            $dates.prop('disabled', checked).prop('required', !checked);
            if (checked) {
                if (!isInitialLoad) $dates.val('');
            } else if (!$dates.val() && preservedValue) {
                $dates.val(preservedValue);
            }
            clearReelDateValidation(dates);
        }
        function updateTextCounters() {
            $('.reel-des-textarea').each(function () {
                $(this).siblings('.text-counting').text($(this).val().length + '/200');
            });
        }
        function submitReelForm(form) {
            const formData = new FormData(form);
            const thumbnailInput = form.querySelector('input[name="thumbnail"]');
            const videoInput = form.querySelector('input[name="video"]');
            if (thumbnailInput?.files?.[0]) formData.set('thumbnail', thumbnailInput.files[0]);
            if (videoInput?.files?.[0]) formData.set('video', videoInput.files[0]);
            $.ajax({
                url: $(form).attr('action'),
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () { $('#loading').show(); },
                success: function (response) {
                    if (response.errors?.length) {
                        response.errors.forEach(error => toastr.error(error.message));
                        return;
                    }
                    toastr.success(response.message);
                    if (response.redirect) window.location.href = response.redirect;
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    const errors = Array.isArray(response.errors) ? response.errors : [];
                    if (errors.length) {
                        errors.forEach(error => toastr.error(error.message));
                        return;
                    }
                    toastr.error(response.message || '{{ translate('messages.Something_went_wrong') }}');
                },
                complete: function () { $('#loading').hide(); }
            });
        }
        $(function () {
            initReelDateRange();
            toggleAlwaysVisibleState(true);
            updateTextCounters();
            $(document).on('change', '#is_always_visible', function () {
                toggleAlwaysVisibleState(false);
            });
            $(document).on('input', '.reel-des-textarea', updateTextCounters);
            $('#reel-form').on('submit', function (e) {
                e.preventDefault();
                submitReelForm(this);
            });
        });
    </script>
@endpush
