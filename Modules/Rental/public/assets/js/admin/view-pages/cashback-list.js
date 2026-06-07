
"use strict";

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
$(document).on('ready', function () {

    let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'), {
        select: {
            style: 'multi',
            classMap: {
                checkAll: '#datatableCheckAll',
                counter: '#datatableCounter',
                counterInfo: '#datatableCounterInfo'
            }
        }
    });
});
