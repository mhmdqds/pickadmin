"use strict";
$(document).ready(function () {
    let datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

    $(".status_form_alert").on("click", function (e) {
        const id = $(this).data('id');
        const message = $(this).data('message');
        const alert = $(this).data('alert');
        const no = $(this).data('no');
        const yes = $(this).data('yes');
        e.preventDefault();
        Swal.fire({
            title: alert,
            text: message,
            type: 'warning',
            showCancelButton: true,
            cancelButtonColor: 'default',
            confirmButtonColor: '#FC6A57',
            cancelButtonText: no,
            confirmButtonText: yes,
            reverseButtons: true
        }).then((result) => {
            if (result.value) {
                $('#' + id).submit()
            }
        })
    });
});
