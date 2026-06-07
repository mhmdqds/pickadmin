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

    $('.remove-btn').click(function () {
        var $card = $(this).closest('.upload-file');
        $card.find('.single_file_input').val('');
        $card.find('.upload-file-img').attr('src',$card.find('.upload-file-img').attr('data-src') );
        $(this).css('opacity', 0);
    });

    $('#reset_btn').click(function () {
        var $cards = $('.upload-file');
        $cards.each(function () {
            $(this).find('.single_file_input').val('');
            $(this).find('.upload-file-img').attr('src',$(this).find('.upload-file-img').attr('data-src') );
            $(this).find('.remove-btn').css('opacity', 0);
        });
    });
});

$(document).ready(function () {
    function getApplicablePrice() {
        let hourlyChecked = $('input[name="trip_hourly"]').is(':checked');
        let distanceChecked = $('input[name="trip_distance"]').is(':checked');
        let hourlyPrice = parseFloat($('input[name="hourly_price"]').val()) || 0;
        let distancePrice = parseFloat($('input[name="distance_price"]').val()) || 0;

        if (hourlyChecked && distanceChecked) {
            return Math.min(hourlyPrice, distancePrice);
        } else if (hourlyChecked) {
            return hourlyPrice;
        } else if (distanceChecked) {
            return distancePrice;
        }
        return 0;
    }

    $('#discount_input').on('input', function () {
        let discountType = $('#discount_type').val();
        let inputValue = parseFloat($(this).val());
        let applicablePrice = getApplicablePrice();

        if (discountType === 'percent' && inputValue >= 100) {
            $(this).val(99);
        } else if (discountType === 'amount' && inputValue > applicablePrice) {
            $(this).val(applicablePrice);
        }
    });

    $('input[name="trip_hourly"], input[name="trip_distance"], input[name="hourly_price"], input[name="distance_price"]').on('change input', function () {
        $('#discount_input').trigger('input');
    });
});


document.addEventListener("DOMContentLoaded", function () {
    const MAX_FILE_SIZE_MB = 1;
    const MAX_FILES = 5;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const imageContainer = document.getElementById("image_container");
    const imageUploadWrapper = document.getElementById("image_upload_wrapper");
    const inputElement = document.querySelector(".multiple_image_input");
    const fileSet = new Set();
    let removedImages = [];
    let removedDocuments = [];

    inputElement.addEventListener("change", function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error("You can upload a maximum of " + MAX_FILES + " files.", {
                CloseButton: true,
                ProgressBar: true
            });
            imageUploadWrapper.style.display = "none";
            event.target.value = "";
            return;
        }

        files.forEach(file => {
            if (!ALLOWED_FILE_TYPES.includes(file.type)) {
                toastr.error("Please only input PNG or JPG type file.", {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            if (file.size > MAX_FILE_SIZE_MB * 1024 * 1024) {
                toastr.error("File size too big.", {
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

    imageContainer.addEventListener("click", function (event) {
        if (event.target.closest(".remove-btn")) {
            const removeBtn = event.target.closest(".remove-btn");
            const fileName = removeBtn.getAttribute("data-file-name");
            removeImage(fileName, removeBtn);
        }
    });

    function removeImage(fileName, element) {
        const imageName = fileName.split("/").pop();
        removedImages.push(imageName);
        document.getElementById("removed_images").value = JSON.stringify(removedImages);
        element.closest(".image-single").remove();
        fileSet.delete(fileName);
        toggleUploadWrapper();
    }

    function toggleUploadWrapper() {
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;
        imageUploadWrapper.style.display = currentFiles >= MAX_FILES ? "none" : "block";
    }

    document.querySelector("form").addEventListener("submit", function (e) {
        e.preventDefault();
        const formData = new FormData(this);

        fileSet.forEach((fileName) => {
            const fileInput = document.querySelector(`input[name="images[]"][data-file-name="${fileName}"]`);
            if (fileInput) {
                formData.append("images[]", fileInput.files[0]);
            }
        });

        removedImages.forEach((fileName) => {
            formData.append("removed_images[]", fileName);
        });

        removedDocuments.forEach((fileName) => {
            formData.append("removed_documents[]", fileName);
        });

        fetch(document.getElementById("vehicle_edit_url").dataset.url, {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                console.log("Files successfully uploaded and removed:", data);
            })
            .catch(error => {
                console.error("Error uploading files:", error);
            });
    });

    document.getElementById("reset_btn").addEventListener("click", function () {
        const uploadedImages = imageContainer.querySelectorAll(".image-single:not([data-existing='true'])");
        uploadedImages.forEach(image => image.remove());
        fileSet.clear();
        imageUploadWrapper.style.display = "block";
    });
});



$(document).ready(function () {
    const MAX_FILES = 5;
    const pdfContainer = document.getElementById("pdf-container");
    const documentUploadWrapper = document.getElementById("upload-wrapper");
    const uploadedFiles = new Map(); // Store files with unique names as keys

    // Handle file selection and upload
    document.querySelector('.multiple_document_input').addEventListener('change', function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = pdfContainer.querySelectorAll(".pdf-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error(`You can upload a maximum of ${MAX_FILES} files.`, {
                CloseButton: true,
                ProgressBar: true,
            });
            return;
        }

        files.forEach((file) => {
            if (!uploadedFiles.has(file.name)) {
                uploadedFiles.set(file.name, file); // Store the file with its name as the key

                const fileURL = URL.createObjectURL(file);
                const fileName = file.name;
                const fileType = file.type;

                const pdfSingle = document.createElement("div");
                pdfSingle.className = "pdf-single";
                pdfSingle.setAttribute("data-file-name", fileName);
                pdfSingle.setAttribute("data-pdf-url", fileURL);

                const iconSrc = fileType.startsWith("image/") ?
                    document.getElementById("default_image").getAttribute("data-url") :
                    document.getElementById("default_document").getAttribute("data-url");

                const defaultBlank = document.getElementById("default_blank").getAttribute("data-url");
                pdfSingle.innerHTML = `
                    <div class="pdf-frame">
                        <canvas class="pdf-preview display-none"></canvas>
                        <img class="pdf-thumbnail" src="${defaultBlank}" alt="File Thumbnail">
                    </div>
                    <div class="overlay">
                        <a href="javascript:void(0);" class="remove-btn" data-file-name="${file.name}">
                            <i class="tio-clear"></i>
                        </a>
                        <div class="pdf-info d-flex gap-10px align-items-center">
                            <img src="${iconSrc}" width="34" alt="File Type Logo">
                            <div class="fs-13 text--title d-flex flex-column">
                                <span class="file-name">${fileName}</span>
                                <span class="opacity-50">Click to view the file</span>
                            </div>
                        </div>
                    </div>
                `;

                pdfContainer.appendChild(pdfSingle);
                renderFileThumbnail(pdfSingle, fileType);

                toastr.success("File added successfully.", {
                    CloseButton: true,
                    ProgressBar: true,
                });
            }
        });
        toggleUploadWrapper();
    });

    window.removedDocuments = [];

    pdfContainer.addEventListener("click", function (event) {
        if (event.target.closest(".remove-btn")) {
            const removeBtn = event.target.closest(".remove-btn");
            const fileName = removeBtn.getAttribute("data-file-name");
            removeDocument(fileName, removeBtn);
        } else if (event.target.closest(".pdf-single")) {
            const pdfSingle = event.target.closest(".pdf-single");
            const fileUrl = pdfSingle.getAttribute("data-pdf-url");
            window.open(fileUrl, "_blank");
        }
    });

    function removeDocument(fileName, element) {
        const documentName = fileName.split("/").pop();
        removedDocuments.push(documentName);
        document.getElementById("removed_documents").value = JSON.stringify(removedDocuments);
        element.closest(".pdf-single").remove();
        uploadedFiles.delete(fileName);
        toggleUploadWrapper();
    }

    function toggleUploadWrapper() {
        const currentFiles = pdfContainer.querySelectorAll(".pdf-single").length;
        documentUploadWrapper.style.display = currentFiles >= MAX_FILES ? "none" : "block";
    }

    async function renderFileThumbnail(element, fileType) {
        const fileUrl = element.getAttribute("data-pdf-url");
        const canvas = element.querySelector(".pdf-preview");
        const thumbnail = element.querySelector(".pdf-thumbnail");

        if (fileType.startsWith("image/")) {
            thumbnail.src = fileUrl;
        } else if (fileType === "application/pdf") {
            try {
                const ctx = canvas.getContext("2d");
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({ scale: 0.5 });
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: ctx, viewport }).promise;
                thumbnail.src = canvas.toDataURL();
            } catch (error) {
                console.error("Error rendering PDF thumbnail:", error);
            }
        } else {
            thumbnail.src = document.getElementById("default_blank").getAttribute("data-url");
        }

        thumbnail.style.display = "block";
        canvas.style.display = "none";
    }

    $('form').on('submit', function (e) {
        const formData = new FormData(this);
        uploadedFiles.forEach((file, fileName) => {
            formData.append('documents[]', file, fileName);
        });
    });

    $('#reset_btn').click(function () {
        const uploadedDocuments = pdfContainer.querySelectorAll(".pdf-single:not([data-existing='true'])");
        uploadedDocuments.forEach((doc) => doc.remove());
        uploadedFiles.clear();
        documentUploadWrapper.style.display = "block";
    });
});


document.addEventListener("DOMContentLoaded", function() {

    async function renderFileThumbnail(element) {
        const fileUrl = element.getAttribute("data-pdf-url");
        const canvas = element.querySelector(".pdf-preview");
        const thumbnail = element.querySelector(".pdf-thumbnail");
        const fileNameSpan = element.querySelector(".file-name");

        const fullFileName = fileUrl.split('/').pop();
        const fileExtension = fullFileName.split('.').pop().toLowerCase();
        const fileNameWithoutExtension = fullFileName.replace(/\.[^/.]+$/, '');

        const truncatedFileName =
            fileNameWithoutExtension.length > 20 ?
                `${fileNameWithoutExtension.substring(0, 17)}...` :
                fileNameWithoutExtension;
        const displayedFileName = `${truncatedFileName}.${fileExtension}`;

        fileNameSpan.textContent = displayedFileName;

        if (fileExtension === "pdf") {
            const ctx = canvas.getContext("2d");

            try {
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({
                    scale: 0.5
                });
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({
                    canvasContext: ctx,
                    viewport
                }).promise;

                thumbnail.src = canvas.toDataURL();
            } catch (error) {
                console.error("Error rendering PDF thumbnail:", error);

                thumbnail.src = document.getElementById("default_blank").getAttribute("data-url");
            }
        } else if (["jpg", "jpeg", "png", "gif", "bmp", "webp"].includes(fileExtension)) {
            thumbnail.src = fileUrl;
        } else {
            const fileIconPath = `${document.getElementById("default_blank_icon").getAttribute("data-url")}/${fileExtension}.png`;
            const fallbackIconPath =
                document.getElementById("default_blank").getAttribute("data-url");
            const iconExists = await checkFileIconExistence(fileIconPath);
            thumbnail.src = iconExists ? fileIconPath : fallbackIconPath;
        }


        thumbnail.style.display = "block";
        canvas.style.display = "none";
    }

    async function checkFileIconExistence(iconPath) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => resolve(true);
            img.onerror = () => resolve(false);
            img.src = iconPath;
        });
    }

    document.querySelectorAll(".pdf-single").forEach(renderFileThumbnail);


    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".pdf-single").forEach((element) => {
            element.addEventListener("click", function () {
                const fileUrl = this.getAttribute("data-pdf-url");
                window.open(fileUrl, "_blank");
            });
        });
    });


});
$(document).ready(function () {
    toggleButton();

    $('input[name="multiple_vehicles"]').change(function () {
        toggleButton();
    });

    function toggleButton() {
        if ($('input[name="multiple_vehicles"]').is(':checked')) {
            $('.add-btn').show();
            $('.multiple-vehicles').removeClass('d-none').addClass('d-flex');
        } else {
            $('.multiple-vehicles').addClass('d-none').removeClass('d-flex');
            $('.add-btn').hide();
            $('.new-added').not('#input-container').remove();
        }
    }

    $(document).on('click', '.add-btn', function () {

        var vin = $(this).data('vin');
        var license = $(this).data('license');

        let newDiv = $('<div class="d-flex gap-20px flex-column flex-md-row equal-width new-added">\
            <div class="form-group mb-0">\
                <label class="input-label" for="">'+vin+'</label>\
                <input type="text" name="vehicle[vin_number][]" class="form-control" placeholder="Type your VIN number" value="">\
            </div>\
            <div class="form-group mb-0">\
                <label class="input-label" for="">'+license+'</label>\
                <input type="text" name="vehicle[license_plate_number][]" class="form-control" placeholder="Type your license plate number" value="">\
            </div>\
            <button type="button" class="btn remove-btn shadow-none text--danger p-0 fs-32 lh--1 text-left mt-md-4">\
                <i class="tio-clear-circle-outlined"></i>\
            </button>\
        </div>');

        newDiv.insertBefore('.equal-width:last');
    });

    $(document).on('click', '.remove-btn', function () {
        $(this).closest('.equal-width').remove();
    });
});


$(document).ready(function () {
    const $tripHourly = $('input[name="trip_hourly"]');
    const $tripDistance = $('input[name="trip_distance"]');
    const $hourlyPrice = $('input[name="hourly_price"]');
    const $distancePrice = $('input[name="distance_price"]');

    function updateInputs() {
        if (!$tripHourly.is(':checked')) {
            $hourlyPrice.prop('disabled', true).val('');
        } else {
            $hourlyPrice.prop('disabled', false);
        }

        if (!$tripDistance.is(':checked')) {
            $distancePrice.prop('disabled', true).val('');
        } else {
            $distancePrice.prop('disabled', false);
        }

        if (!$tripHourly.is(':checked') && !$tripDistance.is(':checked')) {
            $tripHourly.prop('checked', true);
            $hourlyPrice.prop('disabled', false);
        }
    }

    $tripHourly.change(updateInputs);
    $tripDistance.change(updateInputs);

    updateInputs();
});

$(document).ready(function() {
    $('#pickup_zones12').select2({
        placeholder: "Type and press Enter",
        tags: true,
        tokenSeparators: [',', ' ', ';'],
        createTag: function(params) {
            return {
                id: params.term,
                text: params.term
            };
        },
        insertTag: function (data, tag) {
            data.push(tag);
        }
    });
});
