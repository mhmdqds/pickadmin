"use strict";
$(document).ready(function () {
    $('#dataTable').DataTable();

    $('#exampleModal').on('show.bs.modal', function (event) {
        let button = $(event.relatedTarget);
        let day_name = button.data('day');
        let day_id = button.data('dayid');
        let modal = $(this);
        let message = $(this).data('message');
        modal.find('.modal-title').text(message + day_name);
        modal.find('.modal-body input[name=day]').val(day_id);
    })

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

    $('#column3_search').on('change', function () {
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


$(document).on('click', '.delete-schedule', function () {
    let route = $(this).data('url');
    Swal.fire({
        title: $('#title').data('title'),
        text: $('#subTitle').data('sub-title'),
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#00868F',
        cancelButtonText: $('#buttonNo').data('no'),
        confirmButtonText: $('#buttonYes').data('yes'),
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.get({
                url: route,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    if (data.errors) {
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        $('#schedule').empty().html(data.view);
                        toastr.success($('#removed').data('removed'), {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    toastr.error($('#notFound').data('not-found'), {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                complete: function () {
                    $('#loading').hide();
                },
            });
        }
    })
});

$('#add-schedule').on('submit', function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    let route = $(this).data('route');
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $.post({
        url: route,
        data: formData,
        cache: false,
        contentType: false,
        processData: false,
        beforeSend: function () {
            $('#loading').show();
        },
        success: function (data) {
            if (data.errors) {
                for (let i = 0; i < data.errors.length; i++) {
                    toastr.error(data.errors[i].message, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            } else {
                $('#schedule').empty().html(data.view);
                $('#exampleModal').modal('hide');
                toastr.success($('#added').data('added'), {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            toastr.error(XMLHttpRequest.responseText, {
                CloseButton: true,
                ProgressBar: true
            });
        },
        complete: function () {
            $('#loading').hide();
        },
    });
});
