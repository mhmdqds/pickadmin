// ----- mutiple image upload
$(document).ready(function () {
    const MAX_FILE_SIZE_MB = 1; // Maximum file size in MB
    const MAX_FILES = 5;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const imageContainer = document.getElementById("image_container");
    const uploadWrapper = document.getElementById("image_upload_wrapper");
    const inputElement = document.querySelector('.multiple_image_input');
    const fileSet = new Set(); // To keep track of files
    let removedImages = []; // To track removed images

    // Handle file input change (adding new files)
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
        uploadWrapper.classList.toggle('d-none', currentFiles >= 5);
    }

    // Handle reset button click
    $('#reset_btn').click(function () {
        // Select and remove only the new uploaded image elements (those without data-existing="true")
        const uploadedImages = imageContainer.querySelectorAll(".image-single:not([data-existing='true'])");
        uploadedImages.forEach(image => image.remove());

        // Clear the file set for new uploads
        fileSet.clear();

        // Ensure the upload wrapper is visible
        uploadWrapper.classList.remove('d-none');
    });
 // ----- mutiple image upload ends

// ---- single image upload starts

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

    // Check for a valid src on load to handle pre-existing images
    $('.upload-file').each(function () {
        var $card = $(this);
        var $textbox = $card.find('.upload-file-textbox');
        var $imgElement = $card.find('.upload-file-img');
        var $removeBtn = $card.find('.remove-btn');

        // If there's already a valid image source
        if ($imgElement.attr('src') && $imgElement.attr('src') !== window.location.href) {
            $textbox.hide();
            $imgElement.removeClass('d-none');
        }
    });

   // Handle remove button click
   $('.remove-btn').click(function () {
        var $card = $(this).closest('.upload-file');
        var defaultImage = $card.find('.upload-file-img').data('driverImage');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-img').attr('src', defaultImage);
        $(this).css('opacity', 0);
    });

    // Handle reset button click
    $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            var defaultImage = $(this).find('.upload-file-img').data('driverImage');
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-img').attr('src', defaultImage);
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});
// ---- single image upload ends
