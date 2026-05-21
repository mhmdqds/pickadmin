@extends('layouts.admin.app')

@section('title',translate('messages.Update campaign'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/campaign.png')}}" class="w--26" alt="">
                </span>
                <span>
                    {{translate('messages.update_campaign')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="card">
            <div class="card-body">
                <form class="custom-validation" data-ajax="true" id=campaign-form enctype="multipart/form-data">
                    <div class="mb-20">
                        <h3 class="mb-2 fs-18">{{ translate('Update campaign') }}</h3>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-8">
                            <div class="bg-1079801A rounded p-xxl-20 p-3 h-100">
                                @php($language=\App\Models\BusinessSetting::where('key','language')->first())
                                @php($language = $language->value ?? null)
                                @php($defaultLang = str_replace('_', '-', app()->getLocale()))
                                @if($language)
                                    <ul class="nav nav-tabs mb-4">
                                        <li class="nav-item">
                                            <a class="nav-link lang_link active"
                                            href="#"
                                            id="default-link">{{translate('messages.default')}}</a>
                                        </li>
                                        @foreach (json_decode($language) as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link"
                                                    href="#"
                                                    id="{{ $lang }}-link">{{ \App\CentralLogics\Helpers::get_language_name($lang) . '(' . strtoupper($lang) . ')' }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <div class="lang_form" id="default-form">
                                        <div class="form-group mb-2 error-wrapper">
                                            <label class="input-label" for="default_title">{{translate('messages.title')}} ({{translate('messages.default')}})</label>
                                            <input type="text" name="title[]" id="default_title" class="form-control" placeholder="{{translate('messages.new_campaign')}}" value="{{$campaign?->getRawOriginal('title')}}" required>
                                            <span class="text-right text-counting color-A7A7A7 d-block mt-1">75</span>
                                        </div>
                                        <input type="hidden" name="lang[]" value="default">
                                        <div class="form-group mb-2 error-wrapper">
                                            <label class="input-label" maxlength="75" for="exampleFormControlInput1">{{translate('messages.short_description')}} ({{translate('messages.default')}})</label>
                                            <textarea type="text" name="description[]" maxlength="150" class="form-control ckeditor" required>{!! $campaign?->getRawOriginal('description') !!}</textarea>
                                            <span class="text-right text-counting color-A7A7A7 d-block mt-1">150</span>
                                        </div>
                                    </div>
                                    @foreach(json_decode($language) as $lang)
                                        <?php
                                            if(count($campaign['translations'])){
                                                $translate = [];
                                                foreach($campaign['translations'] as $t)
                                                {
                                                    if($t->locale == $lang && $t->key=="title"){
                                                        $translate[$lang]['title'] = $t->value;
                                                    }
                                                    if($t->locale == $lang && $t->key=="description"){
                                                        $translate[$lang]['description'] = $t->value;
                                                    }
                                                }
                                            }
                                        ?>
                                        <div class="d-none lang_form" id="{{$lang}}-form">
                                            <div class="form-group mb-2 error-wrapper">
                                                <label class="input-label" for="{{$lang}}_title">{{translate('messages.title')}} ({{strtoupper($lang)}})</label>
                                                <input type="text" name="title[]" id="{{$lang}}_title" maxlength="75" class="form-control" placeholder="{{translate('messages.new_campaign')}}" value="{{$translate[$lang]['title']??''}}">
                                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">75</span>
                                            </div>
                                            <input type="hidden" name="lang[]" value="{{$lang}}">
                                            <div class="form-group mb-2 error-wrapper">
                                                <label class="input-label" for="exampleFormControlInput1">{{translate('messages.short_description')}} ({{strtoupper($lang)}})</label>
                                                <textarea type="text" name="description[]" maxlength="150" class="form-control ckeditor">{!! $translate[$lang]['description']??'' !!}</textarea>
                                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">150</span>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                <div id="default-form">
                                    <div class="form-group mb-2 error-wrapper">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.title')}} ({{ translate('messages.default') }})</label>
                                        <input type="text" name="title[]" class="form-control" placeholder="{{translate('messages.new_campaign')}}" value="{{$campaign['title']}}" maxlength="75">
                                        <span class="text-right text-counting color-A7A7A7 d-block mt-1">75</span>
                                    </div>
                                    <input type="hidden" name="lang[]" value="en">
                                    <div class="form-group mb-2 error-wrapper">
                                        <label class="input-label" for="exampleFormControlInput1">{{translate('messages.short_description')}}</label>
                                        <textarea type="text" name="description[]" class="form-control ckeditor" maxlength="150">{!! $campaign['description'] !!}</textarea>
                                        <span class="text-right text-counting color-A7A7A7 d-block mt-1">150</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="bg-1079801A d-center rounded p-xxl-20 p-3 h-100">
                                <div class="error-wrapper">
                                    <div class="form-group mb-0 h-100 d-grid align-items-center">
                                        <div>
                                            <div class="mb-15 text-center">
                                                <h4 class="mb-1 fs-14">{{ translate('Campaign image') }}</h4>
                                                <p class="mb-0 fs-12 gray-dark">
                                                    {{translate('Upload your campaign image')}}
                                                </p>
                                            </div>

                                            <div class="upload-file_custom max-w-300px w-100 h-100px">
                                                <input type="file" id="" name="image"
                                                       class="upload-file__input single_file_input"
                                                       accept=".webp, .jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                                <label class="upload-file__wrapper w-100 h-100 m-0">
                                                    <div class="upload-file-textbox text-center">
                                                        <img width="22" class="svg"
                                                             src="{{asset('public/assets/admin/img/document-upload.svg')}}"
                                                             alt="img">
                                                        <h6 class="mt-1 color-656566 fw-medium fs-10 lh-base text-center">
                                                            <span class="theme-clr">Add Image</span>
                                                        </h6>
                                                    </div>
                                                    <img class="upload-file-img" loading="lazy" src="{{$campaign->image_full_url }}" alt=""
                                                    >
                                                </label>
                                                <div class="overlay">
                                                    <div
                                                        class="d-flex gap-1 justify-content-center align-items-center h-100">
                                                        <button type="button"
                                                                class="btn btn-outline-info icon-btn view_btn">
                                                            <i class="tio-invisible"></i>
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-outline-info icon-btn edit_btn">
                                                            <i class="tio-edit"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <p class="mt-3 mb-0 fs-12 gray-dark text-center">
                                                JPEG, JPG, PNG, GIF, WEBP. Less Than 2MB <span class="font-medium text-title">(3:1)</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="bg-1079801A d-center rounded py-lg-4 py-3 px-2">
                                <div class="row g-3 w-100">
                                    <div class="col-lg-12">
                                        <div class="row g-3 mb-3">
                                            <div class="col-sm-6">
                                                <div class="error-wrapper">
                                                    <label class="input-label" for="title">{{translate('messages.start_date')}}</label>
                                                    <input type="date" id="date_from" class="form-control" required name="start_date" value="{{$campaign->start_date->format('Y-m-d')}}">
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="error-wrapper">
                                                    <label class="input-label" for="title">{{translate('messages.end_date')}}</label>
                                                    <input type="date" id="date_to" class="form-control" required="" name="end_date" value="{{$campaign->end_date->format('Y-m-d')}}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="error-wrapper">
                                                    <label class="input-label text-capitalize" for="title">{{translate('messages.daily_start_time')}}</label>
                                                    <input type="time" id="start_time" class="form-control" name="start_time" value="{{$campaign->start_time}}">
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="error-wrapper">
                                                    <label class="input-label text-capitalize" for="title">{{translate('messages.daily_end_time')}}</label>
                                                    <input type="time" id="end_time" class="form-control" name="end_time" value="{{$campaign->end_time}}">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- <div class="col-lg-6">
                            <div class="error-wrapper">
                                <div class="form-group mb-0 h-100 d-flex flex-column">
                                    <label>
                                        {{translate('messages.campaign_image')}}
                                        <small class="text-danger">* ( {{translate('messages.ratio')}} 900x300 )</small>
                                    </label>
                                    <div class="text-center py-3 my-auto">
                                        <img class="initial--4 onerror-image" id="viewer"
                                        src="{{$campaign->image_full_url }}"
                                             data-onerror-image="{{ asset('public/assets/admin/img/900x400/img1.jpg') }}" alt="campaign image"/>
                                    </div>
                                    <div class="custom-file">
                                        <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                               accept=".webp, .jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                        <label class="custom-file-label" for="customFileEg1">{{translate('messages.choose_file')}}</label>
                                    </div>
                                </div>
                            </div>
                        </div> -->
                    </div>
                    <div class="btn--container justify-content-end mt-4 pt-2">
                        <button type="reset" id="reset_btn" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" class="btn btn--primary">{{translate('messages.update')}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script src="{{asset('public/assets/admin')}}/js/view-pages/basic-campaign-edit.js"></script>
    <script>
        "use strict";


        $('#campaign-form').on('submit', function (e) {
            e.preventDefault();

            let $form = $(this);
            if (!$form.valid()) {
                return false;
            }

            var formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{route('admin.campaign.update-basic',[$campaign['id']])}}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function (data) {
                    if (data.errors) {
                        for (var i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    } else {
                        toastr.success('Campaign updated successfully!', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function () {
                            location.href = '{{route('admin.campaign.list', 'basic')}}';
                        }, 2000);
                    }
                }
            });
        });

        $('#reset_btn').click(function(){
            $('#viewer').attr('src','{{asset('storage/app/public/campaign')}}/{{$campaign->image}}');
        })
    </script>
@endpush
