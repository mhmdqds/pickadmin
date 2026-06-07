"use strict";
$(document).on('click', '.restaurant-open-status', function (event) {
    const route = $(this).data('route');
    const title = $(this).data('title');
    const text = $(this).data('text');
    const no = $(this).data('no');
    const yes = $(this).data('yes');
    event.preventDefault();
    Swal.fire({
        title: title,
        text: text,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#00868F',
        cancelButtonText: no,
        confirmButtonText: yes,
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
            $.get({
                url: route,
                contentType: false,
                processData: false,
                beforeSend: function () {
                    $('#loading').show();
                },
                success: function (data) {
                    toastr.success(data.message);
                },
                complete: function () {
                    $('#loading').hide();
                    location.reload();
                },
            });
        } else {
            event.checked = !event.checked;
        }
    })

});

$(document).on('click', '.delete-schedule', function () {
    let route =  $(this).data('url');
    let title =  $('#button-title').data('title');
    let text =  $('#button-text').data('text');
    let no =  $('#button-cancel').data('no');
    let yes =  $('#button-accept').data('yes');
    let success =  $('#button-success').data('success');
    let error =  $('#button-error').data('error');
    Swal.fire({
        title: title,
        text: text,
        type: 'warning',
        showCancelButton: true,
        cancelButtonColor: 'default',
        confirmButtonColor: '#00868F',
        cancelButtonText: no,
        confirmButtonText: yes,
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
                        toastr.success(success, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                },
                error: function() {
                    toastr.error(error, {
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

function readURL(input) {
    if (input.files && input.files[0]) {
        let reader = new FileReader();

        reader.onload = function (e) {
            $('#viewer').attr('src', e.target.result);
        }

        reader.readAsDataURL(input.files[0]);
    }
}
$("#customFileEg1").change(function () {
    readURL(this);
});

$(document).on('ready', function () {
    $("#gst_status").on('change', function(){
        if($("#gst_status").is(':checked')){
            $('#gst').removeAttr('readonly');
        } else {
            $('#gst').attr('readonly', true);
        }
    });
});

$('#exampleModal').on('show.bs.modal', function (event) {
    let button = $(event.relatedTarget);
    let day_name = button.data('day');
    let day_id = button.data('dayid');
    let modal = $(this);
    let title = $(this).data('title');
    modal.find('.modal-title').text(title + day_name);
    modal.find('.modal-body input[name=day]').val(day_id);
})

$('#add-schedule').on('submit', function (e) {
    let route = $(this).data('route');
    let added = $('#button-added').data('added');
    e.preventDefault();
    let formData = new FormData(this);
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
                toastr.success(added, {
                    CloseButton: true,
                    ProgressBar: true
                });
            }
        },
        error: function(XMLHttpRequest) {
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
