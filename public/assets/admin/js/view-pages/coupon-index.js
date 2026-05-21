"use strict";
$(document).on('ready', function () {


    $('#min_purchase').data('previous-value', $('#min_purchase').val());
    $('#discount').data('previous-value', $('#discount').val());


    $('#discount_type').on('change', function () {
        discount_check();
    });
    $('#discount').on('click', function () {
        discount_check();
    });
    $('#min_purchase').on('click', function () {
        discount_check();

    });
    function discount_check() {
        if ($('#discount_type').val() == 'amount') {
            $('#max_discount').attr("readonly", "true");
            $('#max_discount').val(0);
            $('#discount').attr('max', $('#min_purchase').val() || 0);
            validateDiscount();
        }
        else {
            if ($('#discount_type').val() == 'percent') {
                $('#max_discount').removeAttr("readonly");
            }
            $('#discount').attr('max', 100);
        }
    }

    $('#date_from').attr('min', (new Date()).toISOString().split('T')[0]);
    $('#date_to').attr('min', (new Date()).toISOString().split('T')[0]);


    coupon_type_change($('#coupon_type').val());
});

$("#date_from").on("change", function () {
    $('#date_to').attr('min', $(this).val());
});

$("#date_to").on("change", function () {
    $('#date_from').attr('max', $(this).val());
});
$('#zone_wise').hide();
$('#coupon_type').on('change', function () {
    let coupon_type = $(this).val();
    coupon_type_change(coupon_type)
})
function coupon_type_change(coupon_type) {
    $('#zone_wise, #store_wise, #customer_wise').hide();
    $('#coupon_limit').attr("readonly", false);
    $('#limit_for_same_user').removeClass('d-none');
    switch (coupon_type) {
        case 'zone_wise':
            $('#zone_wise').show();
            break;

        case 'store_wise':
            $('#store_wise').show();
            $('#customer_wise').show();
            break;

        case 'first_order':
            $('#coupon_limit').val(1).attr("readonly", true);
            $('#limit_for_same_user').addClass('d-none');
            break;

        default:
            $('#customer_wise').show();
            $('#coupon_limit').val($('#coupon_limit').data('value')).attr("readonly", false);
            $('#limit_for_same_user').removeClass('d-none');
            break;
    }

    if (coupon_type === 'free_delivery') {
        $('#discount_type').attr("disabled", true).val("").trigger("change");
        $('#max_discount, #discount').val(0).attr("readonly", true);
    } else {
        $('#discount_type').removeAttr("disabled").attr("required", true);
        $('#max_discount, #discount').removeAttr("readonly");
    }

    if ($('#discount_type').val() === 'amount') {
        $('#max_discount').val(0).attr("readonly", true);
    } else if ($('#discount_type').val() === 'percent') {
        $('#max_discount').removeAttr("readonly");
    }
}


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

$('#reset_btn').click(function () {
    $('#module_select').val(null).trigger('change');
    $('#store_id').val(null).trigger('change');
    $('#store_wise').show();
    $('#zone_wise').hide();
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


function validateDiscount() {
    let discountType = $('#discount_type').val();
    let discountInput = $('#discount');
    let minPurchase = parseFloat($('#min_purchase').val()) || 0;
    let discountValue = parseFloat(discountInput.val()) || 0;

    if (discountType === 'amount' && discountValue > minPurchase) {
        discountInput.val(discountValue);
        // toastr.error($('#min-purchase-toast').val());
    }
}

$(document).on('click', '#generate_code', function () {
    let title = $('#default_title').val();
    let url = $(this).data('url');
    $.get({
        url: url,
        data: {
            title: title
        },
        success: function (data) {
            $('input[name="code"]').val(data);
            toastr.success($('#generate_code').data('success-message'));
        },
        error: function (error) {
            console.log(error);
        }
    });
});

$(document).on('click', '.copy-to-clipboard', function () {
    copyToClipboardById($(this).data('id'));
});

function copyToClipboardById(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        navigator.clipboard.writeText(element.value)
            .then(() => {
                toastr.success('Copied to clipboard!');
            })
            .catch(() => {
                toastr.error('Failed to copy!');
            });
    } else {
        toastr.warning('Element not found.');
    }
}



$(document).on('click', '.data-info-show', function () {
    let id = $(this).data('id');
    let url = $(this).data('url');
    $('#content-disable').addClass('disabled');
    fetch_data(id, url)
})



function fetch_data(id, url) {
    $.ajax({
        url: url,
        type: "get",
        beforeSend: function () {
            $('#data-view').empty();
            $('#loading').show()
        },
        success: function (data) {
            $("#data-view").append(data.view);
        },
        complete: function () {
            $('#loading').hide()
        }
    })
}
