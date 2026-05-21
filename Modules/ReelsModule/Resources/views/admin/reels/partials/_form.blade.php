@php
    $selectedStoreId = old('store_id', $reel->store_id ?? null);
    $isEdit = isset($isEdit) && $isEdit;
    $alwaysVisible = (int) old('is_always_visible', $reel->is_always_visible ?? 0) === 1;
    $defaultDescription = old('description.0', $reel->getRawOriginal('description') ?? '');
    $existingDateRange = old('dates');
    $reelVideoMaxSizeMb = max(1, (int) (\App\CentralLogics\Helpers::get_business_settings('reels_max_upload_size_mb') ?? 15));
    $reelVideoSizeText = str_replace(':size', (string) $reelVideoMaxSizeMb, translate('messages.Size_video_dynamic_9_16_Recommended'));
    $reelMaxDuration = max(1, (int) (\App\CentralLogics\Helpers::get_business_settings('reels_max_duration') ?? 30));
    $reelMaxDurationUnit = \App\CentralLogics\Helpers::get_business_settings('reels_max_duration_unit') ?? 'min';
    $reelMaxDurationSeconds = $reelMaxDurationUnit === 'hour' ? $reelMaxDuration * 3600 : $reelMaxDuration * 60;
    $reelDurationUnitLabel = $reelMaxDurationUnit === 'hour' ? translate('messages.Hour') : translate('messages.Minutes');
    $reelDurationText = str_replace(
        [':duration', ':unit'],
        [(string) $reelMaxDuration, $reelDurationUnitLabel],
        translate('messages.Max_video_duration_dynamic')
    );

    if ($existingDateRange === null && $reel->start_date && $reel->end_date) {
        $existingDateRange = \Carbon\Carbon::parse($reel->start_date)->format('m/d/Y') . ' - ' . \Carbon\Carbon::parse($reel->end_date)->format('m/d/Y');
    }

    $previewStore = $reel->store ?? $stores->firstWhere('id', $selectedStoreId);
    $previewStoreName = $previewStore?->name ?? '';
    $previewStoreLogo = $previewStore?->logo_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg');
    $storeLabel = \App\CentralLogics\Helpers::getStoreLabelByModuleType(config('module.current_module_type'));
    $selectStoreLabel = translate('messages.select') . ' ' . $storeLabel;
@endphp

<div class="card card-body mb-20">
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                <label class="form-label" for="store_id">
                    {{ $storeLabel }}
                    <span class="input-label-secondary m-0" data-toggle="tooltip" data-title="{{ translate('messages.Choose a'). ' ' . $storeLabel .' '. translate('messages. from the list to proceed') }}">
                        <i class="tio-info"></i>
                    </span>
                </label>
                <select class="form-control w-100 js-select2-custom store-select" id="store_id" name="store_id" required>
                    <option value="" disabled {{ !$selectedStoreId ? 'selected' : '' }}>{{ $selectStoreLabel }}</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" data-logo="{{ $store->logo_full_url }}" {{ (int) $selectedStoreId === (int) $store->id ? 'selected' : '' }}>
                            {{ $store->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                <ul class="nav nav-tabs nav--tabs mt-0 mb-20">
                    <li class="nav-item">
                        <a class="nav-link lang_link active" href="#" id="default-link">{{ translate('messages.default') }}</a>
                    </li>
                    @foreach ($language as $lang)
                        <li class="nav-item">
                            <a class="nav-link lang_link" href="#" id="{{ $lang }}-link">
                                {{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="row align-items-end">
                    <div class="col-md-12 lang_form default-form" id="default-form">
                        <label for="reel_description_default" class="form-label">
                            {{ translate('messages.Short_Description') }} ({{ translate('messages.default') }})
                            <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                data-title="{{ translate('Enter a short and engaging description (up to 200 words) that clearly highlights the key details.') }}">
                                <i class="tio-info text-muted"></i>
                            </span>
                        </label>
                        <textarea required id="reel_description_default" class="form-control reel-des-textarea" rows="2" maxlength="200"
                            name="description[]" placeholder="{{ translate('messages.write_short_description') }}">{{ $defaultDescription }}</textarea>
                        <span class="text-right text-counting color-A7A7A7 d-block mt-1">{{ strlen($defaultDescription) }}/200</span>
                        <input type="hidden" name="lang[]" value="default">
                    </div>

                    @foreach ($language as $lang)
                        @php
                            $translatedDescription = old('description.' . ($loop->index + 1));
                            if ($translatedDescription === null) {
                                $translatedDescription = optional(
                                    $reel->translations->first(function ($translation) use ($lang) {
                                        return $translation->locale === $lang && $translation->key === 'description';
                                    })
                                )->value;
                            }
                        @endphp
                        <div class="col-md-12 d-none lang_form" id="{{ $lang }}-form">
                            <label for="reel_description_{{ $lang }}" class="form-label">
                                {{ translate('messages.Short_Description') }} ({{ strtoupper($lang) }})
                            </label>
                            <textarea id="reel_description_{{ $lang }}" class="form-control reel-des-textarea" rows="2" maxlength="200"
                                name="description[]" placeholder="{{ translate('messages.write_short_description') }}">{{ $translatedDescription }}</textarea>
                            <span class="text-right text-counting color-A7A7A7 d-block mt-1">{{ strlen($translatedDescription ?? '') }}/200</span>
                            <input type="hidden" name="lang[]" value="{{ $lang }}">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="reel-upload-box-wrapper text-center">
                            <label class="form-label fs-12 text-muted mb-20">
                                {{ translate('messages.Upload_Thumbnail_Image') }}
                                <span class="text-danger">*</span>
                            </label>

                            <div class="reel-upload-box {{ $reel->thumbnail_full_url ? 'active' : '' }}" data-type="image" data-max-size="{{ MAX_FILE_SIZE }}" data-ratio="9:16" data-original-thumbnail="{{ $reel->thumbnail_full_url ?? '' }}">
                                <input type="file" hidden accept="{{ IMAGE_EXTENSION }}" name="thumbnail">

                                <div class="upload-placeholder text-center" style="{{ $reel->thumbnail_full_url ? 'display:none;' : '' }}">
                                    <img src="{{ asset('public/assets/admin/img/reels/img-icon.png') }}" alt="">
                                    <div>
                                        <span class="text-info">{{ translate('messages.Click_to_upload') }}</span>
                                        <br>
                                        {{ translate('messages.or_drag_and_drop') }}
                                    </div>
                                </div>

                                <div class="upload-wrapper img-upload-wrapper" style="display: {{ $reel->thumbnail_full_url ? 'block' : 'none' }};">
                                    <img  src="{{ $reel->thumbnail_full_url }}" alt="">
                                    <button type="button" class="btn upload-again-btn" aria-label="{{ translate('messages.upload_again') }}">
                                        <i class="tio-edit"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="mt-3">
                                {{ translate(IMAGE_FORMAT) }}
                                <br>
                                  {{ translate('messages.Size_Max_')}} {{ MAX_FILE_SIZE }} {{ translate('MB_9_16') }}
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="reel-upload-box-wrapper text-center">
                            <label class="form-label fs-12 text-muted mb-3">
                                {{ translate('messages.Upload_a_file') }}
                                <span class="text-danger">*</span>
                            </label>

                            <div class="reel-upload-box {{ $reel->video_full_url ? 'active' : '' }}" data-type="video" data-max-size="{{ $reelVideoMaxSizeMb }}" data-max-duration-seconds="{{ $reelMaxDurationSeconds }}" data-max-duration-label="{{ $reelMaxDuration }} {{ $reelDurationUnitLabel }}" data-original-thumbnail="{{ $reel->thumbnail_full_url ?? $previewStoreLogo }}" data-original-video="{{ $reel->video_full_url ?? '' }}" data-original-video-name="{{ $reel->video ? basename($reel->video) : '' }}" data-original-video-type="{{ $reel->video ? strtoupper(pathinfo($reel->video, PATHINFO_EXTENSION)) : '' }}">
                                <input type="file" hidden accept="video/*" name="video">

                                <div class="upload-placeholder text-center" style="{{ $reel->video_full_url ? 'display:none;' : '' }}">
                                    <img src="{{ asset('public/assets/admin/img/reels/video-icon.png') }}" alt="">
                                    <div>
                                        <span class="text-info">{{ translate('messages.Add_Video') }}</span>
                                    </div>
                                </div>

                                <div class="upload-wrapper reel-upload-wrapper" style="display: {{ $reel->video_full_url ? 'block' : 'none' }};">
                                    <div class="img-wrapper">
                                        <img src="{{ $reel->thumbnail_full_url ?? $previewStoreLogo }}" alt="">
                                        <div class="reels-play-btn">
                                            <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                                <i class="tio-play"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <h6 class="fs-10 fw-medium mb-0 reel-title">{{ $reel->video ? basename($reel->video) : '' }}</h6>
                                    <p class="fs-10 mt-2 mb-0"><span class="reel-type">{{ $reel->video ? strtoupper(pathinfo($reel->video, PATHINFO_EXTENSION)) : '' }}</span></p>
                                    <button type="button" class="btn upload-again-btn" aria-label="{{ translate('messages.upload_again') }}">
                                        <i class="tio-edit"></i>
                                    </button>
                                </div>
                            </div>

                            <p class="mt-3">
                                {{ translate('messages.Mp4_MOV_3GP_GIF') }}
                                <br>
                                {{ $reelVideoSizeText }}
                                <br>
                                {{ $reelDurationText }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-light p-3 p-xxl-4 rounded">
                <div class="d-flex gap-2 justify-content-between flex-wrap mb-2">
                    <label class="form-label mb-0" for="dates">
                        {{ translate('messages.Reel_Validity') }}
                        <span class="input-label-secondary m-0" data-toggle="tooltip" data-title="{{ translate('messages.select_reels_duration') }}">
                            <i class="tio-info"></i>
                        </span>
                    </label>
                    <label class="form-label d-flex gap-2 align-items-center mb-0" for="is_always_visible">
                        {{ translate('messages.Always_Visible_to_Customers') }}
                        <input type="checkbox" id="is_always_visible" name="is_always_visible" value="1" {{ $alwaysVisible ? 'checked' : '' }}>
                    </label>
                </div>
                <div class="position-relative">
                    <i class="tio-calendar fs-16 icon-absolute-on-right"></i>
                    <input required type="text" id="dates" class="form-control h-45 position-relative bg-transparent"
                        name="dates" value="{{ $existingDateRange ?? '' }}"
                        data-initial-value="{{ $existingDateRange ?? '' }}"
                        data-no-global-daterangepicker="true"
                        placeholder="{{ translate('messages.select_reels_duration') }}" autocomplete="off" {{ $alwaysVisible ? 'disabled' : '' }}>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="bg-light p-3 p-xxl-4 rounded">
                <h4 class="fw-medium mb-3">{{ translate('messages.Reel_Preview') }}</h4>
                    <div class="reel-preview-box {{ $reel->video_full_url ? 'active' : '' }}" style="{{ $reel->thumbnail_full_url ? "background-image: url('{$reel->thumbnail_full_url}');" : '' }}">
                        <video src="{{ $reel->video_full_url }}" controls class="reels-video" style="display:none;"></video>
                    <div class="reel-overlay">
                        <button type="button" class="btn reels-play-btn">
                            <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                <i class="tio-play"></i>
                            </div>
                        </button>
                    </div>
                        <div class="reel-des-wrapper">
                            <div class="d-flex gap-2 align-items-center mb-2">
                            <div class="reel-preview-thumbnail" data-reel-thumbnail="{{ $previewStoreLogo }}" style="{{ $previewStoreLogo ? "background-image: url('{$previewStoreLogo}');" : '' }}"></div>
                            <div class="thumbnail-placeholder" style="{{ $previewStoreLogo ? 'display:none;' : '' }}"></div>
                            <div class="reel-preview-title" data-reel-title="{{ $previewStoreName }}">{{ $previewStoreName }}</div>
                            <div class="title-placeholder" style="{{ $previewStoreName ? 'display:none;' : '' }}"></div>
                        </div>
                        <div class="reel-preview-des">{{ $defaultDescription }}</div>
                        <div class="des-placeholder" style="{{ $defaultDescription ? 'display:none;' : '' }}">
                            <div class="mb-1"></div>
                            <div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="btn--container justify-content-end">
    <button type="reset" id="resetBtn" class="btn btn--reset min-w-120">{{ translate('messages.Reset') }}</button>
    <button type="submit" class="btn btn-primary min-w-120 call-demo">
        {{ $isEdit ? translate('messages.Update') : translate('messages.Submit') }}
    </button>
</div>
