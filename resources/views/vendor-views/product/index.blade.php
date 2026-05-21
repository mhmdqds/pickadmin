@extends('layouts.vendor.app')

@section('title', translate('messages.add_new_item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/AI/animation/product/ai-sidebar.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
@endpush

@section('content')
    @php($module_type = \App\CentralLogics\Helpers::get_store_data()->module->module_type)
    @php(Config::set('module.current_module_type', $module_type))
    @php($openai_config = \App\CentralLogics\Helpers::get_business_settings('openai_config'))

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/items.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ translate('messages.add_new_item') }}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <form id="item_form" enctype="multipart/form-data" class="validate-form" data-ajax="true">
            <input type="hidden" id="request_type" value="vendor">
            <input type="hidden" id="store_id" value="{{ \App\CentralLogics\Helpers::get_store_id() }}">
            <input type="hidden" id="module_type" value="{{ $module_type }}">

            <div class="row g-2">
                @includeif('admin-views.product.partials._title_and_discription')


                    <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="mb-20">
                                <h3 class="mb-0">{{ translate('Item_Thumbnail') }}
                                    @if (Config::get('module.current_module_type') != 'food')
                                    <span class="text-danger">*</span>
                                    @endif
                                </h3>
                            </div>
                            <div class="__bg-F8F9FC-card d-center p-3">
                                <div class="w-100 py-5">
                                    <div>
                                        <div class="text-center py-2">
                                            <div class="mx-auto text-center">
                                                    @include('admin-views.partials._image-uploader', [
                                                            'id' => 'image-input',
                                                            'name' => 'image',
                                                            'ratio' => '1:1',
                                                            'isRequired' =>Config::get('module.current_module_type') == 'food' ?  false : true,
                                                            'existingImage' => null,
                                                            'imageExtension' => IMAGE_EXTENSION,
                                                            'imageFormat' => IMAGE_FORMAT,
                                                            'maxSize' => MAX_FILE_SIZE,
                                                            'textPosition' => 'bottom',
                                                        ]
                                                    )
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                @include('admin-views.product.partials._product-video')
                @include('admin-views.partials._multiple-image-uploader', [
                    'rootId' => 'product-multiple-image-uploader',
                    'containerId' => 'product-additional-images',
                    'title' => translate('messages.Product Additional Images'),
                    'description' => translate('Upload additional images.') . translate(IMAGE_FORMAT) .' '. translate('Image size : Max') .' ' .MAX_FILE_SIZE. translate('MB (1:1)') ,
                    'fieldName' => 'item_images[]',
                    'maxCount' => 5,
                    'rowHeight' => '120px',
                    'groupClassName' => 'spartan_item_wrapper size--md',
                    'maxSize' => MAX_FILE_SIZE,
                    'placeholderImage' => asset('public/assets/admin/img/400x400/coba-placeholder.png'),
                    'dropFileLabel' => 'Drop Here',
                    'extensionErrorMessage' => translate('messages.please_only_input_png_or_jpg_type_file'),
                    'sizeErrorMessage' => translate('messages.file_size_too_big'),
                    'resetButtonSelector' => '#reset_btn',
                ])

            @includeif('admin-views.product.partials._category_and_general')
                @includeif('admin-views.product.partials._price_and_stock')


                @if ($module_type == 'food')
                    @includeif('admin-views.product.partials._food_variations')
                @else
                    @includeif('admin-views.product.partials._other_variations')
                @endif

                @includeif('admin-views.product.partials._ai_sidebar')

                @if (Config::get('module.current_module_type') == 'ecommerce')
                    @includeIf('admin-views.business-settings.landing-page-settings.partial._meta_data')
                @endif

                <div class="col-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary"
                            id="submit_btn">{{ translate('messages.submit') }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <span id="message-enter-choice-values" data-text="{{ translate('enter_choice_values') }}"></span>

@endsection

@push('script')
@endpush

@push('script_2')
    @include('admin-views.product.partials._shared-script-assets', [
        'moduleType' => $module_type,
        'viewPageScript' => 'public/assets/admin/js/view-pages/vendor/product-index.js',
    ])



    <script>
        "use strict";


        function validateImageSize(inputSelector, imageType = "Image", maxSizeMB = 2) {
            let fileInput = $(inputSelector)[0];
            if (fileInput && fileInput.files.length > 0) {
                let fileSize = fileInput.files[0].size;
                if (fileSize > maxSizeMB * 1024 * 1024) {
                    toastr.error(`${imageType} size should not exceed ${maxSizeMB}MB`, {
                        CloseButton: true,
                        ProgressBar: true
                    });
                    return false;
                }
            }
            return true;
        }

        mod_type = "{{ $module_type }}";

        // INITIALIZATION OF SELECT2
        // =======================================================
        $('.js-select2-custom').each(function() {
            let select2 = $.HSCore.components.HSSelect2.init($(this));
        });
        @include('admin-views.product.partials._shared-variation-builder-script')

        function combination_update() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "POST",
                url: '{{ route('vendor.item.variant-combination') }}',
                data: $('#item_form').serialize() + '&stock={{ $module_data['stock'] }}',
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                    $('#variant_combination').html(data.view);
                    if (data.length < 1) {
                        $('input[name="current_stock"]').attr("readonly", false);
                    }
                }
            });
        }


        // $('#item_form').on('keydown', function(e) {
        //         if (e.key === 'Enter') {
        //         e.preventDefault(); // Prevent submission on Enter
        //         }
        //     });




        $('#brand_id').select2({
            ajax: {
                url: '{{ route('vendor.item.getBrandList') }}',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        page: params.page,
                    };
                },
                processResults: function(data) {
                    return {
                        results: data
                    };
                },
                __port: function(params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });



        let form_submitted = false;
        $('#item_form').on('submit', function(e) {
            e.preventDefault();

            if (form_submitted) return false;
            form_submitted = true;
            $('#submit_btn').prop('disabled', true);

            if (typeof FormValidation != 'undefined' && !FormValidation.validateForm(this)) {
                form_submitted = false;
                $('#submit_btn').prop('disabled', false);
                return false;
            }

            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: '{{ route('vendor.item.store') }}',
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                    if (data.errors) {
                        $('#submit_btn').prop('disabled', false);
                        form_submitted = false;
                        for (let i = 0; i < data.errors.length; i++) {
                            toastr.error(data.errors[i].message, {
                                CloseButton: true,
                                ProgressBar: true
                            });
                        }
                    }
                    if (data.product_approval) {
                        toastr.success(data.product_approval, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('vendor.item.pending_item_list') }}';
                        }, 2000);
                    }
                    if (data.success) {
                        toastr.success(data.success, {
                            CloseButton: true,
                            ProgressBar: true
                        });
                        setTimeout(function() {
                            location.href = '{{ route('vendor.item.list') }}';
                        }, 2000);
                    }
                },
                error: function() {
                    $('#loading').hide();
                    $('#submit_btn').prop('disabled', false);
                    form_submitted = false;
                    toastr.error('{{ translate('messages.something_went_wrong') }}');
                }
            });
        });

        function initImagePicker() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'item_images[]',
                maxCount: 5,
                rowHeight: '176px !important',
                groupClassName: 'spartan_item_wrapper min-w-176px max-w-176px',
                maxFileSize: 1024 * 1024 * 2,
                placeholderImage: {
                    image: "{{ asset('public/assets/admin/img/upload-img.png') }}",
                    width: '176px'
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {
                    setTimeout(function() {
                        let $newInput = $("#coba .spartan_item_wrapper").last();
                        if ($newInput.length) {
                            $newInput[0].scrollIntoView({
                                behavior: "smooth",
                                inline: "end",
                                block: "nearest"
                            });
                        }
                    }, 50);
                },
                onExtensionErr: function(index, file) {
                    toastr.error("{{ translate('messages.please_only_input_png_or_jpg_type_file') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function(index, file) {
                    toastr.error("{{ translate('messages.file_size_too_big') }}", {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        }

        $(function() {
            initImagePicker();
        });

        $('#reset_btn').click(function() {
            $('#category_id').val(null).trigger('change');
            $('#sub-categories').val(null).trigger('change');
            $('#unit').val(null).trigger('change');
            $('#veg').val(0).trigger('change');
            $('#addons').val(null).trigger('change');
            $('#discount_type').val(null).trigger('change');
            $('#choice_attributes').val(null).trigger('change');
            $('#customer_choice_options').empty().trigger('change');
            $('#variant_combination').empty().trigger('change');
            $('#viewer').attr('src', "{{ asset('public/assets/admin/img/upload.png') }}");
            $("#coba").empty();
            initImagePicker();
        })
    </script>
@endpush
