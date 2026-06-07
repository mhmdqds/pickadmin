
"use strict";
$('.plan-slider').owlCarousel({
    loop: false,
    margin: 30,
    responsiveClass:true,
    nav:false,
    dots:false,
    items: 3,
    center: true,
    startPosition: $('#index').data('index'),

    responsive:{
        0: {
            items:1.1,
            margin: 10,
        },
        375: {
            items:1.3,
            margin: 30,
        },
        576: {
            items:1.7,
        },
        768: {
            items:2.2,
            margin: 40,
        },
        992: {
            items: 3,
            margin: 40,
        },
        1200: {
            items: 4,
            margin: 40,
        }
    }
})

$('.status_change_alert').on('click', function (event) {
    let url = $(this).data('url');
    let message = $(this).data('message');
    status_change_alert(url, message, event)
})

function status_change_alert(url, message, e) {
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: url,
                data: {
                    id: $('#storeId').data('id'),
                    subscription_id: $('#storeSubId').data('sub-id'),
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    toastr.success($('#successfully').data('successfully'));
                },
                complete: function () {
                    $('#loading').hide();
                    location.reload();
                }
            });
        }
    })
}

$('.shift_to_commission').on('click', function (event) {
    let url = $(this).data('url');
    let message = $(this).data('message');
    shift_to_commission(url, message, event)
})

function shift_to_commission(url, message, e) {
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: url,
                data: {
                    id: $('#storeId').data('id'),
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    toastr.success($('#switched').data('switched'));
                },
                complete: function () {
                    $('#loading').hide();
                    location.reload();
                }
            });
        }
    })
}

$(document).on('click', '.package_detail', function () {
    var url = $(this).attr('data-url');
    $.ajax({
        url: url,
        method: 'get',
        beforeSend: function() {
            $('#loading').show();
            $('#plan-modal').modal('hide')
        },
        success: function(data){
            $('#data_package').html(data.view);
            if(data.disable_item_count !== null && data.disable_item_count > 0){
                $('#product_warning').modal('show')
                $('#disable_item_count').text(data.disable_item_count)
            } else{
                $('#subscription-renew-modal').modal('show')
            }
        },
        complete: function() {
            $('#loading').hide();
        },

    });
});
$(document).on('click', '#continue_btn', function () {
    $('#subscription-renew-modal').modal('show')
});

$(document).on('click', '#back_to_planes', function () {
    $('#plan-modal').modal('show')
});

$("#comission_status").on('change', function(){
    if($("#comission_status").is(':checked')){
        $('#comission').removeAttr('readonly');
    } else {
        $('#comission').attr('readonly', true);
        $('#comission').val('0');
    }
});
