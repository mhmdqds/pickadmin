"use strict";
$(document).ready(function () {
    const MAX_FILE_SIZE_MB = 1;
    const MAX_FILES = 5;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const imageContainer = document.getElementById("image_container");
    const uploadWrapper = document.getElementById("image_upload_wrapper");
    const inputElement = document.querySelector('.multiple_image_input');
    const fileSet = new Set();
    let removedImages = [];

     inputElement.addEventListener('change', function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error($('#max_file_upload_limit_error_text').val() + MAX_FILES, {
                CloseButton: true,
                ProgressBar: true
            });
            return;
        }
        files.forEach(file => {
            if (!ALLOWED_FILE_TYPES.includes(file.type)) {
                toastr.error($('#file_type_error_text').val(), {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
                toastr.error($('#file_size_error_text').val(), {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            if (!fileSet.has(file.name)) {
                fileSet.add(file.name);

                const fileURL = URL.createObjectURL(file);
                const imageSingle = document.createElement("div");
                imageSingle.className = "image-single h-100 max-w-200px p-0";
                imageSingle.innerHTML = `
                     <a href="javascript:void(0);;" class="remove-btn" data-file-name="${file.name}">
                        <i class="tio-clear"></i>
                    </a>
                    <img class="img--vertical-2 rounded-10" width="200" height="100" loading="lazy" src="${fileURL}" alt="">
                `;
                imageContainer.appendChild(imageSingle);
            }
        });

        toggleUploadWrapper();
    });

     document.addEventListener("click", function (event) {
    const button = event.target.closest(".remove-btn");
    if (button) {
        event.stopPropagation();
        const imageSingle = button.closest(".image-single");
        if (imageSingle) {
            const fileName = button.dataset.fileName;
            imageSingle.remove();
            fileSet.delete(fileName);
            removedImages.push(fileName);
            toggleUploadWrapper();
        }
    }
});


    function toggleUploadWrapper() {
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;
        uploadWrapper.style.display = currentFiles >= 5 ? "none" : "block";
    }

     $('#reset_btn').click(function () {
         const uploadedImages = imageContainer.querySelectorAll(".image-single:not([data-existing='true'])");
        uploadedImages.forEach(image => image.remove());
         fileSet.clear();
         uploadWrapper.style.display = "block";
    });
});

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
        $card.find('.upload-file-img').attr('src', $card.find('.upload-file-img').data('file-name')  );
        $(this).css('opacity', 0);
    });

     $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-img').attr('src',  $(this).find('.upload-file-img').data('file-name') );
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});
