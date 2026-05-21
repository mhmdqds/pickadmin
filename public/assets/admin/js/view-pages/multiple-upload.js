"use strict";
$(document).ready(function () {
    const MAX_FILE_SIZE_MB = 1;
    const MAX_FILES = 5;
    const ALLOWED_FILE_TYPES = ["image/jpeg", "image/jpg", "image/png", "image/webp"];
    const imageContainer = document.getElementById("image_container");
    const uploadWrapper = document.getElementById("image_upload_wrapper");
    const inputElement = document.querySelector('.multiple_image_input');
    const fileSet = new Set();
    inputElement.addEventListener('change', function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = imageContainer.querySelectorAll(".image-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error($('#max_file_upload_limit_error_text').val() + MAX_FILES , {
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
        uploadWrapper.style.display = currentFiles >= 5 ? "none" : "block";
    }

$('#reset_btn').click(function () {
        const uploadedImages = imageContainer.querySelectorAll(".image-single");
        uploadedImages.forEach(image => image.remove());

        fileSet.clear();

        uploadWrapper.style.display = "block";
    });

});


$(document).ready(function () {
    const MAX_FILES = 5;
    const pdfContainer = $("#pdf-container");
    const documentUploadWrapper = $("#upload-wrapper");
    const uploadedFiles = new Map();

    // Fetch asset URLs from data attributes
    const fileAssets = $("#file-assets");
    const pictureIcon = fileAssets.data("picture-icon");
    const documentIcon = fileAssets.data("document-icon");
    const blankThumbnail = fileAssets.data("blank-thumbnail");

    $(".multiple_document_input").on("change", function (event) {
        const files = Array.from(event.target.files);
        const currentFiles = pdfContainer.find(".pdf-single").length;

        if (currentFiles + files.length > MAX_FILES) {
            toastr.error(`You can upload a maximum of ${MAX_FILES} files.`, {
                CloseButton: true,
                ProgressBar: true,
            });
            return;
        }

        files.forEach((file) => {
            if (!uploadedFiles.has(file.name)) {
                uploadedFiles.set(file.name, file);

                const fileURL = URL.createObjectURL(file);
                const fileName = file.name;
                const fileType = file.type;
                const iconSrc = fileType.startsWith("image/") ? pictureIcon : documentIcon;

                const pdfSingle = $(`
                    <div class="pdf-single" data-file-name="${fileName}" data-file-url="${fileURL}">
                        <div class="pdf-frame">
                            <canvas class="pdf-preview display-none"></canvas>
                            <img class="pdf-thumbnail" src="${blankThumbnail}" alt="File Thumbnail">
                        </div>
                        <div class="overlay">
                            <a href="javascript:void(0);" class="remove-btn">
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
                    </div>
                `);

                pdfContainer.append(pdfSingle);
                renderFileThumbnail(pdfSingle, fileType);

                toastr.success("File added successfully.", {
                    CloseButton: true,
                    ProgressBar: true,
                });
            }
        });

        toggleUploadWrapper();
    });

    // Remove file on clicking remove button
    pdfContainer.on("click", ".remove-btn", function (event) {
        event.stopPropagation();
        const pdfSingle = $(this).closest(".pdf-single");
        const fileName = pdfSingle.data("file-name");

        uploadedFiles.delete(fileName);
        pdfSingle.remove();
        toggleUploadWrapper();
    });

    // Open file on click
    pdfContainer.on("click", ".pdf-single", function () {
        const fileURL = $(this).data("file-url");
        window.open(fileURL, "_blank");
    });

    function toggleUploadWrapper() {
        const currentFiles = pdfContainer.find(".pdf-single").length;
        documentUploadWrapper.toggle(currentFiles < MAX_FILES);
    }

    async function renderFileThumbnail(element, fileType) {
        const fileUrl = element.data("file-url");
        const canvas = element.find(".pdf-preview")[0];
        const thumbnail = element.find(".pdf-thumbnail")[0];

        try {
            if (fileType.startsWith("image/")) {
                thumbnail.src = fileUrl;
            } else if (fileType === "application/pdf") {
                const ctx = canvas.getContext("2d");
                const loadingTask = pdfjsLib.getDocument(fileUrl);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);

                const viewport = page.getViewport({ scale: 0.5 });
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                await page.render({ canvasContext: ctx, viewport }).promise;
                thumbnail.src = canvas.toDataURL();
            } else {
                thumbnail.src = blankThumbnail;
            }

            $(thumbnail).show();
            $(canvas).hide();
        } catch (error) {
            console.error("Error rendering file thumbnail:", error);
        }
    }

    $("form").on("submit", function (e) {
        const formData = new FormData(this);
        uploadedFiles.forEach((file, fileName) => {
            formData.append("documents[]", file, fileName);
        });

        console.log("Files submitted:", Array.from(uploadedFiles.keys()));
    });

    $("#reset_btn").click(function () {
        $(".pdf-single").remove();
        uploadedFiles.clear();
        documentUploadWrapper.show();
    });
});

