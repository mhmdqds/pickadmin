@extends('layouts.admin.app')

@section('title',translate('messages.Create_Reels'))

@push('css_or_js')
<meta name="csrf-token" content="{{ csrf_token() }}">

<script type="text/javascript" src="{{asset('public/assets/admin/js/moment.min.js')}}"></script>
<script type="text/javascript" src="{{asset('public/assets/admin/js/daterangepicker.min.js')}}"></script>
@endpush

@section('content')
<div class="content container-fluid">
    <h2 class="fs-20 text-capitalize lh-1 mb-20">
        {{ translate('Create_Reels') }}
    </h2>
    <form action="">
        <div class="card card-body mb-20">
            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                        <label class="form-label" for="">
                            {{ translate('Store') }}
                            <span class="input-label-secondary m-0" title="" data-toggle="tooltip" data-title="{{ translate('Select_Store') }}">
                                <i class="tio-info"></i>
                            </span>
                        </label>
                        <select class="form-control w-100 js-select2-custom store-select" name="">
                            <option value="" selected disabled>{{ translate('Select_Store') }}</option>
                            <option value="">Abc Store</option>
                        </select>
                    </div>
                    <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                        <ul class="nav nav-tabs nav--tabs mt-0 mb-20 ">
                            <li class="nav-item">
                                <a class="nav-link lang_link active" href="#" id="default-link">Default</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link lang_link" href="#" id="en-link">English(EN)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link lang_link" href="#" id="ar-link">Arabic - العربية(AR)</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link lang_link" href="#" id="bn-link">Bengali - বাংলা(BN)</a>
                            </li>
                        </ul>

                        <div class="row align-items-end">
                            <div class="col-md-12 lang_form default-form">
                                <label for="reels_des" class="form-label">{{ translate('Short_Description') }} (Default)
                                    <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                        data-title="{{ translate('Enter_the_short_decscription') }}">
                                        <i class="tio-info text-muted"></i>
                                    </span>
                                </label>
                                <textarea id="reels_des" type="text" class="form-control reel-des-textarea" rows="1" maxlength="200" name="reels_des[]"
                                    placeholder="{{ translate('write short description') }}"></textarea>
                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">0/200</span>
                                <input type="hidden" name="lang[]" value="default">
                            </div>
                            <div class="col-md-12 d-none lang_form" id="en-form">
                                <label for="reels_desen" class="form-label">{{ translate('Short_Description') }} (EN)

                                </label>
                                <textarea id="reels_desen" type="text" class="form-control reel-des-textarea" rows="1" maxlength="200" name="reels_des[]"
                                    placeholder="{{ translate('write short description') }}"></textarea>
                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">0/200</span>
                                <input type="hidden" name="lang[]" value="en">
                            </div>
                            <div class="col-md-12 d-none lang_form" id="ar-form">
                                <label for="reels_desar" class="form-label">{{ translate('Short_Description') }} (AR)

                                </label>
                                <textarea id="reels_desar" type="text" class="form-control reel-des-textarea" rows="1" maxlength="200" name="reels_des[]"
                                    placeholder="{{ translate('write short description') }}"></textarea>
                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">0/200</span>
                                <input type="hidden" name="lang[]" value="ar">
                            </div>
                            <div class="col-md-12 d-none lang_form" id="bn-form">
                                <label for="reels_desbn" class="form-label">{{ translate('Short_Description') }} (BN)

                                </label>
                                <textarea id="reels_desbn" type="text" class="form-control reel-des-textarea" rows="1" maxlength="200" name="reels_des[]"
                                    placeholder="{{ translate('write short description') }}"></textarea>
                                <span class="text-right text-counting color-A7A7A7 d-block mt-1">0/200</span>
                                <input type="hidden" name="lang[]" value="bn">
                            </div>
                        </div>
                    </div>
                    <div class="bg-light p-3 p-xxl-4 rounded mb-20">
                        <div class="row g-4">

                            <!-- Thumbnail Upload -->
                            <div class="col-lg-6">
                                <div class="reel-upload-box-wrapper text-center">
                                    <label class="form-label fs-12 text-muted mb-20">
                                        {{ translate('Upload Thumbnail Image') }}
                                    </label>
    
                                    <div class="reel-upload-box" 
                                        data-type="image" 
                                        data-max-size="2"
                                        data-ratio="9:16">
    
                                        <input type="file" hidden accept="image/*">
    
                                        <div class="upload-placeholder text-center">
                                            <img src="{{ asset('public/assets/admin/img/reels/img-icon.png') }}" alt="">
                                            <div>
                                                <span class="text-info">{{ translate('Click to upload') }}</span>
                                                <br>
                                                {{ translate('or drag and drop') }}
                                            </div>
                                        </div>
                                        <div class="upload-wrapper img-upload-wrapper">
                                            <img src="" alt="">
                                            <button type="button" class="btn remove-btn">
                                                <i class="tio-clear"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <p class="mt-3">
                                        {{ translate('JPG, JPEG, PNG Image') }}
                                        <br>
                                        {{ translate('Size : Max 2 MB (9:16 )') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Video Upload -->
                            <div class="col-lg-6">
                                <div class="reel-upload-box-wrapper text-center">
                                    <label class="form-label fs-12 text-muted mb-3">
                                        {{ translate('Upload a file') }}
                                    </label>
    
                                    <div class="reel-upload-box" 
                                        data-type="video" 
                                        data-max-size="15">
    
                                        <input type="file" hidden accept="video/*">
    
                                        <div class="upload-placeholder text-center">
                                            <img src="{{ asset('public/assets/admin/img/reels/video-icon.png') }}" alt="">
                                            <div>
                                                <span class="text-info">{{ translate('Add Video') }}</span>
                                            </div>
                                        </div>
    
                                        <div class="upload-wrapper reel-upload-wrapper">
                                            <div class="img-wrapper">
                                                <img src="" alt="">
                                                <div class="reels-play-btn">
                                                    <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                                        <i class="tio-play"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <h6 class="fs-10 fw-medium mb-0 reel-title">Biriyani.mp4</h6>
                                            <p class="fs-10 mt-2 mb-0"><span class="reel-type">Mp4</span> <span class="reel-size">15 Mb</span></p>
                                            <button type="button" class="btn remove-btn">
                                                <i class="tio-clear"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <p class="mt-3">
                                        {{ translate('Mp4, MOV, 3GP, GIF') }}
                                        <br>
                                        {{ translate('Size: video 15 MB (9:16 Recommended)') }}
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="bg-light p-3 p-xxl-4 rounded"> 
                        <div class="d-flex gap-2 justify-content-between flex-wrap mb-2">
                            <label class="form-label mb-0" for="">
                                {{ translate('Store_Validity') }}
                                <span class="input-label-secondary m-0" title="" data-toggle="tooltip" data-title="{{ translate('Select_Store_Validity') }}">
                                    <i class="tio-info"></i>
                                </span>
                            </label>
                            <label for="" class="form-label d-flex gap-2 align-items-center mb-0">
                                {{ translate('Always Visible to Customers') }}
                                <input type="checkbox">
                            </label>
                        </div>
                        <div class="position-relative">
                            <i class="tio-calendar fs-16 icon-absolute-on-right"></i>
                            <input type="text"
                                class="form-control h-45 position-relative bg-transparent" name="dates" min="{{date('Y-m-d',strtotime(now()))}}"
                                value="{{ old('dates') }}" placeholder="{{ translate('messages.select_reels_duration') }}" required autocomplete="off">
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="bg-light p-3 p-xxl-4 rounded">
                        <h4 class="fw-medium mb-3">{{ translate('Reel_Preview') }}</h4>
                        <div class="reel-preview-box">
                            <video src="" controls class="reels-video"></video>
                            <div class="reel-overlay">
                                <button type="button" class="btn reels-play-btn">
                                    <div class="d-flex justify-content-center align-items-center w-100 h-100">
                                        <i class="tio-play"></i>
                                    </div>
                                </button>
                            </div>
                            <div class="reel-des-wrapper">
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <div class="reel-preview-thumbnail" data-reel-thumbnail="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"></div>
                                    <div class="thumbnail-placeholder"></div>
                                    <div class="reel-preview-title" data-reel-title="Demo Title"></div>
                                    <div class="title-placeholder"></div>
                                </div>
                                <div class="reel-preview-des"></div>
                                <div class="des-placeholder">
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
            <button type="reset" id="resetBtn" class="btn btn--reset min-w-120">{{ translate('Reset') }}</button>
            <button type="submit" class="btn btn-primary min-w-120">{{ translate('Filter') }}</button>
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script src="{{asset('public/assets/admin')}}/js/reel/reel-upload.js"></script>
<script>
    "use strict";

    $(function() {
        $('input[name="dates"]').daterangepicker({
            drops: 'down',
            opens: 'right',
            startDate: moment().startOf('day'),
            endDate: moment().endOf('day'),
            minDate: new Date(),
            autoUpdateInput: false,
            autoApply: false,
            alwaysShowCalendars: true,
            locale: {
                format: 'MM/DD/YY',
                cancelLabel: 'Clear'
            },
        });

        $('input[name="dates"]').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(
                picker.startDate.format('MM/DD/YY') + ' - ' + picker.endDate.format('MM/DD/YY')
            );
        });

        $('input[name="dates"]').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        $('input[name="dates"]').on('hide.daterangepicker', function(ev, picker) {
            if (!picker.autoApply) {
                picker.show();
            }
        });
    });
</script>
@endpush
