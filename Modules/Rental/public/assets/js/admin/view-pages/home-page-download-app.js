
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
            $textbox.hide();
            $imgElement.removeClass('d-none');
        }
    });

   $('.remove-btn').click(function () {
        var $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-img').attr('src', $card.find('.upload-file-img').attr('data-src'));
        $(this).css('opacity', 0);
    });

    $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-img').attr('src', $(this).find('.upload-file-img').attr('data-src'));
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});

document.addEventListener("DOMContentLoaded", function () {
    const fields = document.querySelectorAll('.character-count-field');
    fields.forEach((field) => {
        const textCount = field.closest('.character-count').querySelector('.text-count');
        const maxLength = field.getAttribute('maxlength');
        updateCount(field, textCount, maxLength);

        field.addEventListener('input', function () {
            updateCount(field, textCount, maxLength);
        });
    });
    function updateCount(field, textCount, maxLength) {
        const currentLength = field.value.length;
        textCount.textContent = `${currentLength} / ${maxLength}`;
    }
});

