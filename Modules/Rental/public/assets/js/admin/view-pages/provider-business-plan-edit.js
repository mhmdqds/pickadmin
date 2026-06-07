'use strict';
$(document).on('ready', function() {
    var owl = $('.plan-slider');

    owl.owlCarousel({
        loop: false,
        margin: 30,
        responsiveClass: true,
        nav: false,
        dots: false,
        items: 3,
        center: true,
        startPosition: '{{ $index }}',

        responsive: {
            0: {
                items: 1.1,
                margin: 10,
            },
            375: {
                items: 1.3,
                margin: 30,
            },
            576: {
                items: 1.7,
            },
            768: {
                items: 2.2,
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
    function centerActiveSlide() {
        $(".owl-item").each(function () {
        if ($(this).find("label").hasClass("active")) {
            var index = $(this).index();
            owl.trigger("to.owl.carousel", [index, 300, true]);
        }
        });
    }

    centerActiveSlide();
});

$(document).ready(function () {
    $('input[name="business_plan"]:checked').each(function () {
        if ($(this).val() == 'subscription-base') {
            $('#subscription-plan').removeClass('d-none');
            $('#subscription-plan').addClass('d-block');
            $('#commissionBtn').hide();
            $('#subscriptionBtn').show();
        } else {
            $('#subscription-plan').addClass('d-none');
            $('#commissionBtn').show();
            $('#subscriptionBtn').hide();
        }
    });

    $('input[name="package_id"]:checked').each(function () {
        $(this).closest('.__plan-item').addClass('active');
    });

    $('input[name="business_plan"]').on('change', function () {
        if ($(this).val() == 'subscription-base') {
            $('#subscription-plan').removeClass('d-none');
            $('#subscription-plan').addClass('d-block');
            $('#commissionBtn').hide();
            $('#subscriptionBtn').show();
        } else {
            $('#subscription-plan').addClass('d-none');
            $('#subscription-plan').removeClass('d-block');
            $('#commissionBtn').show();
            $('#subscriptionBtn').hide();
        }
    });

    $('input[name="package_id"]').on('change', function () {
        $('input[name="package_id"]').each(function () {
            $(this).closest('.__plan-item').removeClass('active');
        });
        $(this).closest('.__plan-item').addClass('active');
    });
});

$('.shift_to_commission').on('click', function (event) {
    let url = $(this).data('url');
    let message = $(this).data('message');
    let storeBusinessModel = $('#data-set').data('store-business-model') == 'commission';

    if (storeBusinessModel) {
        $('#loading').hide();
        window.location.href = $('#data-set').data('rental-provider-url');
        return;
    }
    shift_to_commission(url, message, event);
});




function shift_to_commission(url, message, e) {
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
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: url,
                data: {
                    id: '{{ $store->id }}',
                },
                beforeSend: function () {
                    $('#loading').show()
                },
                success: function (data) {
                    toastr.success($('#data-set').data('translate-success'));
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
    var oldPackage = $(this).data('id');
    var activePackage = $('.__plan-item.active input[name="package_id"]');
    let packageViewUrl;
    if(oldPackage == activePackage.val()){
        $('#loading').hide();
        window.location.href = $('#data-set').data('rental-provider-url');
        return;
    }

    if (activePackage.length) {
        var packageId = activePackage.val();
         packageViewUrl = $('#data-set').data('subscription-package-view-url').replace('PLACEHOLDER_ID', packageId);
    }
    else{
        $('#loading').hide();
        toastr.warning($('#data-set').data('select-subscription-package'));
        return;
    }

    $.ajax({
        url: packageViewUrl,
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
