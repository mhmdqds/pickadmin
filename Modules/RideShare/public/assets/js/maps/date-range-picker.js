$(function () {

    $('.date-range-picker').daterangepicker({
        ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        showCustomRangeLabel: true,
        startDate: $(this).data("startDate"),
        endDate: $(this).data("endDate"),
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear'
        },
        "alwaysShowCalendars": true,
    });

    $('.date-range-picker').attr('placeholder', "Select date");

    $('.date-range-picker').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });

    $('.date-range-picker').on('cancel.daterangepicker', function (ev, picker) {
        $(this).val('');
    });
});
