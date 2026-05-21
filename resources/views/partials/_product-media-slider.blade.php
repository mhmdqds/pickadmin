@php
    $galleryImages = collect([$product['image_full_url'] ?? null])
        ->merge($product['images_full_url'] ?? [])
        ->filter()
        ->unique()
        ->values();

    $mediaItems = collect();

    if ($product?->has_video_preview || ($product?->video_fallback_required && !isset($is_edit_mode))) {
        $mediaItems->push([
            'type' => 'video',
            'is_fallback' => (bool) ($product->video_fallback_required),
            'fallback_reason' => $product->video_unavailable_reason,
            'preview_type' => $product->video_preview_type,
            'preview_url' => $product->video_preview_url,
            'thumbnail_url' => $product->video_thumbnail_url ?: $product->video_full_url,
            'modal_type' => $product->video_preview_modal_type,
            'modal_url' => $product->video_preview_modal_url,
            'title' => $product->name,
        ]);
    }

    foreach ($galleryImages as $imageUrl) {
        $mediaItems->push([
            'type' => 'image',
            'url' => $imageUrl,
        ]);
    }
@endphp

@if ($mediaItems->isNotEmpty())
    <div class="product-card-slider-wrap js-product-media-gallery">
        <div class="main-slider-container">
            <div class="js-product-main-slider owl-carousel owl-theme">
                @foreach ($mediaItems as $mediaItem)
                    <div class="item product-media-slide {{ $mediaItem['type'] === 'video' ? 'product-media-slide--video' : '' }}">
                        @if ($mediaItem['type'] === 'video')
                            <div class="product-media-slide__media-surface position-relative h-100" 
                                 data-video-preview-surface 
                                 data-video-preview-state="{{ ($mediaItem['is_fallback'] ?? false) ? 'fallback' : 'available' }}">
                                <button type="button"
                                    class="product-media-slide__media d-block border-0 bg-transparent w-100 h-100 p-0 js-product-video-preview-trigger {{ ($mediaItem['is_fallback'] ?? false) ? 'd-none' : '' }}"
                                    data-preview-type="{{ $mediaItem['modal_type'] }}"
                                    data-preview-url="{{ $mediaItem['modal_url'] }}"
                                    data-preview-title="{{ $mediaItem['title'] }}"
                                >
                                    @if (in_array($mediaItem['preview_type'], ['upload', 'direct']) && $mediaItem['preview_url'])
                                        <video class="product-media-slide__video js-product-video-thumb" muted playsinline preload="metadata" @if($mediaItem['preview_type'] === 'direct') crossorigin="anonymous" @endif>
                                            <source src="{{ $mediaItem['preview_url'] }}">
                                        </video>
                                    @elseif ($mediaItem['thumbnail_url'])
                                        <img class="product-media-slide__image" src="{{ $mediaItem['thumbnail_url'] }}" alt="{{ $product->name }}">
                                    @endif
                                    <span class="disabled-play position-absolute">
                                        <i class="tio-play fs-32 text-white"></i>
                                    </span>
                                </button>
                                
                                <div class="product-media-slide__unavailable product-media-slide__media d-flex flex-column align-items-center justify-content-center gap-2 p-4 text-center h-100 {{ ($mediaItem['is_fallback'] ?? false) ? '' : 'd-none' }}" data-video-fallback>
                                    <i class="tio-video-off fs-40 text-muted"></i>
                                    <div class="text-muted fs-12">
                                        @if(($mediaItem['fallback_reason'] ?? '') === 'file_missing')
                                            {{ translate('Video file missing') }}
                                        @else
                                            {{ translate('Video unavailable') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <img class="onerror-image product-media-slide__image"
                                src="{{ $mediaItem['url'] }}"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                alt="Image Description">
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="nav-arrows">
                <button class="prev-arrow btn p-0 m-0 js-product-gallery-prev" type="button">
                    <i class="tio-chevron-left"></i>
                </button>
                <button class="next-arrow btn p-0 m-0 js-product-gallery-next" type="button">
                    <i class="tio-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="thumb-slider-container">
            <div class="js-product-thumb-slider owl-carousel owl-theme">
                @foreach ($mediaItems as $mediaItem)
                    <div class="item product-media-thumb {{ $mediaItem['type'] === 'video' ? 'product-media-thumb--video' : '' }}">
                        @if ($mediaItem['type'] === 'video')
                            <div class="product-media-thumb__media-surface position-relative h-100" 
                                 data-video-preview-surface 
                                 data-video-preview-state="{{ ($mediaItem['is_fallback'] ?? false) ? 'fallback' : 'available' }}">
                                <button type="button"
                                    class="product-media-thumb__media border-0 bg-transparent w-100 h-100 p-0 js-product-video-preview-trigger {{ ($mediaItem['is_fallback'] ?? false) ? 'd-none' : '' }}"
                                    data-preview-type="{{ $mediaItem['modal_type'] }}"
                                    data-preview-url="{{ $mediaItem['modal_url'] }}"
                                    data-preview-title="{{ $mediaItem['title'] }}"
                                >
                                    @if (in_array($mediaItem['preview_type'], ['upload', 'direct']) && $mediaItem['preview_url'])
                                        <video class="product-media-thumb__video js-product-video-thumb" muted playsinline preload="metadata" @if($mediaItem['preview_type'] === 'direct') crossorigin="anonymous" @endif>
                                            <source src="{{ $mediaItem['preview_url'] }}">
                                        </video>
                                    @elseif ($mediaItem['thumbnail_url'])
                                        <img class="product-media-thumb__image" src="{{ $mediaItem['thumbnail_url'] }}" alt="{{ $product->name }}">
                                    @endif
                                    <span class="disabled-play position-absolute">
                                        <i class="tio-play fs-20 text-white"></i>
                                    </span>
                                </button>
                                <div class="product-media-thumb__unavailable product-media-thumb__media d-flex align-items-center justify-content-center h-100 {{ ($mediaItem['is_fallback'] ?? false) ? '' : 'd-none' }}" data-video-fallback>
                                    <i class="tio-video-off text-muted opacity-50 fs-20"></i>
                                </div>
                            </div>
                        @else
                            <img class="onerror-image product-media-thumb__image"
                                src="{{ $mediaItem['url'] }}"
                                data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                alt="Image Description">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
