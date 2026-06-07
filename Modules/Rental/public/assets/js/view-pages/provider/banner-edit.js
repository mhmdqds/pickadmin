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
                        $textbox.addClass('d-none');
                        $imgElement.attr('src', e.target.result).removeClass('d-none');
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
                    $textbox.addClass('d-none');
                    $imgElement.removeClass('d-none');
                }
            });

           $('.remove-btn').click(function () {
                var $card = $(this).closest('.upload-file');
                var defaultImage = $card.find('.upload-file-img').data('bannerImage');
                console.log(defaultImage);

                $card.find('.single_file_input').val('');
                $card.find('.upload-file-img').attr('src', defaultImage);
                $(this).css('opacity', 0);
            });

            $('#reset_btn').click(function () {
                var $cards = $('.upload-file');
                $cards.each(function () {
                    var defaultImage = $(this).find('.upload-file-img').data('bannerImage');
                    $(this).find('.single_file_input').val('');
                    $(this).find('.upload-file-img').attr('src', defaultImage);
                    $(this).find('.remove-btn').css('opacity', 0);
                });
            });
        });
