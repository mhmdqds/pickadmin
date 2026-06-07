@extends('layouts.admin.app')

@section('title', translate('messages.Home_Page_Setup'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/store.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ translate('messages.Home_Page_Setup') }} ({{ translate('Only_For_React_Web') }})
                    </h1></span>
                    </h1>
                </div>
            </div>

            <!-- Nav Scroller -->
            <div class="js-nav-scroller hs-nav-scroller-horizontal">
                <span class="hs-nav-scroller-arrow-prev d-none">
                    <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                        <i class="tio-chevron-left"></i>
                    </a>
                </span>

                <span class="hs-nav-scroller-arrow-next d-none">
                    <a class="hs-nav-scroller-arrow-link" href="javascript:;">
                        <i class="tio-chevron-right"></i>
                    </a>
                </span>

                <!-- Nav -->
                <ul class="nav nav-tabs border-0 nav--tabs nav--pills mb-2">
                    <li class="nav-item">
                        <a class="nav-link text-capitalize text-title active"
                            href="javascript:">{{ translate('messages.Download App') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-capitalize text-title"
                            href="{{ route('admin.rental.settings.vendors_registration') }}">{{ translate('messages.Vendors Registration') }}</a>
                    </li>
                </ul>
                <!-- End Nav -->
            </div>
            <!-- End Nav Scroller -->
        </div>
        <!-- End Page Header -->


        {{-- @dd($title_data,
        $sub_title_data,
        $image) --}}
        <form action="{{ route('admin.rental.settings.down_app_update') }}" method="post" enctype="multipart/form-data">
            @csrf
            <div class="card mb-20">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-lg-6">
                            @if ($language)
                                <ul class="nav nav-tabs border-0 mb-4">
                                    <li class="nav-item">
                                        <a class="nav-link lang_link active" href="#"
                                            id="default-link">{{ translate('Default') }}</a>
                                    </li>
                                    @foreach ($language as $lang)
                                        <li class="nav-item">
                                            <a class="nav-link lang_link" href="#"
                                                id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            @if ($language)
                                <div class="lang_form" id="default-form">
                                    <div class="form-group mb-20">
                                        <label class="input-label font-semibold"
                                            for="default_name">{{ translate('messages.title') }}
                                            ({{ translate('messages.Default') }})
                                        </label>
                                        <div class="character-count">
                                            <input type="text" name="title[]" id="default_name"
                                                class="form-control character-count-field h--45px"
                                                value="{{ $title_data?->getRawOriginal('value') }}"
                                                placeholder="{{ translate('messages.type_title') }}" maxlength="30"
                                                data-max-character="30" required>
                                            <span class="d-flex text-count justify-content-end"></span>
                                        </div>
                                    </div>
                                    <input type="hidden" name="lang[]" value="default">
                                    <div class="form-group mb-20">
                                        <label class="input-label font-semibold"
                                            for="exampleFormControlInput1">{{ translate('messages.subtitle') }}
                                            ({{ translate('messages.default') }})</label>
                                        <div class="character-count">
                                            <textarea type="text" name="sub_title[]" placeholder="{{ translate('messages.type_subtitle') }}"
                                                class="form-control  character-count-field" maxlength="110" data-max-character="110">{{ $sub_title_data?->getRawOriginal('value') }}</textarea>
                                            <span class="d-flex text-count justify-content-end"></span>
                                        </div>

                                    </div>
                                </div>
                                @foreach ($language as $lang)
                                    <?php
                                    if(isset($title_data->translations)&&count($title_data->translations)){
                                        $title_data_translate = [];
                                        foreach($title_data->translations as $t)
                                        {
                                            if($t->locale == $lang && $t->key=='module_home_page_data_title'){
                                                $title_data_translate[$lang]['value'] = $t->value;
                                            }
                                        }

                                    }
                                if(isset($sub_title_data->translations) && count($sub_title_data->translations)){
                                        $sub_title_data_translate = [];
                                        foreach($sub_title_data->translations as $t)
                                        {
                                            if($t->locale == $lang && $t->key=='module_home_page_data_sub_title'){
                                                $sub_title_data_translate[$lang]['value'] = $t->value;
                                            }
                                        }

                                    }
                                    ?>


                                    <div class="d-none lang_form" id="{{ $lang }}-form">
                                        <div class="form-group mb-20">
                                            <label class="input-label font-semibold"
                                                for="{{ $lang }}_name">{{ translate('messages.title') }}
                                                ({{ strtoupper($lang) }})
                                            </label>
                                            <div class="character-count">
                                                <input type="text" name="title[]" id="{{ $lang }}_name"
                                                    class="form-control character-count-field h--45px" maxlength="30"
                                                    data-max-character="30" value="{{ $title_data_translate[$lang]['value']?? '' }}"
                                                    placeholder="{{ translate('messages.type_title') }}">
                                                <span class="d-flex text-count justify-content-end"></span>
                                            </div>
                                        </div>
                                        <input type="hidden" name="lang[]" value="{{ $lang }}">
                                        <div class="form-group mb-20">
                                            <label class="input-label font-semibold"
                                                for="exampleFormControlInput1">{{ translate('messages.subtitle') }}
                                                ({{ strtoupper($lang) }})</label>
                                            <div class="character-count">
                                                <textarea type="text" name="sub_title[]" placeholder="{{ translate('messages.type_subtitle') }}"
                                                    class="form-control character-count-field" maxlength="110" data-max-character="110">{{ $sub_title_data_translate[$lang]['value']?? '' }}</textarea>
                                                <span class="d-flex text-count justify-content-end"></span>
                                            </div>

                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex flex-column justify-content-between h-100">
                                <div class="text-center">
                                    <label class="text--title fs-16 font-semibold mb-1">
                                        {{ translate('Image') }}
                                    </label>
                                    <div class="mb-20">
                                        <p class="fs-12">
                                            {{ translate('JPG, JPEG, PNG Less Than 1MB') }} <strong class="font-semibold">({{ translate('Ratio 1:1') }})</strong>
                                        </p>
                                    </div>
                                    <div class="upload-file image-general d-inline-block w-auto">
                                        <a href="javascript:void(0);" class="remove-btn opacity-0 z-index-99">
                                            <i class="tio-clear"></i>
                                        </a>
                                        <input type="file" name="image" class="upload-file__input single_file_input"
                                            accept=".webp, .jpg, .jpeg, .png"  value="{{ $image?->value ?  \App\CentralLogics\Helpers::get_full_url('react_landing', $image?->value?? '', $image?->storage[0]?->value ?? 'public','upload_image_1' ) : '' }}">
                                        <label
                                            class="upload-file-wrapper w--180px">
                                            <div class="upload-file-textbox text-center">
                                                <img width="34" height="34" src="{{ asset('public/assets/admin/img/document-upload.svg') }}" alt="">
                                                <h6 class="mt-2 font-semibold text-center">
                                                    <span>{{ translate('Click to upload') }}</span>
                                                    <br>
                                                    {{ translate('or drag and drop') }}
                                                </h6>
                                            </div>
                                            <img class="upload-file-img d-none" data-src="{{ $image?->value ?  \App\CentralLogics\Helpers::get_full_url('react_landing', $image?->value?? '', $image?->storage[0]?->value ?? 'public','upload_image_1' ) : '' }}" height="180" width="180" loading="lazy"   src="{{ $image?->value ?  \App\CentralLogics\Helpers::get_full_url('react_landing', $image?->value?? '', $image?->storage[0]?->value ?? 'public','upload_image_1' ) : '' }}" alt="">
                                        </label>
                                    </div>

                                </div>
                                <div class="btn--container justify-content-end mt-5">
                                    <button type="reset" id="reset_btn" data-src="{{ $image?->value ?  \App\CentralLogics\Helpers::get_full_url('react_landing', $image?->value?? '', $image?->storage[0]?->value ?? 'public','upload_image_1' ) : '' }}"
                                        class="btn btn--reset min-w-120px">{{ translate('messages.reset') }}</button>
                                    <button type="submit"
                                        class="btn btn--primary min-w-120px">{{ translate('messages.Submit') }}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <label class="badge badge-soft-secondary fs-12 p-10px">
                <span class="text--danger"># {{ translate('Note:') }} </span>
                <span class="font-regular opacity-lg text-title">{{ translate('This Section App Download buttons are appear based on
                    footer Apps Download button') }}</span>
            </label>
        </div>

    </div>
@endsection

@push('script_2')

<script src="{{ asset('Modules/Rental/public/assets/js/admin/view-pages/home-page-download-app.js') }}"></script>

@endpush
