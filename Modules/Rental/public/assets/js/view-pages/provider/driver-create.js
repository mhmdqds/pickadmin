// ---- single image upload starts
$(document).ready(function () {
    const MAX_FILE_SIZE_MB = 1; // Maximum file size in MB
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];

    // Handle file input change
    $('.single_file_input').on('change', function (event) {
        var file = event.target.files[0];

        // Validate file type
        if (!ALLOWED_FILE_TYPES.includes(file.type)) {
            toastr.error($('#file-type-toast').val(), {
                CloseButton: true,
                ProgressBar: true
            });
            $(this).val(''); // Clear the input
            return;
        }

        // Validate file size
        if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
            toastr.error($('#file-size-toast').val(), {
                CloseButton: true,
                ProgressBar: true
            });
            $(this).val(''); // Clear the input
            return;
        }

        // Continue with existing file preview logic
        if (file) {
            var reader = new FileReader();
            reader.onload = function (e) {
                var $card = $(event.target).closest('.upload-file');
                $card.find('.upload-file-textbox').hide();
                $card.find('.upload-file-img').attr('src', e.target.result).removeClass('d-none');
                $card.find('.remove-btn').css('opacity', 1);
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle remove button click
    $('.remove-btn').click(function () {
        var $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-textbox').show();
        $card.find('.upload-file-img').addClass('d-none').attr('src', '');
        $(this).css('opacity', 0);
    });

    // Handle reset button click
    $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-textbox').show();
            $(this).find('.upload-file-img').addClass('d-none').attr('src', '');
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});
 // ---- single image upload ends

// ----- mutiple image upload
$(document).ready(function () {
    const MAX_FILE_SIZE_MB = 1; // Maximum file size in MB
    const MAX_FILES = 5;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const imageContainer = document.getElementById("image_container");
    const uploadWrapper = document.getElementById("image_upload_wrapper");
    const inputElement = document.querySelector('.multiple_image_input');
    const fileSet = new Set(); // To keep track of files

    inputElement.addEventListener('change', function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error($('#file-max-toast').val(), {
                CloseButton: true,
                ProgressBar: true
            });
            return;
        }
        files.forEach(file => {
            // Validate file type
            if (!ALLOWED_FILE_TYPES.includes(file.type)) {
                toastr.error($('#file-type-toast').val(), {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            // Validate file size
            if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
                toastr.error($('#file-size-toast').val(), {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            // Add to the file set and create preview
            if (!fileSet.has(file.name)) {
                fileSet.add(file.name);

                const fileURL = URL.createObjectURL(file);
                const imageSingle = document.createElement("div");
                imageSingle.className = "image-single h-100 max-w-200px p-0";
                imageSingle.innerHTML = `
                     <a href="javascript:void(0);" class="remove-btn" data-file-name="${file.name}">
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
                toggleUploadWrapper();
            }
        }
    });

    function toggleUploadWrapper() {
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;
        uploadWrapper.classList.toggle('d-none', currentFiles >= 5);
    }
   // Handle reset button click
   $('#reset_btn').click(function () {
        // Select and remove only the uploaded image elements
        const uploadedImages = imageContainer.querySelectorAll(".image-single");
        uploadedImages.forEach(image => image.remove());

        // Clear the file set
        fileSet.clear();

        // Ensure the upload wrapper is visible
        uploadWrapper.classList.remove('d-none');
    });

});
// ----- mutiple image upload ends
