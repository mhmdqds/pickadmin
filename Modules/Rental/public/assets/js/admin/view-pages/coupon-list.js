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
            toastr.error($('#min_purchase').data('error-text'));
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
        toastr.error($('#min_purchase').data('error-text'));
    }
}



$(document).on('ready', function () {
var module_id = $('#current_module_id').val();
var url = $('#store_id').attr('data-url');
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
            var $request = $.ajax(params);
            $request.then(success);
            $request.fail(failure);
            return $request;
        }
    }
});
});
