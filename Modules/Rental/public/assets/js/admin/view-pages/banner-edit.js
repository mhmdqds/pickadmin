
    "use strict";
    $(document).ready(function () {
        $('.single_file_input').on('change', function (event) {
            var file = event.target.files[0];
            var $card = $(event.target).closest('.upload-file');
            var $textbox = $card.find('.upload-file-textbox');
            var $imgElement = $card.find('.upload-file-img');
            var $removeBtn = $card.find('.remove-btn');

            if (file) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    $textbox.hide();
                    $imgElement.attr('src', e.target.result).show();
                    $removeBtn.css('opacity', 1);
                };
                reader.readAsDataURL(file);
            }
        });

        $('.upload-file').each(function () {
            var $card = $(this);
            var $textbox = $card.find('.upload-file-textbox');
            var $imgElement = $card.find('.upload-file-img');
            var $removeBtn = $card.find('.remove-btn');
            if ($imgElement.attr('src') && $imgElement.attr('src') !== window.location.href) {
                $textbox.hide();
                $imgElement.show();
            }
        });
    });


    $("#banner_type").on("change", function () {
        let order_type = $(this).val();
        banner_type_change(order_type);
    }).trigger('change');
    function banner_type_change(order_type) {
        if (order_type == "item_wise") {
            $("#store_wise").hide();
            $("#item_wise").show();
            $("#default").hide();
        } else if (order_type == "store_wise") {
            $("#store_wise").removeClass("d-none").show();
            $("#item_wise").hide();
            $("#default").hide();
        } else if (order_type == "default") {
            $("#default").removeClass("d-none").show();
            $("#store_wise").hide();
            $("#item_wise").hide();
        } else {
            $("#item_wise").hide();
            $("#store_wise").hide();
            $("#default").hide();
        }
    }


    $(document).ready(function () {
        $('.remove-btn').click(function () {
            var $card = $(this).closest('.upload-file');
            $card.find('.single_file_input').val('');
            $card.find('.upload-file-img').attr('src', $('#defaut_image_url').val());
            $(this).css('opacity', 0);
        });
        $('#reset_btn').click(function () {
            banner_type_change($('#defaut_banner_type').val())
            if($('#default_store_id').val()){
                $('#store_id').val($('#default_store_id').val()).trigger('change')
            }
            $('#banner_type').val($('#defaut_banner_type').val()).trigger('change');
            var $cards = $('.upload-file');
            $cards.each(function () {
                $(this).find('.single_file_input').val('');
                $(this).find('.upload-file-img').attr('src', $('#defaut_image_url').val());
                $(this).find('.remove-btn').css('opacity', 0);
            });
        });
    });
    $(document).on('ready', function() {

        banner_type_change($('#defaut_banner_type').val());
        var  module_id = $('#current_module_id').val();
        var url = $('#store_id').attr('data-url');

        $('.js-data-example-ajax').select2({
            ajax: {
                url: url,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page,
                        module_id: module_id
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    var $request = $.ajax(params);
                    $request.then(success);
                    $request.fail(failure);
                    return $request;
                }
            }
        });
    });
