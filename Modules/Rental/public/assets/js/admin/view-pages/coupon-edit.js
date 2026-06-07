"use strict";
$('#min_purchase').on('input', function() {
    if ($('#discount_type').val() === 'amount') {
        $('#discount').attr('max', $(this).val() || 0);
        if (parseFloat($('#discount').val()) > parseFloat($(this).val())) {
            toastr.error($('#min_purchase').data('error-text'));
            $(this).val($(this).data('previous-value'));
        }
    }
    $(this).data('previous-value', $(this).val());
});

$('#discount').on('input', function() {
    if ($('#discount_type').val() === 'amount') {
        let minPurchase = parseFloat($('#min_purchase').val()) || 0;
        let discountValue = parseFloat($(this).val()) || 0;

        if (discountValue > minPurchase) {
            toastr.error($);
            $(this).val($(this).data('previous-value'));
        }
    }
    $(this).data('previous-value', $(this).val());
});

function validateDiscount() {
    let discountType = $('#discount_type').val();
    let discountInput = $('#discount');
    let minPurchase = parseFloat($('#min_purchase').val()) || 0;
    let discountValue = parseFloat(discountInput.val()) || 0;

    if (discountType === 'amount' && discountValue > minPurchase) {
        discountInput.val(discountValue);
        toastr.error($);
    }
}
coupon_type_change($('#defaut_coupon_type').val());

$(document).on('ready', function () {
    var module_id = $('#current_module_id').val();
    var url = $('#store_id').attr('data-url');
    $('#date_from').attr('max', $('#defaut_coupon_expire_date').val());
    $('#date_to').attr('min', $('#defaut_coupon_start_date').val());

    if($('#defaut_coupon_discount_type').val()=='amount'){
        $('#max_discount').attr("readonly","true");
        $('#max_discount').val(0);
    }
    coupon_type_change($('#defaut_banner_type').val());
    $('.js-data-example-ajax').select2({
        ajax: {
            url: url,
            data: function (params) {
                return {
                    q: params.term,
                    page: params.page,
                    module_id: module_id
                };
            },
            processResults: function (data) {
                return {
                results: data
                };
            },
            __port: function (params, success, failure) {
                let $request = $.ajax(params);

                $request.then(success);
                $request.fail(failure);

                return $request;
            }
        }
    });

    $('.js-flatpickr').each(function () {
        $.HSCore.components.HSFlatpickr.init($(this));
    });
});

