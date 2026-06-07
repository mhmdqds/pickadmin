"use strict";

         // ---- single image upload starts
        $(document).ready(function () {
            // Handle file input change
            $('.single_file_input').on('change', function (event) {
                let file = event.target.files[0];
                let $card = $(event.target).closest('.upload-file');
                let $textbox = $card.find('.upload-file-textbox');
                let $imgElement = $card.find('.upload-file-img');
                let $removeBtn = $card.find('.remove-btn');

                if (file) {
                    let reader = new FileReader();
                    reader.onload = function (e) {
                        $textbox.addClass('d-none');
                        $imgElement.attr('src', e.target.result).removeClass('d-none');
                        $removeBtn.css('opacity', 1);
                    };
                    reader.readAsDataURL(file);

                }
                 else {
                    $textbox.removeClass('d-none');
                    $imgElement.addClass('d-none').attr('src', '');
                    $removeBtn.css('opacity', 0);
                }
            });

            // Handle remove button click
            $('.remove-btn').click(function () {
                let $card = $(this).closest('.upload-file');
                $card.find('.single_file_input').val('');
                $card.find('.upload-file-textbox').removeClass('d-none');
                $card.find('.upload-file-img').addClass('d-none').attr('src', '');
                $(this).css('opacity', 0);
            });

            // Handle reset button click
            $('#reset_btn').click(function () {
                $('#banner_type').trigger('change');
                $('#store_id').val(null).trigger('change');
                let $cards = $('.upload-file');
                $cards.each(function () {
                    $(this).find('.single_file_input').val('');
                    $(this).find('.upload-file-textbox').removeClass('d-none');
                    $(this).find('.upload-file-img').addClass('d-none').attr('src', '');
                    $(this).find('.remove-btn').css('opacity', 0);
                });
            });
        });
