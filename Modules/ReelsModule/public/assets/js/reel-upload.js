"use strict";

const previewBox = document.querySelector(".reel-preview-box");
const previewTitle = document.querySelector(".reel-preview-title");
const previewThumb = document.querySelector(".reel-preview-thumbnail");
const previewDes = document.querySelector(".reel-preview-des");
const playBtn = document.querySelector(".reel-preview-box .reels-play-btn");
const previewVideoEl = document.querySelector(".reels-video");

const thumbPlaceholder = document.querySelector(".thumbnail-placeholder");
const titlePlaceholder = document.querySelector(".title-placeholder");
const desPlaceholder = document.querySelector(".des-placeholder");

const storeSelect = document.querySelector(".store-select");

const defaultTitle = previewTitle ? previewTitle.dataset.reelTitle || "" : "";
const defaultThumb = previewThumb ? previewThumb.dataset.reelThumbnail || "" : "";

function hasPreviewVideo() {
    return Boolean(previewVideoEl && previewVideoEl.getAttribute("src"));
}

function togglePreviewPlayButton(visible) {
    if (!playBtn) {
        return;
    }

    playBtn.style.display = visible ? "flex" : "none";
}

function showTitle(text) {
    if (!previewTitle || !titlePlaceholder) {
        return;
    }

    previewTitle.textContent = text;
    previewTitle.style.display = text.trim() ? "block" : "none";
    titlePlaceholder.style.display = text.trim() ? "none" : "block";
}

function showDescription(text) {
    if (!previewDes || !desPlaceholder) {
        return;
    }

    previewDes.textContent = text;
    previewDes.style.display = text.trim() ? "block" : "none";
    desPlaceholder.style.display = text.trim() ? "none" : "block";
}

function showStoreLogo(src) {
    if (!previewThumb || !thumbPlaceholder) {
        return;
    }

    if (src) {
        previewThumb.style.backgroundImage = `url('${src}')`;
        previewThumb.style.display = "block";
        thumbPlaceholder.style.display = "none";
    } else {
        previewThumb.style.backgroundImage = "";
        previewThumb.style.display = "none";
        thumbPlaceholder.style.display = "block";
    }
}

function hasAnyData() {
    if (!previewBox || !previewVideoEl || !previewDes) {
        return false;
    }

    return (
        previewVideoEl.src ||
        previewDes.textContent.trim() ||
        previewBox.style.backgroundImage
    );
}

function applyVendorDefault() {
    if (!storeSelect && hasAnyData()) {
        showTitle(defaultTitle);
        showStoreLogo(defaultThumb);
    }
}

function clearVendorDefaultIfEmpty() {
    if (!storeSelect && !hasAnyData()) {
        showTitle("");
        showStoreLogo("");
    }
}

function updatePreviewBackground(src) {
    if (!previewBox) {
        return;
    }

    previewBox.style.backgroundImage = src ? `url('${src}')` : "";
}

function initStorePreview() {
    if (!storeSelect) {
        if (defaultThumb) {
            showStoreLogo(defaultThumb);
        }
        if (defaultTitle) {
            showTitle(defaultTitle);
        }
        return;
    }

    const selectedOption = storeSelect.options[storeSelect.selectedIndex];
    const text = selectedOption ? selectedOption.text.trim() : "";
    const value = selectedOption ? selectedOption.value : "";
    const logo = selectedOption
        ? selectedOption.getAttribute("data-logo") || defaultThumb
        : defaultThumb;

    if (value && text && text !== "Select Store") {
        showTitle(text);
        showStoreLogo(logo);
    } else if (defaultTitle || defaultThumb) {
        showTitle(defaultTitle);
        showStoreLogo(defaultThumb);
    } else {
        showTitle("");
        showStoreLogo("");
    }
}

function initDescriptionPreview() {
    const defaultDescriptionInput = document.querySelector("#reel_description_default");
    if (defaultDescriptionInput) {
        showDescription(defaultDescriptionInput.value || "");
        return;
    }

    const firstDescriptionInput = document.querySelector(".reel-des-textarea");
    if (firstDescriptionInput) {
        showDescription(firstDescriptionInput.value || "");
    }
}

function updatePreviewVideo(src) {
    if (!previewVideoEl || !previewBox) {
        return;
    }

    previewVideoEl.src = src;
    previewVideoEl.style.display = "none";
    previewVideoEl.pause();

    previewBox.classList.add("active");
    togglePreviewPlayButton(true);
    applyVendorDefault();
}

function clearPreviewVideo() {
    if (!previewVideoEl || !previewBox) {
        return;
    }

    previewVideoEl.pause();
    previewVideoEl.src = "";
    previewVideoEl.style.display = "none";
    previewBox.classList.remove("active");
    togglePreviewPlayButton(false);
    clearVendorDefaultIfEmpty();
}

function restoreUploadPlaceholder(box) {
    const uploadWrapper = box.querySelector(".upload-wrapper");
    const placeholder = box.querySelector(".upload-placeholder");

    if (uploadWrapper) {
        uploadWrapper.style.display = "none";

        const img = uploadWrapper.querySelector("img");
        if (img) {
            img.src = "";
        }
    }

    if (placeholder) {
        placeholder.style.display = "flex";
    }

    box.classList.remove("active");
}

function restoreOriginalReelUploader(box) {
    const uploadWrapper = box.querySelector(".upload-wrapper");
    const placeholder = box.querySelector(".upload-placeholder");
    const originalThumbnail = box.dataset.originalThumbnail || "";
    const originalVideo = box.dataset.originalVideo || "";
    const originalVideoName = box.dataset.originalVideoName || "";
    const originalVideoType = box.dataset.originalVideoType || "";
    const img = uploadWrapper ? uploadWrapper.querySelector("img") : null;
    const title = uploadWrapper ? uploadWrapper.querySelector(".reel-title") : null;
    const type = uploadWrapper ? uploadWrapper.querySelector(".reel-type") : null;

    if (box.dataset.type === "image") {
        if (originalThumbnail) {
            if (uploadWrapper) {
                uploadWrapper.style.display = "block";
            }

            if (placeholder) {
                placeholder.style.display = "none";
            }

            if (img) {
                img.src = originalThumbnail;
            }

            box.classList.add("active");
            updatePreviewBackground(originalThumbnail);
        } else {
            restoreUploadPlaceholder(box);
            updatePreviewBackground("");
        }

        togglePreviewPlayButton(true);
        applyVendorDefault();
        return;
    }

    if (box.dataset.type === "video") {
        if (originalVideo) {
            if (uploadWrapper) {
                uploadWrapper.style.display = "block";
            }

            if (placeholder) {
                placeholder.style.display = "none";
            }

            if (img) {
                img.src = originalThumbnail || "";
            }

            if (title) {
                title.textContent = originalVideoName;
            }

            if (type) {
                type.textContent = originalVideoType;
            }

            box.classList.add("active");
            updatePreviewBackground(originalThumbnail || "");
            updatePreviewVideo(originalVideo);
        } else {
            restoreUploadPlaceholder(box);
            updatePreviewBackground("");
            clearPreviewVideo();
            togglePreviewPlayButton(true);
        }

        clearVendorDefaultIfEmpty();
    }
}

function initReelUploader(container = document) {
    const boxes = container.querySelectorAll(".reel-upload-box");

    boxes.forEach((box) => {
        if (box.dataset.initialized) return;
        box.dataset.initialized = true;

        const input = box.querySelector("input");
        const uploadWrapper = box.querySelector(".upload-wrapper");
        const uploadAgainBtn = box.querySelector(".upload-again-btn");

        if (!input || !uploadWrapper) {
            return;
        }

        box.addEventListener("click", (e) => {
            if (e.target.closest(".upload-again-btn")) return;
            input.click();
        });

        input.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;

            const maxSize = parseFloat(box.dataset.maxSize);
            const maxDurationSeconds = parseFloat(box.dataset.maxDurationSeconds || "0");
            if (file.size > maxSize * 1024 * 1024) {
                if (typeof toastr !== "undefined") {
                    toastr.error(`Max size ${maxSize}MB exceeded`);
                } else {
                    alert(`Max size ${maxSize}MB exceeded`);
                }
                input.value = "";
                return;
            }

            box.classList.add("active");
            uploadWrapper.style.display = "block";

            if (box.dataset.type === "image") {
                const img = uploadWrapper.querySelector("img");
                const url = URL.createObjectURL(file);
                if (img) {
                    img.src = url;
                }

                updatePreviewBackground(url);
                togglePreviewPlayButton(hasPreviewVideo());
                applyVendorDefault();
            }

            if (box.dataset.type === "video") {
                const img = uploadWrapper.querySelector("img");
                const title = uploadWrapper.querySelector(".reel-title");
                const type = uploadWrapper.querySelector(".reel-type");
                const size = uploadWrapper.querySelector(".reel-size");

                const tempVideo = document.createElement("video");
                const tempVideoUrl = URL.createObjectURL(file);
                tempVideo.preload = "metadata";
                tempVideo.src = tempVideoUrl;

                tempVideo.addEventListener("loadedmetadata", () => {
                    if (maxDurationSeconds && tempVideo.duration > maxDurationSeconds) {
                        const message = `Video duration must not exceed ${box.dataset.maxDurationLabel}.`;
                        if (typeof toastr !== "undefined") {
                            toastr.error(message);
                        } else {
                            alert(message);
                        }

                        URL.revokeObjectURL(tempVideoUrl);
                        input.value = "";
                        resetReelUploader(box);
                        return;
                    }

                    tempVideo.currentTime = Math.min(1, Math.max(tempVideo.duration / 2, 0));
                });

                tempVideo.addEventListener("loadeddata", () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = tempVideo.videoWidth;
                    canvas.height = tempVideo.videoHeight;
                    canvas.getContext("2d").drawImage(tempVideo, 0, 0);
                    if (img) {
                        img.src = canvas.toDataURL("image/png");
                    }

                    URL.revokeObjectURL(tempVideoUrl);
                });

                if (title) {
                    title.textContent = file.name;
                }
                if (type) {
                    type.textContent = file.type.split("/")[1]?.toUpperCase() || "";
                }
                if (size) {
                    size.textContent = (file.size / (1024 * 1024)).toFixed(1) + " Mb";
                }

                updatePreviewVideo(URL.createObjectURL(file));
            }
        });

        if (uploadAgainBtn) {
            uploadAgainBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                input.click();
            });
        }
    });
}

function resetReelUploader(box) {
    const input = box.querySelector("input");

    if (!input) {
        return;
    }

    input.value = "";
    restoreOriginalReelUploader(box);

    if (box.dataset.type === "image") {
        initStorePreview();
    }
}

if (storeSelect) {
    $(document).ready(function () {
        $(".store-select").on("select2:select change", function () {
            const selectedOption = $(this).find("option:selected");
            const text = selectedOption.text();
            const logo = selectedOption.data("logo") || defaultThumb;

            if (text && text !== "Select Store") {
                showTitle(text);
                showStoreLogo(logo);
            } else {
                showTitle("");
                showStoreLogo("");
            }
        });
    });
}

document.querySelectorAll(".reel-des-textarea").forEach((el) => {
    el.addEventListener("input", function () {
        showDescription(this.value);
        applyVendorDefault();
        clearVendorDefaultIfEmpty();
    });
});

if (playBtn && previewVideoEl) {
    playBtn.addEventListener("click", function () {
        const src = previewVideoEl.getAttribute("src");

        if (src && previewVideoEl.readyState >= 2) {
            previewVideoEl.style.display = "block";
            previewVideoEl.play();
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    initReelUploader();
    initStorePreview();
    initDescriptionPreview();
    togglePreviewPlayButton(true);
});

const resetBtn = document.querySelector("#resetBtn");
if (resetBtn) {
    resetBtn.addEventListener("click", function () {
        setTimeout(() => {
            document.querySelectorAll(".reel-upload-box").forEach((box) => {
                resetReelUploader(box);
            });

            initStorePreview();
            initDescriptionPreview();
        }, 0);
    });
}
