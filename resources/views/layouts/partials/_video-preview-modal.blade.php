<div class="modal fade" id="videoPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-333333 border-0">
            <div class="modal-header border-0 px-3 py-3">
                <span class="text-white fs-14" id="modal-video-title"></span>
                <button type="button" class="btn-close bg-474747 w-30 h-30 bg-warning rounded-circle p-1 d-center min-w-30" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <i class="tio-clear fs-14 text-white"></i>
                </button>
            </div>
            <div class="modal-body px-3 pt-0 pb-2 bg-333333 rounded-bottom">
                <div class="position-relative js-video-modal-player">
                    <video id="modalVideoPlayer" playsinline preload="metadata">
                        <source src="">
                        {{ translate('Your browser does not support the video tag.') }}
                    </video>
                </div>
                <div class="ratio ratio-16x9 d-none js-video-modal-embed">
                    <iframe
                        id="modalVideoEmbed"
                        class="w-100 rounded border-0"
                        src=""
                        allow="autoplay; fullscreen; encrypted-media; picture-in-picture"
                        allowfullscreen
                        referrerpolicy="strict-origin-when-cross-origin"
                    ></iframe>
                </div>
                <div class="d-none js-video-modal-unavailable rounded p-4 text-center" style="background:#444;">
                    <img src="{{ asset('public/assets/admin/img/video-placeholder.svg') }}" alt="Video unavailable" class="mb-3" style="width:48px;height:48px;opacity:.8;">
                    <p class="text-white mb-1 font-weight-semibold">{{ translate('Video unavailable') }}</p>
                    <p class="text-white-50 fs-12 mb-0">{{ translate('This video link is invalid, unsupported, or currently not reachable') }}.</p>
                </div>
            </div>
        </div>
    </div>
</div>
