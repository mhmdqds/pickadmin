"use strict";
$(document).on('ready', function () {
    let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));
});

$(".status_form_alert").on("click", function (e) {
    const id = $(this).data('id');
    const message = $(this).data('message');
    const title = $(this).data('title');
    e.preventDefault();
    Swal.fire({
        title: title,
        text: message,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: $(this).data('no-text'),
        confirmButtonText: $(this).data('yes-text'),
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $('#' + id).submit()
        }
    })
})
