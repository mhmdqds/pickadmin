@php
    $aspectRatio = match ($ratio ?? '1:1') {
        '1:1' => 'ratio-1',
        '2:1' => 'ratio-2-1',
        '3:1' => 'ratio-3-1',
        '9:1' => 'ratio-9-1',
        default => 'ratio-1',
    };
    $imageExtension = $imageExtension ?? IMAGE_EXTENSION;
    $maxSize = $maxSize ?? MAX_FILE_SIZE;
    $isRequired = $isRequired ?? false;
    $existingImage = $existingImage ?? '';
    $ratio = $ratio ?? '1:1';
    $id = $id ?? 'image-input';
    $name = $name ?? 'image';
    $imageFormat = $imageFormat ?? IMAGE_FORMAT;
    $pixel = isset($pixel) && $pixel !== '' ? $pixel . ' px' : null;
    $size = $pixel ?? $ratio;
@endphp

<div class="upload-file mx-auto" data-invalid-icon="{{ dynamicAsset('assets/admin/img/invalid-icon.png') }}">
    <input type="hidden" name="{{ $name }}_deleted" class="image-delete-flag" value="0">
    <input type="file" name="{{ $name }}" id="{{ $id }}" class="upload-file__input single_file_input"
        accept="{{ $imageExtension }}" {{ $isRequired && !$existingImage ? 'required' : '' }}
        data-max-size="{{ $maxSize }}">
    <label class="upload-file__wrapper {{ $aspectRatio }} mx-auto m-0">
        <div class="upload-file-textbox text-center" style="">
            <img width="27" class="svg" src="{{dynamicAsset('assets/admin/img/image-upload.png')}}" alt="img">
            <h6 class="mt-1 text-gray1 fw-medium fs-10 lh-base text-center text-primary">
                {{translate('Click to upload')}}
            </h6>
        </div>
        <img class="upload-file-img" loading="lazy" src="{{ $existingImage }}" data-default-src="" alt=""
            style="display: none;">
    </label>
    <div class="overlay">
        <div class="d-flex gap-1 justify-content-center align-items-center h-100">
            <button type="button" class="btn btn-outline-primary text-primary icon-btn edit_btn">
                <i class="tio-edit"></i>
            </button>
            @if (!$isRequired)
                <button type="button" class="remove_btn btn icon-btn">
                    <i class="tio-delete text-danger"></i>
                </button>
            @endif
        </div>
    </div>
</div>
<p class="fs-10 text-center mb-0 mt-4 text-capitalize">
    {{ translate($imageFormat . ' Image size : Max ' . $maxSize . ' MB')}} <span
        class="font-medium text-title">{{ translate('(' . $size . ')')}}</span>
</p>
