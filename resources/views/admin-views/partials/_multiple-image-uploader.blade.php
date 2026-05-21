@php
    $rootId = $rootId ?? 'multiple-image-uploader-' . uniqid();
    $title = $title ?? translate('messages.Product Additional Images');
    $description = $description ?? translate('messages.Upload additional images. JPG, JPEG, PNG Image size : Max 2 MB (1:1)');
    $containerId = $containerId ?? 'coba';
    $wrapperClass = $wrapperClass ?? 'tabs-inner pt-3 d-flex gap-3 identity_documnet_wrap';
    $fieldName = $fieldName ?? 'item_images[]';
    $maxCount = $maxCount ?? 5;
    $rowHeight = $rowHeight ?? '120px';
    $groupClassName = $groupClassName ?? 'spartan_item_wrapper size--md';
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    $maxFileSizeBytes = (int) $maxSize * 1024 * 1024;
    $placeholderImage = $placeholderImage ?? asset('public/assets/admin/img/400x400/coba-placeholder.png');
    $placeholderWidth = $placeholderWidth ?? '100%';
    $dropFileLabel = $dropFileLabel ?? 'Drop Here';
    $extensionErrorMessage = $extensionErrorMessage ?? translate('messages.please_only_input_png_or_jpg_type_file');
    $sizeErrorMessage = $sizeErrorMessage ?? translate('messages.file_size_too_big');
    $resetButtonSelector = $resetButtonSelector ?? null;
@endphp

<div class="col-md-12" id="{{ $rootId }}">
    <div class="card">
        <div class="card-body">
            <div class="mb-20">
                <h3 class="text-dark mb-1">
                    {{ $title }}
                </h3>
                <p class="fs-12 mb-0">
                    {{ $description }}
                </p>
            </div>
            <div class="__bg-F8F9FC-card p-3">
                <div class="flex-grow-1 mx-auto overflow-x-auto scrollbar-primary">
                    <div class="form-group m-0">
                        <div class="identity_documnet_body multiple_coba-img tabs-slide-wrap position-relative">
                            <div class="{{ $wrapperClass }}" id="{{ $containerId }}"></div>
                            <div class="arrow-area">
                                <div class="button-prev align-items-center">
                                    <button type="button"
                                        class="btn btn-click-prev mr-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                        <i class="tio-chevron-left fs-24"></i>
                                    </button>
                                </div>
                                <div class="button-next align-items-center pt-5">
                                    <button type="button"
                                        class="btn btn-click-next ml-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                        <i class="tio-chevron-right fs-24"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script_2')
    <script>
        $(function() {
            const rootSelector = '#{{ $rootId }}';
            const containerId = @json($containerId);
            const containerSelector = `${rootSelector} #${containerId}`;
            const wrapperClass = @json($wrapperClass);
            const prevButtonSelector = `${rootSelector} .btn-click-prev`;
            const nextButtonSelector = `${rootSelector} .btn-click-next`;
            const prevWrapSelector = `${rootSelector} .button-prev`;
            const nextWrapSelector = `${rootSelector} .button-next`;
            const eventNamespace = '.multipleImageUploader{{ preg_replace('/[^A-Za-z0-9_\-]/', '', $rootId) }}';
            let mutationObserver = null;
            let resizeObserver = null;

            function updateArrows() {
                const container = document.querySelector(containerSelector);
                const prevWrap = document.querySelector(prevWrapSelector);
                const nextWrap = document.querySelector(nextWrapSelector);

                if (!container || !prevWrap || !nextWrap) {
                    return;
                }

                const hasOverflow = container.scrollWidth > container.clientWidth;
                if (!hasOverflow) {
                    prevWrap.style.display = 'none';
                    nextWrap.style.display = 'none';
                    return;
                }

                const scrollLeft = container.scrollLeft;
                const maxScroll = container.scrollWidth - container.clientWidth;

                prevWrap.style.display = scrollLeft > 2 ? 'flex' : 'none';
                nextWrap.style.display = scrollLeft < maxScroll - 2 ? 'flex' : 'none';
            }

            function bindArrowEvents() {
                $(document)
                    .off(`click${eventNamespace}`, prevButtonSelector)
                    .on(`click${eventNamespace}`, prevButtonSelector, function() {
                        const container = document.querySelector(containerSelector);
                        const item = container?.querySelector('.spartan_item_wrapper, .tabs-slide_items, .existing_image');
                        const itemWidth = item?.offsetWidth || 100;
                        container?.scrollBy({
                            left: -itemWidth,
                            behavior: 'smooth'
                        });
                    });

                $(document)
                    .off(`click${eventNamespace}`, nextButtonSelector)
                    .on(`click${eventNamespace}`, nextButtonSelector, function() {
                        const container = document.querySelector(containerSelector);
                        const item = container?.querySelector('.spartan_item_wrapper, .tabs-slide_items, .existing_image');
                        const itemWidth = item?.offsetWidth || 100;
                        container?.scrollBy({
                            left: itemWidth,
                            behavior: 'smooth'
                        });
                    });
            }

            function bindObservers() {
                const container = document.querySelector(containerSelector);
                if (!container) {
                    return;
                }

                if (mutationObserver) {
                    mutationObserver.disconnect();
                }

                if (resizeObserver) {
                    resizeObserver.disconnect();
                }

                container.removeEventListener('scroll', updateArrows);
                container.addEventListener('scroll', updateArrows);
                window.removeEventListener('resize', updateArrows);
                window.addEventListener('resize', updateArrows);

                mutationObserver = new MutationObserver(function() {
                    updateArrows();
                });
                mutationObserver.observe(container, {
                    childList: true,
                    subtree: true
                });

                if (typeof ResizeObserver !== 'undefined') {
                    resizeObserver = new ResizeObserver(function() {
                        updateArrows();
                    });
                    resizeObserver.observe(container);
                }

                updateArrows();
            }

            function initPicker() {
                const $currentContainer = $(containerSelector);
                if (!$currentContainer.length) {
                    return;
                }

                const $existingImages = $currentContainer.find('.existing_image').detach();
                const $newContainer = $('<div>', {
                    id: containerId,
                    class: wrapperClass
                });

                $currentContainer.replaceWith($newContainer);
                if ($existingImages.length) {
                    $newContainer.append($existingImages);
                }

                const pickerMaxCount = Math.max({{ (int) $maxCount }} - $existingImages.length, 0);

                if (pickerMaxCount > 0) {
                    $newContainer.spartanMultiImagePicker({
                        fieldName: @json($fieldName),
                        maxCount: pickerMaxCount,
                        rowHeight: @json($rowHeight),
                        groupClassName: @json($groupClassName),
                        maxFileSize: {{ $maxFileSizeBytes }},
                        placeholderImage: {
                            image: @json($placeholderImage),
                            width: @json($placeholderWidth)
                        },
                        dropFileLabel: @json($dropFileLabel),
                        onAddRow: function() {
                            updateArrows();
                        },
                        onRenderedPreview: function() {
                            updateArrows();
                        },
                        onRemoveRow: function() {
                            updateArrows();
                        },
                        onExtensionErr: function() {
                            toastr.error(@json($extensionErrorMessage), {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        },
                        onSizeErr: function() {
                            toastr.error(@json($sizeErrorMessage), {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    });
                }

                bindObservers();
                updateArrows();
            }

            bindArrowEvents();
            initPicker();

            @if ($resetButtonSelector)
                $(document)
                    .off(`click${eventNamespace}`, @json($resetButtonSelector))
                    .on(`click${eventNamespace}`, @json($resetButtonSelector), function() {
                        setTimeout(function() {
                            initPicker();
                        }, 0);
                    });
            @endif
        });
    </script>
@endpush
