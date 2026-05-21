@extends('layouts.admin.app')

@section('title', translate('messages.banner'))

@push('css_or_js')

@endpush

@section('content')
@php($bottom_section_banner = \App\Models\ModuleWiseBanner::where('module_id', Config::get('module.current_module_id'))->where('key', 'bottom_section_banner')->first())
@php($best_reviewed_section_banner = \App\Models\ModuleWiseBanner::where('module_id', Config::get('module.current_module_id'))->where('key', 'best_reviewed_section_banner')->first())
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-header-title">
            <span class="page-header-icon">
                <img src="{{asset('public/assets/admin/img/3rd-party.png')}}" class="w--26" alt="">
            </span>
            <span>
                {{translate('messages.Other_Promotional_Content_Setup')}}
            </span>
        </h1>
    </div>
    <!-- End Page Header -->
    <div class="row g-3">
        <div class="col-lg-6">
            <form action="{{ route('admin.promotional-banner.store') }}" method="POST"
                enctype="multipart/form-data" class="h-100">
                @csrf
                <div class="card card-body h-100">
                    <input type="text" name="key" value="bottom_section_banner" hidden>
                    <div class="d-flex gap-1 align-items-center mb-4">
                        <img src="{{asset('public/assets/admin/img/other-banner.png')}}" class="h-85"
                            alt="">
                        <h3 class="fs-16 mb-0">
                            {{translate('Bottom_Section_Banner')}}
                        </h3>
                    </div>
                    <div class="bg-light2 rounded h-100 p-3 p-sm-4">
                        <div class="text-center">
                            <div class="mb-4">
                                <h4 class="mb-1">{{ translate('Upload_Banner') }} <span class="text-danger">*</span></h4>
                            </div>
                            @include('admin-views.partials._image-uploader', [
                                'id' => 'image-input',
                                'name' => 'image',
                                'ratio' => '4:1',
                                'isRequired' => false,
                                'existingImage' => \App\CentralLogics\Helpers::get_full_url('promotional_banner', $bottom_section_banner?->value ?? '', $bottom_section_banner?->storage[0]?->value ?? 'public', 'upload_placeholder') ?? null,
                                'imageExtension' => IMAGE_EXTENSION,
                                'imageFormat' => IMAGE_FORMAT,
                                'maxSize' => MAX_FILE_SIZE,
                                'textPosition' => 'bottom'
                                ])
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-4">
                        <button type="submit"
                            class="btn btn--primary mb-2">{{translate('messages.Submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="col-lg-6">
            <form action="{{ route('admin.promotional-banner.store') }}" method="POST"
                enctype="multipart/form-data" class="h-100">
                @csrf
                <div class="card card-body h-100">
                    <input type="text" name="key" value="best_reviewed_section_banner" hidden>
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <div class="d-flex gap-1 align-items-center">
                            <img src="{{asset('public/assets/admin/img/other-banner.png')}}" class="h-85"
                                alt="">
                            <h3 class="fs-16 mb-0">
                                {{translate('Best_Reviewed_Section_Banner')}}
                            </h3>
                        </div>
                        <div class="blinkings">
                            <div>
                                <i class="tio-info-outined"></i>
                            </div>
                            <div class="business-notes">
                                <h6><img src="{{asset('/public/assets/admin/img/notes.png')}}" alt="">
                                    {{translate('Note')}}</h6>
                                <div>
                                    {{translate('messages.this_banner_is_only_for_react_web.')}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-light2 rounded h-100 p-3 p-sm-4">
                        <div class="text-center">
                            <div class="mb-4">
                                <h4 class="mb-1">{{ translate('Upload_Banner') }} <span class="text-danger">*</span></h4>
                            </div>
                            @include('admin-views.partials._image-uploader', [
                                'id' => 'image-input',
                                'name' => 'image',
                                'ratio' => 'Min_Size_for_Better_Resolution_235_x_375_px',
                                'isRequired' => false,
                                'existingImage' => \App\CentralLogics\Helpers::get_full_url('promotional_banner', $best_reviewed_section_banner?->value ?? '', $best_reviewed_section_banner?->storage[0]?->value ?? 'public', 'upload_placeholder') ?? null,
                                'imageExtension' => IMAGE_EXTENSION,
                                'imageFormat' => IMAGE_FORMAT,
                                'maxSize' => MAX_FILE_SIZE,
                                'textPosition' => 'bottom'
                                ])
                        </div>
                    </div>
                    <div class="btn--container justify-content-end mt-4">
                        <button type="submit"
                            class="btn btn--primary mb-2">{{translate('messages.Submit')}}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<form id="best_reviewed_section_banner_form" action="{{ route('admin.remove_image') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{  $best_reviewed_section_banner?->id}}">
    {{-- <input type="hidden" name="json" value="1"> --}}
    <input type="hidden" name="model_name" value="ModuleWiseBanner">
    <input type="hidden" name="image_path" value="promotional_banner">
    <input type="hidden" name="field_name" value="value">
</form>
<form id="bottom_section_banner_form" action="{{ route('admin.remove_image') }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{  $bottom_section_banner?->id}}">
    {{-- <input type="hidden" name="json" value="1"> --}}
    <input type="hidden" name="model_name" value="ModuleWiseBanner">
    <input type="hidden" name="image_path" value="promotional_banner">
    <input type="hidden" name="field_name" value="value">
</form>
@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/other-banners.js"></script>
    <script>
        $('#reset_btn').click(function () {
            $('#viewer').attr('src', '{{asset('/public/assets/admin/img/upload-placeholder.png')}}');
        })
    </script>
@endpush