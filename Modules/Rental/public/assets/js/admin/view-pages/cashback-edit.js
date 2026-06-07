
"use strict";


$(document).ready(function () {

    $('#cashback_type').on('change', function () {
        if ($('#cashback_type').val() == 'amount') {
            $('#max_discount').attr("readonly", "true");
            $('#max_discount').removeAttr("required");
            $('#max_discount').val($(this).data("max_discount"));
            $('#percentage').addClass('d-none');
            $('#cuttency_symbol').removeClass('d-none');
            $('#Cash_back_amount').attr('max', 99999999999);
        }
        else {
            $('#max_discount').removeAttr("readonly");
            $('#max_discount').attr("required", "true");
            $('#percentage').removeClass('d-none');
            $('#cuttency_symbol').addClass('d-none');
            $('#Cash_back_amount').attr('max', 100);

        }
    });

    $('#date_from').attr('min', (new Date()).toISOString().split('T')[0]);
    $('#date_to').attr('min', (new Date()).toISOString().split('T')[0]);

    $('.js-select2-custom').each(function () {
        let select2 = $.HSCore.components.HSSelect2.init($(this));
    });
});

$("#date_from").on("change", function () {
    $('#date_to').attr('min', $(this).val());
});

$("#date_to").on("change", function () {
    $('#date_from').attr('max', $(this).val());
});



$('#reset_btn').click(function () {
    setTimeout(reset_select, 100);
})
$('#select_customer').on('change', function () {
    let customer = $(this).val();
    if (Array.isArray(customer) && customer.includes("all")) {
        $('.select_customer_option').prop('disabled', true);
        customer = ["all"];
        $(this).val(customer);
    } else {
        $('.select_customer_option').prop('disabled', false);
    }
});
function reset_select() {
    $('#select_customer').trigger('change');
    if ($('#cashback_type').val() == 'amount') {
        $('#max_discount').attr("readonly", "true");
        $('#max_discount').removeAttr("required");
        $('#percentage').addClass('d-none');
        $('#cuttency_symbol').removeClass('d-none');
        $('#Cash_back_amount').attr('max', 99999999999);
    } else {
        $('#max_discount').removeAttr("readonly");
        $('#max_discount').attr("required", "true");
        $('#percentage').removeClass('d-none');
        $('#cuttency_symbol').addClass('d-none');
        $('#Cash_back_amount').attr('max', 100);
    }
}
$(document).on('ready', function () {
    $('#date_from').attr('min', (new Date()).toISOString().split('T')[0]);
    $('#date_from').attr('max', $('#default_cashback_end_date').val());
    $('#date_to').attr('min', $('#default_cashback_start_date').val());
});
