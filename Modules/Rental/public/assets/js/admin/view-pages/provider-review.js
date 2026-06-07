"use strict";
// Call the dataTables jQuery plugin
$(document).ready(function () {
    $('#dataTable').DataTable();


    $('.js-select2-custom').each(function () {
        let select2 = $.HSCore.components.HSSelect2.init($(this));
    });
});

$(".status_form_alert").on("click", function (e) {
    const id = $(this).data('id');
    const message = $(this).data('message');
    e.preventDefault();
    Swal.fire({
        title: $('#title').data('title'),
        text: message,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#FC6A57',
        cancelButtonText: $('#buttonCancel').data('no'),
        confirmButtonText: $('#buttonApprove').data('yes'),
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $('#' + id).submit()
        }
    })
})
