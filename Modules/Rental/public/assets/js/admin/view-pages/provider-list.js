'use strict';
$('.status_change_alert').on('click', function (event) {
    let url = $(this).data('url');
    let message = $(this).data('message');
    status_change_alert(url, message, event)
})

function status_change_alert(url, message, e) {
    e.preventDefault();
    Swal.fire({
        title: $('#data-set').data('translate-are-you-sure'),
        text: message,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: $('#data-set').data('translate-no'),
        confirmButtonText: $('#data-set').data('translate-yes'),
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            location.href=url;
        }
    })
}
$(document).on('ready', function () {

    let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

    $('#column1_search').on('keyup', function () {
        datatable
            .columns(1)
            .search(this.value)
            .draw();
    });

    $('#column2_search').on('keyup', function () {
        datatable
            .columns(2)
            .search(this.value)
            .draw();
    });

    $('#column3_search').on('keyup', function () {
        datatable
            .columns(3)
            .search(this.value)
            .draw();
    });

    $('#column4_search').on('keyup', function () {
        datatable
            .columns(4)
            .search(this.value)
            .draw();
    });

    $('.js-select2-custom').each(function () {
        let select2 = $.HSCore.components.HSSelect2.init($(this));
    });
});
