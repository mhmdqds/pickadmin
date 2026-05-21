"use strict";

$(document).on('ready', function () {
    $('#date_from').attr('max', (new Date()).toISOString().split('T')[0]);
    $('#date_to').attr('max', (new Date()).toISOString().split('T')[0]);

    $('.id_wise').hide();
    $('.date_wise').hide();

    $('#type').on('change', function () {

        if ($(this).val() == 'id_wise') {
            $('#step_2_defaul').addClass('d-none');
            $('#step_2_date_wise').addClass('d-none');
            $('#step_2_id_wise').removeClass('d-none');
        }
        else if ($(this).val() == 'date_wise') {
            $('#step_2_defaul').addClass('d-none');
            $('#step_2_id_wise').addClass('d-none');
            $('#step_2_date_wise').removeClass('d-none');

        } else {
            $('#step_2_date_wise').addClass('d-none');
            $('#step_2_id_wise').addClass('d-none');
            $('#step_2_defaul').removeClass('d-none');
        }
        $('.id_wise').hide();
        $('.date_wise').hide();
        $('.' + $(this).val()).show();
    });

    // Date validation
    $('#date_from, #date_to').on('change', function () {
        const fromDate = $('#date_from').val();
        const toDate = $('#date_to').val();

        if (fromDate && toDate && new Date(fromDate) > new Date(toDate)) {
            const errorMessage = $('#date_from').data('error-message') || "from_date_cannot_be_greater_than_to_date";
            toastr.error(errorMessage);
            $('#date_from').val('');
        }
    });

    $('.btn--reset').on('click', function (e) {
        e.preventDefault();
        const form = $(this).closest('form')[0];
        form.reset();

        $('.id_wise, .date_wise').hide();
        $('#type').val('all');
        $('#type').trigger('change');
    });
});
