@extends('layouts.admin.app')

@section('title', translate('messages.banner'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.Banners') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h5 class="text-title mb-1">
                                {{ translate('messages.Update_Banner') }}
                            </h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{route('admin.rental.banner.update', [$banner['id']])}}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-lg-6">
                                    <div class="__bg-FAFAFA p-4 radius-10 mb-4">
                                        @if ($language)
                                            <ul class="nav nav-tabs mb-3 border-0">
                                                <li class="nav-item">
                                                    <a class="nav-link lang_link active" href="#"
                                                        id="default-link">{{ translate('messages.default') }}</a>
                                                </li>
                                                @foreach ($language as $lang)
                                                    <li class="nav-item">
                                                        <a class="nav-link lang_link" href="#"
                                                            id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="lang_form" id="default-form">
                                                <div class="form-group mb-0">
                                                    <label class="input-label"
                                                        for="default_title">{{ translate('messages.title') }}
                                                        ({{ translate('Default') }})
                                                    </label>
                                                    <input type="text" name="title[]" id="default_title"
                                                        class="form-control" value="{{$banner?->getRawOriginal('title')}}"
                                                        placeholder="{{ translate('messages.new_banner') }}">
                                                </div>
                                                <input type="hidden" name="lang[]" value="default">
                                            </div>
                                            @foreach ($language as $lang)

                                            <?php
                                            if(count($banner['translations'])){
                                                $translate = [];
                                                foreach($banner['translations'] as $t)
                                                {
                                                    if($t->locale == $lang && $t->key=="title"){
                                                        $translate[$lang]['title'] = $t->value;
                                                    }
                                                }
                                            }
                                        ?>



                                                <div class="d-none lang_form" id="{{ $lang }}-form">
                                                    <div class="form-group mb-0">
                                                        <label class="input-label"
                                                            for="{{ $lang }}_title">{{ translate('messages.title') }}
                                                            ({{ strtoupper($lang) }})
                                                        </label>
                                                        <input type="text" name="title[]" id="{{ $lang }}_title"
                                                            class="form-control" value="{{$translate[$lang]['title']??''}}"
                                                            placeholder="{{ translate('messages.new_banner') }}">
                                                    </div>
                                                    <input type="hidden" name="lang[]" value="{{ $lang }}">
                                                </div>
                                            @endforeach
                                        @endif

                                    </div>

                                    <div class="form-group">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('Banner_Type') }}</label>
                                        <select name="banner_type" id="banner_type" class="custom-select js-select2-custom">
                                            <option  {{$banner->type == 'store_wise'? 'selected':'' }}  value="store_wise">{{ translate('Provider_Wise') }}</option>
                                            <option {{$banner->type == 'default'? 'selected':'' }}   value="default">{{ translate('messages.default') }}</option>
                                        </select>
                                    </div>


                                    <div class="form-group mb-0  {{$banner->type == 'store_wise'? '':'d-none' }}" id="store_wise">
                                        <label class="input-label"
                                            for="exampleFormControlSelect1">{{ translate('messages.provider') }}</label>
                                        <select name="store_id" id="store_id" data-url="{{ route('admin.store.get-providers') }}" class="js-data-example-ajax form-control"
                                            title="{{ translate('messages.Select_Provider') }}">
                                            @if($banner->type=='store_wise')
                                        @php($store = \App\Models\Store::where('id', $banner->data)->first(['id','name']))
                                            @if($store)
                                            <option value="{{$store->id}}" selected>{{$store->name}}</option>
                                            @endif
                                        @endif
                                        </select>
                                    </div>

                                    <div class="form-group mb-0 {{$banner->type !== 'store_wise'? '':'d-none' }}" id="default">
                                        <label class="input-label"
                                            for="exampleFormControlInput1">{{ translate('messages.default_link') }}({{ translate('messages.optional') }})</label>
                                        <input type="url" name="default_link" class="form-control" value="{{ $banner->default_link }}"
                                            placeholder="{{ translate('messages.default_link') }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="h-100 d-flex flex-column justify-content-between">
                                        <div class="form-group">
                                            <label
                                                class="fs-16 text-title font-semibold  mb-0">{{ translate('messages.Banner_Image') }}</label>
                                            <p class="mb-20">{{ translate('JPG, JPEG, PNG Less Than 2MB') }} <span
                                                    class="font-weight-bold">({{ translate('Ratio 3:1') }})</span>
                                            </p>
                                            <div class="upload-file image-general">
                                                <a href="javascript:void(0);" class="remove-btn opacity-0 z-index-99">
                                                    <i class="tio-clear"></i>
                                                </a>
                                                <input type="file" name="image" class="upload-file__input single_file_input" value="{{ $banner['image_full_url'] }}" accept=".webp, .jpg, .jpeg, .png" title="" />
                                                <label class="upload-file-wrapper fullwidth">
                                                    <div class="upload-file-textbox text-center">
                                                        <img width="34" height="34" src="{{ asset('public/assets/admin/img/document-upload.svg') }}" alt="">
                                                        <h6 class="mt-2 font-semibold text-center">
                                                            <span>{{ translate('Click to upload') }}</span>
                                                            <br>
                                                            {{ translate('or drag and drop') }}
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy"  src="{{ $banner['image_full_url'] }}" alt="">
                                                </label>
                                            </div>

                                        </div>
                                        <div class="btn--container justify-content-end">
                                            <button type="reset" id="reset_btn"
                                                class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                            <button type="submit"
                                                class="btn btn--primary">{{ translate('messages.submit') }}</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <input type="hidden" id="current_module_id" value="{{ Config::get('module.current_module_id') }}" >
    <input type="hidden" id="defaut_banner_type" value="{{ $banner?->type }}" >
    <input type="hidden" id="defaut_image_url" value="{{ $banner?->image_full_url }}" >
    <input type="hidden" id="default_store_id" value="{{ $store?->id ?? null }}" >
    

@endsection

@push('script_2')

<script src="{{ asset('Modules/Rental/public/assets/js/admin/view-pages/banner-edit.js') }}"></script>
@endpush
