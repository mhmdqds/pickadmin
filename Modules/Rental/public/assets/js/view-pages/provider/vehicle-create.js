"use strict";
$(document).ready(function () {
    function getApplicablePrice() {
        let hourlyChecked = $('input[name="trip_hourly"]').is(':checked');
        let distanceChecked = $('input[name="trip_distance"]').is(':checked');
        let hourlyPrice = parseFloat($('input[name="hourly_price"]').val()) || 0;
        let distancePrice = parseFloat($('input[name="distance_price"]').val()) || 0;

        if (hourlyChecked && distanceChecked) {
            return Math.min(hourlyPrice, distancePrice);
        } else if (hourlyChecked) {
            return hourlyPrice;
        } else if (distanceChecked) {
            return distancePrice;
        }
        return 0;
    }

    $('#discount_input').on('input', function () {
        let discountType = $('#discount_type').val();
        let inputValue = parseFloat($(this).val());
        let applicablePrice = getApplicablePrice();

        if (discountType === 'percent' && inputValue >= 100) {
            $(this).val(99);
        } else if (discountType === 'amount' && inputValue > applicablePrice) {
            $(this).val(applicablePrice);
        }
    });

    $('input[name="trip_hourly"], input[name="trip_distance"], input[name="hourly_price"], input[name="distance_price"]').on('change input', function () {
        $('#discount_input').trigger('input');
    });

    const MAX_FILE_SIZE_MB = 1;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];

    $('.single_file_input').on('change', function (event) {
        var type = $(this).data('type');
        var size = $(this).data('size');
        var file = event.target.files[0];
        var $card = $(event.target).closest('.upload-file');
        var $textbox = $card.find('.upload-file-textbox');
        var $imgElement = $card.find('.upload-file-img');
        var $removeBtn = $card.find('.remove-btn');

        if (!ALLOWED_FILE_TYPES.includes(file.type)) {
            toastr.error( type, {
                CloseButton: true,
                ProgressBar: true
            });
            $(this).val('');
            return;
        }

        if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
            toastr.error(size, {
                CloseButton: true,
                ProgressBar: true
            });
            $(this).val('');
            return;
        }

        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $textbox.hide();
                $imgElement.attr('src', e.target.result).show();
                $removeBtn.css('opacity', 1);
            };
            reader.readAsDataURL(file);
        }
    });

    $('.remove-btn').click(function () {
        var $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-textbox').show();
        $card.find('.upload-file-img').hide().attr('src', '');
        $(this).css('opacity', 0);
    });

    $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-textbox').show();
            $(this).find('.upload-file-img').hide().attr('src', '');
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });

    toggleButton();

    $('input[name="multiple_vehicles"]').change(function () {
        toggleButton();
        if (!$(this).is(':checked')) {
            $('.equal-width').not('#input-container').remove();
        }
    });

    function toggleButton() {
        if ($('input[name="multiple_vehicles"]').is(':checked')) {
            $('.add-btn').show();
        } else {
            $('.add-btn').hide();
        }
    }
});

$(document).on('click', '.vin-remove-btn', function() {
    $(this).closest('.equal-width').remove();
});

$(document).on('click', '.add-btn', function() {
    var vin = $(this).data('vin');
    var license = $(this).data('license');
    let newDiv = $('<div class="d-flex gap-20px flex-column flex-md-row equal-width">\
                    <div class="form-group mb-0">\
                        <label class="input-label" for="">'+vin+'</label>\
                        <input type="text" name="vehicle[vin_number][]" class="form-control" placeholder="Type your business name" value="">\
                    </div>\
                    <div class="form-group mb-0">\
                        <label class="input-label" for="">'+license+'</label>\
                        <input type="text" name="vehicle[license_plate_number][]" class="form-control" placeholder="Type your license plate number" value="">\
                    </div>\
                    <button type="button" class="btn vin-remove-btn shadow-none text--danger p-0 fs-32 lh--1 text-left mt-md-4">\
                        <i class="tio-clear-circle-outlined"></i>\
                    </button>\
                </div>');

    newDiv.insertBefore('.equal-width:last');
});

$(document).ready(function () {
    const $tripHourly = $('input[name="trip_hourly"]');
    const $tripDistance = $('input[name="trip_distance"]');
    const $hourlyPrice = $('input[name="hourly_price"]');
    const $distancePrice = $('input[name="distance_price"]');

    function updateInputs() {
        if (!$tripHourly.is(':checked')) {
            $hourlyPrice.prop('disabled', true).val('');
        } else {
            $hourlyPrice.prop('disabled', false);
        }

        if (!$tripDistance.is(':checked')) {
            $distancePrice.prop('disabled', true).val('');
        } else {
            $distancePrice.prop('disabled', false);
        }

        if (!$tripHourly.is(':checked') && !$tripDistance.is(':checked')) {
            $tripHourly.prop('checked', true);
            $hourlyPrice.prop('disabled', false);
        }
    }

    $tripHourly.change(updateInputs);
    $tripDistance.change(updateInputs);

    updateInputs();

    $('#pickup_zones12').select2({
        placeholder: "Type and press Enter",
        tags: true,
        tokenSeparators: [',', ' ', ';'],
        createTag: function(params) {
            return {
                id: params.term,
                text: params.term
            };
        },
        insertTag: function (data, tag) {
            data.push(tag);
        }
    });
});
