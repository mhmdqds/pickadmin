@php
    $productVideo = $product ?? null;
    $videoMode = old('video_upload_type', $productVideo?->video_link ? 'link' : 'file');
    $existingVideoUrl = $productVideo?->video_full_url;
    $existingVideoName = $productVideo?->video ? basename($productVideo->video) : '';
    $existingVideoSize = $productVideo?->video_size ?? 0;
    $existingVideoLink = old('video_link', $productVideo?->video_link);
    $videoLimitMb = $PRODUCT_VIDEO_MAX_FILE_SIZE ?? \App\CentralLogics\Helpers::productVideoMaxUploadSizeMb();
@endphp

<div class="col-md-6">
    <div class="card h-100 product-video-section" data-video-max-size="{{ $videoLimitMb }}"
        data-initial-video-url="{{ $existingVideoUrl }}" data-initial-video-name="{{ $existingVideoName }}"
        data-initial-video-size="{{ $existingVideoSize }}">
        <div class="card-body">
            <div class="mb-20">
                <h3 class="text--title text-dark mb-0">Product Video</h3>
                <p class="fs-12 mb-0">Upload one optional video or provide a video link.</p>
            </div>

            <input type="hidden" name="remove_video" class="js-remove-video-input" value="0">

            <div class="d-flex align-items-center gap-2 bg-white border flex-sm-nowrap flex-wrap py-2 px-3 rounded">
                <label class="form-check form--check w-100">
                    <input class="form-check-input js-video-type-switch" type="radio" value="file"
                        name="video_upload_type" {{ $videoMode === 'file' ? 'checked' : '' }}>
                    <span class="form-check-label">Upload Video</span>
                </label>
                <label class="form-check form--check w-100">
                    <input class="form-check-input js-video-type-switch" type="radio" value="link"
                        name="video_upload_type" {{ $videoMode === 'link' ? 'checked' : '' }}>
                    <span class="form-check-label">Upload Video Link</span>
                </label>
            </div>

            <div class="bg-light rounded p-xxl-4 p-3 mt-3 upload-video-file-container">
                <div
                    class="video-upload-initial video-upload__box-wrap max-w-130px mx-auto position-relative overflow-hidden {{ $existingVideoUrl && $videoMode === 'file' ? 'd-none' : '' }}">
                    <div class="video-upload__box ratio--1 mx-auto h-100px aspect-1 d-center">
                        <input type="file" name="video"
                            class="video-input position-absolute w-100 h-100 opacity-0 cursor-pointer"
                            accept="{{ VIDEO_EXTENSION }}" data-max-size="{{ $videoLimitMb }}">
                        <div class="text-center">
                            <img width="34" height="34"
                                src="{{ asset('public/assets/admin/img/video-placeholder.svg') }}" alt="">
                            <h6 class="mt-2 mb-0 fs-10 text-primary font-semibold text-center">
                                <span>{{ translate('messages.Add Video') }}</span>
                            </h6>
                        </div>
                    </div>
                    <p class="fs-10 mb-0 text-center mt-3">
                        {{ strtoupper(VIDEO_FORMAT) }} Size : Max <span class="text-dark">{{ $videoLimitMb }} MB</span>
                    </p>
                </div>
                <div
                    class="video-upload-filled max-w-320 mx-auto {{ $existingVideoUrl && $videoMode === 'file' ? 'd-flex' : 'd-none' }} border bg-white rounded p-2 align-items-center position-relative justify-content-between bg-light">
                    <div class="d-flex align-items-center gap-xl-15 gap-2 cursor-pointer open-preview-btn"
                        role="button">
                        <div class="position-relative video-thumbnail-wrap_area" data-video-preview-surface>
                            <video class="video-thumbnail-element rounded" muted preload="metadata">
                                <source src="{{ $existingVideoUrl }}" type="video/mp4">
                            </video>
                            <div class="disabled-play position-absolute">
                                <i class="tio-play fs-32 text-white"></i>
                            </div>
                            <div class="d-none position-absolute inset-0 d-center bg-light rounded" data-video-fallback>
                                <i class="tio-video-off text-muted fs-24"></i>
                            </div>
                        </div>
                        <div class="text-break pe-30">
                            <h6 class="mb-1 fs-14 text-dark line--limit-1 file-name-display">{{ $existingVideoName }}
                            </h6>
                            <small class="text-muted fs-14 file-size-display">
                                {{ $existingVideoSize ? strtoupper(pathinfo($existingVideoName, PATHINFO_EXTENSION) ?: 'video') . ' ' . number_format($existingVideoSize / 1048576, 2) . ' MB' : '' }}
                            </small>
                        </div>
                    </div>
                    <button type="button"
                        class="btn-close bg-danger position-absolute p-1 w-20px h-20 mt-10px d-center top01 end-10 rounded-circle p-2 fs-10 text-white remove-video-btn">
                        <i class="tio-clear"></i>
                    </button>
                </div>
            </div>

            <div class="bg-light rounded p-20 mt-3 upload-video-link-container">
                <div>
                    <label class="input-label text-capitalize d-flex align-items-center">
                        <span class="line--limit-1">Provide Video Link</span>
                    </label>
                    <input type="text" name="video_link" class="form-control js-product-video-link"
                        value="{{ $existingVideoLink }}" placeholder="https://example.com/video" data-link-validation
                        data-link-validation-message="Please enter a valid video link.">
                </div>
            </div>
        </div>
    </div>
</div>