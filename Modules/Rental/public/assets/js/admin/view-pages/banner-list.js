"use strict";
$(document).ready(function () {
    $('.single_file_input').on('change', function (event) {
        let file = event.target.files[0];
        let $card = $(event.target).closest('.upload-file');
        let $textbox = $card.find('.upload-file-textbox');
        let $imgElement = $card.find('.upload-file-img');
        let $removeBtn = $card.find('.remove-btn');
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $textbox.hide();
                $imgElement.attr('src', e.target.result).removeClass('d-none');
                $removeBtn.css('opacity', 1);
            };
            reader.readAsDataURL(file);
            console.log("file--", file);
        }
        else {
            $textbox.show();
            $imgElement.addClass('d-none').attr('src', '');
            $removeBtn.css('opacity', 0);
        }
    });
    $('.remove-btn').click(function () {
        let $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-textbox').show();
        $card.find('.upload-file-img').addClass('d-none').attr('src', '');
        $(this).css('opacity', 0);
    });
    $('#reset_btn').click(function () {
        $('#banner_type').trigger('change');
        $('#store_id').val(null).trigger('change');
        let $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-textbox').show();
            $(this).find('.upload-file-img').addClass('d-none').attr('src', '');
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});

    $(document).on('ready', function() {
        var  module_id = $('#current_module_id').val();
        var url = $('#store_id').attr('data-url');
        console.log(url);
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
