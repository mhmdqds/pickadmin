"use strict";

const previewBox = document.querySelector('.reel-preview-box');
const previewTitle = document.querySelector('.reel-preview-title');
const previewThumb = document.querySelector('.reel-preview-thumbnail');
const previewDes = document.querySelector('.reel-preview-des');
const playBtn = document.querySelector('.reel-preview-box .reels-play-btn');
const previewVideoEl = document.querySelector('.reels-video');

const thumbPlaceholder = document.querySelector('.thumbnail-placeholder');
const titlePlaceholder = document.querySelector('.title-placeholder');
const desPlaceholder = document.querySelector('.des-placeholder');

const storeSelect = document.querySelector('.store-select');

const defaultTitle = previewTitle.dataset.reelTitle || '';
const defaultThumb = previewThumb.dataset.reelThumbnail || '';

function showTitle(text) {
    previewTitle.textContent = text;
    previewTitle.style.display = text.trim() ? 'block' : 'none';
    titlePlaceholder.style.display = text.trim() ? 'none' : 'block';
}

function showDescription(text) {
    previewDes.textContent = text;
    previewDes.style.display = text.trim() ? 'block' : 'none';
    desPlaceholder.style.display = text.trim() ? 'none' : 'block';
}

function showStoreLogo(src) {
    if (src) {
        previewThumb.style.backgroundImage = `url('${src}')`;
        previewThumb.style.display = 'block';
        thumbPlaceholder.style.display = 'none';
    } else {
        previewThumb.style.backgroundImage = '';
        previewThumb.style.display = 'none';
        thumbPlaceholder.style.display = 'block';
    }
}

function hasAnyData() {
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
        showTitle('');
        showStoreLogo('');
    }
}

function updatePreviewBackground(src) {
    previewBox.style.backgroundImage = src ? `url('${src}')` : '';
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
    const text = selectedOption ? selectedOption.text.trim() : '';
    const value = selectedOption ? selectedOption.value : '';
    const logo = selectedOption ? selectedOption.getAttribute('data-logo') || defaultThumb : defaultThumb;

    if (value && text && text !== 'Select Store') {
        showTitle(text);
        showStoreLogo(logo);
    } else if (defaultTitle || defaultThumb) {
        showTitle(defaultTitle);
        showStoreLogo(defaultThumb);
    } else {
        showTitle('');
        showStoreLogo('');
    }
}

function initDescriptionPreview() {
    const defaultDescriptionInput = document.querySelector('#reel_description_default');
    if (defaultDescriptionInput) {
        showDescription(defaultDescriptionInput.value || '');
        return;
    }

    const firstDescriptionInput = document.querySelector('.reel-des-textarea');
    if (firstDescriptionInput) {
        showDescription(firstDescriptionInput.value || '');
    }
}

function updatePreviewVideo(src) {
    previewVideoEl.src = src;
    previewVideoEl.style.display = 'none';
    previewVideoEl.pause();

    previewBox.classList.add('active');
    applyVendorDefault();
}

function clearPreviewVideo() {
    previewVideoEl.pause();
    previewVideoEl.src = '';
    previewVideoEl.style.display = 'none';
    previewBox.classList.remove('active');
    clearVendorDefaultIfEmpty();
}

function initReelUploader(container = document) {
    const boxes = container.querySelectorAll('.reel-upload-box');

    boxes.forEach(box => {
        if (box.dataset.initialized) return;
        box.dataset.initialized = true;

        const input = box.querySelector('input');
        const uploadWrapper = box.querySelector('.upload-wrapper');
        const removeBtn = box.querySelector('.remove-btn');

        box.addEventListener('click', (e) => {
            if (e.target.closest('.remove-btn')) return;
            input.click();
        });

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const maxSize = parseFloat(box.dataset.maxSize);
            if (file.size > maxSize * 1024 * 1024) {
                if (typeof toastr !== "undefined") {
                    toastr.error(`Max size ${maxSize}MB exceeded`);
                } else {
                    alert(`Max size ${maxSize}MB exceeded`);
                }
                input.value = '';
                return;
            }

            box.classList.add('active');
            uploadWrapper.style.display = 'block';

            if (box.dataset.type === 'image') {
                const img = uploadWrapper.querySelector('img');
                const url = URL.createObjectURL(file);
                img.src = url;

                updatePreviewBackground(url);
                applyVendorDefault();
            }

            if (box.dataset.type === 'video') {
                const img = uploadWrapper.querySelector('img');
                const title = uploadWrapper.querySelector('.reel-title');
                const type = uploadWrapper.querySelector('.reel-type');
                const size = uploadWrapper.querySelector('.reel-size');

                const tempVideo = document.createElement('video');
                tempVideo.src = URL.createObjectURL(file);
                tempVideo.currentTime = 1;

                tempVideo.addEventListener('loadeddata', () => {
                    const canvas = document.createElement('canvas');
                    canvas.width = tempVideo.videoWidth;
                    canvas.height = tempVideo.videoHeight;
                    canvas.getContext('2d').drawImage(tempVideo, 0, 0);
                    if (img) {
                        img.src = canvas.toDataURL('image/png');
                    }
                });

                if (title) {
                    title.textContent = file.name;
                }
                if (type) {
                    type.textContent = file.type.split('/')[1]?.toUpperCase() || '';
                }
                if (size) {
                    size.textContent = (file.size / (1024 * 1024)).toFixed(1) + ' Mb';
                }

                updatePreviewVideo(URL.createObjectURL(file));
            }
        });

        removeBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            resetReelUploader(box);
        });
    });
}

function resetReelUploader(box) {
    const input = box.querySelector('input');
    const uploadWrapper = box.querySelector('.upload-wrapper');

    input.value = '';
    box.classList.remove('active');
    uploadWrapper.style.display = 'none';

    const img = uploadWrapper.querySelector('img');
    if (img) img.src = '';

    if (box.dataset.type === 'image') {
        updatePreviewBackground('');
        initStorePreview();
    }

    if (box.dataset.type === 'video') {
        clearPreviewVideo();
    }

    clearVendorDefaultIfEmpty();
}

if (storeSelect) {
    $(document).ready(function () {
        $('.store-select').on('select2:select change', function () {
            const selectedOption = $(this).find('option:selected');
            const text = selectedOption.text();
            const logo = selectedOption.data('logo') || defaultThumb;

            if (text && text !== 'Select Store') {
                showTitle(text);
                showStoreLogo(logo);
            } else {
                showTitle('');
                showStoreLogo('');
            }
        });
    });
}

document.querySelectorAll('.reel-des-textarea').forEach(el => {
    el.addEventListener('input', function () {
        showDescription(this.value);
        applyVendorDefault();
        clearVendorDefaultIfEmpty();
    });
});

playBtn.addEventListener('click', function () {
    const src = previewVideoEl.getAttribute('src');

    if (src && previewVideoEl.readyState >= 2) {
        previewVideoEl.style.display = 'block';
        previewVideoEl.play();
    }
});

document.addEventListener('DOMContentLoaded', function () {
    initReelUploader();
    initStorePreview();
    initDescriptionPreview();
});

document.querySelector('#resetBtn').addEventListener('click', function () {
    setTimeout(() => {
        document.querySelectorAll('.reel-upload-box').forEach(box => {
            resetReelUploader(box);
        });

        initStorePreview();
        initDescriptionPreview();
        updatePreviewBackground('');
        clearPreviewVideo();
    }, 0);
});