"use strict";
$(document).on('ready', function () {
    $('#date_from').attr('min',(new Date()).toISOString().split('T')[0]);
    $('#date_to').attr('min',(new Date()).toISOString().split('T')[0]);
});

$('#reset_btn').click(function(){
    $('#coupon_title').val('');
    $('#coupon_code').val(null);
    $('#coupon_limit').val(null);
    $('#date_from').val(null);
    $('#date_to').val(null);
    $('#discount_type').val('amount');
    $('#discount').val(null);
    $('#max_discount').val(0);
    $('#min_purchase').val(0);
    $('#select_customer').val(null).trigger('change');
})

$('#discount_type').on('change', function() {
    if ($(this).val() === 'amount') {
        $('#discount').attr('max', $('#min_purchase').val() || 0);
        validateDiscount();
    } else {
        $('#discount').attr('max', 100);
    }
});

$('#min_purchase').on('input', function() {
    if ($('#discount_type').val() === 'amount') {
        $('#discount').attr('max', $(this).val() || 0);
        if (parseFloat($('#discount').val()) > parseFloat($(this).val())) {
            toastr.error($('#min-purchase-toast').val());
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
            toastr.error($('#min-purchase-toast').val());
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
        toastr.error($('#min-purchase-toast').val());
    }
}

$(document).ready(function() {
    $('#min_purchase').data('previous-value', $('#min_purchase').val());
    $('#discount').data('previous-value', $('#discount').val());
});
