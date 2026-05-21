@extends('layouts.vendor.app')

@section('title', request()->product_gellary == 1 ? translate('Add item') : translate('Update_item'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
    <link href="{{ asset('public/assets/admin/css/AI/animation/product/ai-sidebar.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('public/assets/admin/css/custom.css')}}">

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
                    <img src="{{ asset('public/assets/admin/img/edit.png') }}" class="w--22" alt="">
                </span>
                <span>
                    {{ request()->product_gellary == 1 ? translate('Add_item') : translate('item_update') }}
                </span>
            </h1>
        </div>

        @if (isset($temp_product) && $temp_product == 1 && $product->note)
            <div class="card-header border-0 align-items-start flex-wrap">
                <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                    <div class="d-flex align-items-center __gap-5px">
                        <h1 class="page-header-title text-danger ">
                            {{ translate('messages.Rejection_Note') }} :
                        </h1>
                        <h3 class="">
                            {{ $product->note }}
                        </h3>
                    </div>
                </div>
            </div>
        @endif
        <!-- End Page Header -->
        <form id="product_form" enctype="multipart/form-data" class="validate-form" data-ajax="true">

            @if (request()->product_gellary == 1)
                @php($route = route('vendor.item.store', ['product_gellary' => request()->product_gellary]))
                @php($product->price = 0)
            @else
                @php($route = route('vendor.item.update', [isset($temp_product) && $temp_product == 1 ? $product['item_id'] : $product['id']]))
            @endif

            <input type="hidden" class="route_url"
                value="{{ $route ?? route('vendor.item.update', [isset($temp_product) && $temp_product == 1 ? $product['item_id'] : $product['id']]) }}">
            <input type="hidden" value="{{ $temp_product ?? 0 }}" name="temp_product">
            <input type="hidden" value="{{ $product['id'] ?? null }}" name="item_id">


            <input type="hidden" id="request_type" value="vendor">
            <input type="hidden" id="store_id" value="{{ \App\CentralLogics\Helpers::get_store_id() }}">
            <input type="hidden" id="module_type" value="{{ $module_type }}">



            <div class="row g-2">

                @includeif('admin-views.product.partials._title_and_discription')

               <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-wrap align-items-center">
                            <div class="mb-20">
                                <h3 class="mb-0">{{ translate('Item_Thumbnail') }}
                                    @if (Config::get('module.current_module_type') != 'food')
                                    <span class="text-danger">*</span>
                                    @endif
                                </h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('Upload additional images.') . translate(IMAGE_FORMAT) .' '. translate('Image size : Max') .' ' .MAX_FILE_SIZE. translate('MB (1:1)')  }}
                                </p>
                            </div>
                            <div class="__bg-F8F9FC-card d-center w-100 p-3">

                                <input type="hidden" id="removedImageKeysInput" name="removedImageKeys" value="">
                                <div class="w-100 py-5">
                                    <div class="">
                                        <div class="text-center py-2">
                                            @include('admin-views.partials._image-uploader', [
                                                    'id' => 'image-input',
                                                    'name' => 'image',
                                                    'ratio' => '1:1',
                                                    'isRequired' =>false,
                                                    'existingImage' => $product['image_full_url'] ?? asset('public/assets/admin/img/upload-img.png') ,
                                                    'imageExtension' => IMAGE_EXTENSION,
                                                    'imageFormat' => IMAGE_FORMAT,
                                                    'maxSize' => MAX_FILE_SIZE,
                                                    ])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('admin-views.product.partials._product-video', ['product' => $product])
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-20">
                                <h3 class="text-dark mb-1">
                                    {{ translate('messages.Product Additional Images') }}
                                </h3>
                                <p class="fs-12 mb-0">
                                    {{ translate('messages.update additional images. JPG, JPEG, PNG Image size : Max 2 MB (1:1)') }}
                                </p>
                            </div>
                            <div class="__bg-F8F9FC-card p-3">
                                <div class="flex-grow-1 mx-auto overflow-x-auto scrollbar-primary">
                                    <div class="identity_documnet_body multiple_coba-img tabs-slide-wrap position-relative">
                                        <div class="tabs-inner pt-1 d-flex gap-3 identity_documnet_wrap" id="coba">

                                            @foreach($product->images as $key => $img)
                                            @php($photo = is_array($img) ? $img : ['img' => $img, 'storage' => 'public'])
                                                <div class="spartan_item_wrapper size--md existing_image" id="existing_image_{{ $key }}">
                                                    <div style="position: relative;">
                                                        <label class="file_upload" style="width: 100%; height: 100px; border: 2px dashed #ddd; border-radius: 3px; cursor: pointer; text-align: center; overflow: hidden; padding: 5px; margin-top: 5px; margin-bottom : 5px; position : relative; display: flex; align-items: center; margin: auto; justify-content: center; flex-direction: column;">
                                                            <div class="spartan_item_loader" data-spartanindexloader="0" style=" position: absolute; width: 100%; height: 100px; background: rgba(255,255,255, 0.7); z-index: 22; text-align: center; align-items: center; margin: auto; justify-content: center; flex-direction: column; display : none; font-size : 1.7em; color: #CECECE"><i class="fas fa-sync fa-spin"></i></div>
                                                            <img class="img--100 rounded border" style="width: 100%; margin: 0px auto; vertical-align: middle;" src="{{ \App\CentralLogics\Helpers::get_full_url('product', $photo['img'] ?? '', $photo['storage']) }}">
                                                            <a href="javascript:void(0)" style="right: 3px; top: 3px; background: transparent; border-radius: 3px; width: 30px; height: 30px; line-height: 30px; text-align: center; text-decoration: none; color: rgb(255, 7, 0); position: absolute !important;" data-key="{{ $key }}"
                                                            data-photo="{{ $photo['img'] }}"
                                                            data-img="{{ $photo['img'] ?? '' }}" class="spartan_remove_row function_remove_img remove-existing-image-btn"><i class="tio-add-to-trash"></i></a>
                                                            </div>
                                                        </label>


                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="arrow-area">
                                            <div class="button-prev align-items-center">
                                                <button type="button"
                                                    class="btn btn-click-prev mr-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                    <i class="tio-chevron-left fs-24"></i>
                                                </button>
                                            </div>
                                            <div class="button-next align-items-center pt-5">
                                                <button type="button"
                                                    class="btn btn-click-next ml-auto border-0 btn-primary rounded-circle fs-12 p-2 d-center">
                                                    <i class="tio-chevron-right fs-24"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                @includeif('admin-views.product.partials._category_and_general')
                @includeif('admin-views.product.partials._price_and_stock')



                <div class="col-lg-12" id="food_variation_section">
                    <div class="variation_wrapper">
                        <div class="outline-wrapper">
                            <div class="card shadow--card-2 border-0 bg-animate">
                                <div class="card-header flex-wrap">
                                    <h5 class="card-title">
                                        <span class="card-header-icon mr-2">
                                            <i class="tio-canvas-text"></i>
                                        </span>
                                        <span>{{ translate('messages.food_variations') }}</span>
                                    </h5>
                                    <div>

                                        <a class="btn text--primary-2" id="add_new_option_button">
                                            {{ translate('add_new_variation') }}
                                            <i class="tio-add"></i>
                                        </a>
                                        @if (isset($openai_config) && data_get($openai_config, 'status') == 1)
                                            <button type="button"
                                                class="btn bg-white text-primary opacity-1 generate_btn_wrapper p-0 mb-2 variation_setup_auto_fill"
                                                id="variation_setup_auto_fill"
                                                data-route="{{ route('admin.product.variation-setup-auto-fill') }}"
                                                data-error="{{ translate('Please provide an item name and description so the AI can generate a suitable food variations.') }}"
                                                data-lang="en">
                                                <div class="btn-svg-wrapper">
                                                    <img width="18" height="18" class=""
                                                        src="{{ asset('public/assets/admin/img/svg/blink-right-small.svg') }}"
                                                        alt="">
                                                </div>
                                                <span class="ai-text-animation d-none" role="status">
                                                    {{ translate('Just_a_second') }}
                                                </span>
                                                <span class="btn-text">{{ translate('Generate') }}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="add_new_option">
                                        @if (isset($product->food_variations) && count(json_decode($product->food_variations, true)) > 0)
                                            @foreach (json_decode($product->food_variations, true) as $key_choice_options => $item)
                                                @if (isset($item['price']))
                                                    @break

                                                @else
                                                    @include(
                                                        'admin-views.product.partials._new_variations',
                                                        [
                                                            'item' => $item,
                                                            'key' => $key_choice_options + 1,
                                                        ]
                                                    )
                                                @endif
                                            @endforeach
                                        @endif
                                    </div>

                                    <!-- Empty Variation -->
                                    @if (!isset($product->food_variations) || count(json_decode($product->food_variations, true)) < 1)
                                        <div id="empty-variation">
                                            <div class="text-center">
                                                <img src="{{ asset('/public/assets/admin/img/variation.png') }}"
                                                    alt="">
                                                <div>{{ translate('No variation added') }}</div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="col-md-12" id="attribute_section">
                    <div class="variation_wrapper">
                        <div class="outline-wrapper">
                            <div class="card shadow--card-2 border-0 bg-animate">
                                <div class="card-header">
                                    <h5 class="card-title">
                                        <span class="card-header-icon"><i class="tio-canvas-text"></i></span>
                                        <span>{{ translate('attribute') }}</span>
                                    </h5>
                                    @if (isset($openai_config) && data_get($openai_config, 'status') == 1)
                                        <button type="button"
                                            class="btn bg-white text-primary opacity-1 generate_btn_wrapper p-0 mb-2 other_variation_setup_auto_fill"
                                            id="other_variation_setup_auto_fill"
                                            data-route="{{ route('admin.product.generate-other-variation-data') }}"
                                            data-error="{{ translate('Please provide an item name and description so the AI can generate a suitable variations.') }}"
                                            data-lang="en">
                                            <div class="btn-svg-wrapper">
                                                <img width="18" height="18" class=""
                                                    src="{{ asset('public/assets/admin/img/svg/blink-right-small.svg') }}"
                                                    alt="">
                                            </div>
                                            <span class="ai-text-animation d-none" role="status">
                                                {{ translate('Just_a_second') }}
                                            </span>
                                            <span class="btn-text">{{ translate('Generate') }}</span>
                                        </button>
                                    @endif
                                </div>
                                <div class="card-body pb-0">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="input-label"
                                                    for="exampleFormControlSelect1">{{ translate('messages.attribute') }}<span
                                                        class="input-label-secondary"></span></label>
                                                <select name="attribute_id[]" id="choice_attributes"
                                                    class="form-control js-select2-custom" multiple="multiple">
                                                    @foreach (\App\Models\Attribute::orderBy('name')->get() as $attribute)
                                                        <option value="{{ $attribute['id'] }}"
                                                            {{ in_array($attribute->id, json_decode($product['attributes'], true)) ? 'selected' : '' }}>
                                                            {{ $attribute['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="customer_choice_options" id="customer_choice_options">
                                                @include('vendor-views.product.partials._choices', [
                                                    'choice_no' => json_decode($product['attributes']),
                                                    'choice_options' => json_decode(
                                                        $product['choice_options'],
                                                        true),
                                                ])
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="variant_combination" id="variant_combination">
                                                @include(
                                                    'vendor-views.product.partials._edit-combinations',
                                                    [
                                                        'combinations' => json_decode(
                                                            $product['variations'],
                                                            true),
                                                        'stock' => $module_data['stock'],
                                                    ]
                                                )
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if (Config::get('module.current_module_type') == 'ecommerce')
                    @includeIf('admin-views.business-settings.landing-page-settings.partial._meta_data', ['item' => $product])
                @endif
                </div>





                <div class="col-12">
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset_btn"
                            class="btn btn--reset">{{ translate('messages.reset') }}</button>
                        <button type="submit" class="btn btn--primary">{{ translate('messages.update') }}</button>
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

        mod_type = "{{ $module_type }}";

        @include('admin-views.product.partials._shared-variation-builder-script', [
            'optionWrapperClass' => '__bg-F8F9FC-card count_div view_new_option mb-2',
        ])



        $(document).ready(function() {
            setTimeout(function() {
                let category = $("#category_id").val();
                let sub_category = '{{ count($product_category) >= 2 ? $product_category[1]->id : '' }}';
                let sub_sub_category = '{{ count($product_category) >= 3 ? $product_category[2]->id : '' }}';
                getRequest('{{ url('/') }}/vendor-panel/item/get-categories?parent_id=' + category +
                    '&&sub_category=' + sub_category, 'sub-categories');
                getRequest('{{ url('/') }}/vendor-panel/item/get-categories?parent_id=' +
                    sub_category + '&&sub_category=' + sub_sub_category, 'sub-sub-categories');
            }, 1000)
        });





        function combination_update() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $.ajax({
                type: "POST",
                url: '{{ route('vendor.item.variant-combination') }}',
                data: $('#product_form').serialize() + '&stock={{ $module_data['stock'] }}',
                beforeSend: function() {
                    $('#loading').show();
                },
                success: function(data) {
                    $('#loading').hide();
                    $('#variant_combination').html(data.view);
                    if (data.length < 1) {
                        $('input[name="current_stock"]').attr("readonly", false);
                        $('input[name="current_stock"]').val(0);
                    }
                    update_qty();
                }
                // success: function(data) {
                //     $('#loading').hide();
                //     $('#variant_combination').html(data.view);
                //     if (data.length > 1) {
                //         $('#quantity').hide();
                //     } else {
                //         $('#quantity').show();
                //     }
                // }
            });
        }


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



        $('#product_form').on('submit', function(e) {
            e.preventDefault();
            if(typeof FormValidation != 'undefined' && !FormValidation.validateForm(this)) {
                return false;
            }

            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $('.route_url').val(),
                data: $('#product_form').serialize(),
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
                }
            });
        });

      function initImagePicker() {

             let existingImages = $("#coba .existing_image").detach();

            let newCoba = $('<div class="tabs-inner pt-1 d-flex gap-3 identity_documnet_wrap" id="coba"></div>');

            $("#coba").replaceWith(newCoba);

            newCoba.append(existingImages);

            let existingCount = existingImages.length;
            let maxCount = 5 - existingCount;
            console.log('Existing: ' + existingCount + ', Max: ' + maxCount);

            if (maxCount > 0) {
                $("#coba").spartanMultiImagePicker({
                    fieldName: 'item_images[]',
                    maxCount: maxCount,
                    rowHeight: '100px',
                    groupClassName: 'spartan_item_wrapper size--md',
                    maxFileSize: {{ MAX_FILE_SIZE }} * 1024 * 1024,
                    placeholderImage: {
                        image: '{{asset('public/assets/admin/img/400x400/coba-placeholder.png')}}',
                        width: '100%'
                    },
                    dropFileLabel: "Drop Here",
                    onAddRow: function (index, file) {
                        // Handle logic after adding new image if needed
                    },
                    onRenderedPreview: function (index) {

                    },
                    onRemoveRow: function (index) {

                    },
                    onExtensionErr: function (index, file) {
                        toastr.error('Please only input png or jpg type file', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    },
                    onSizeErr: function (index, file) {
                        toastr.error('File size too big', {
                            CloseButton: true,
                            ProgressBar: true
                        });
                    }
                });
            }
        }

        $(function() {
            initImagePicker();
        });

        $(document).on('change', '.combination_update', function() {
            combination_update();
        });
        function removeOption(e) {
            element = $(e);
            element.parents('.view_new_option').remove();
            combination_update();
        }
        function deleteRow(e) {
            element = $(e);
            element.parents('.add_new_view_row_class').remove();
            combination_update();
        }

        update_qty();

        function update_qty() {
            let total_qty = 0;
            let qty_elements = $('input[name^="stock_"]');
            for (let i = 0; i < qty_elements.length; i++) {
                total_qty += parseInt(qty_elements.eq(i).val() || 0);
            }
            if (qty_elements.length > 0) {

                $('input[name="current_stock"]').attr("readonly", true);
                $('input[name="current_stock"]').val(total_qty);
            } else {
                $('input[name="current_stock"]').attr("readonly", false);
            }
        }

        $(document).on('keyup', 'input[name^="stock_"]', function() {
            let total_qty = 0;
            let qty_elements = $('input[name^="stock_"]');
            for (let i = 0; i < qty_elements.length; i++) {
                total_qty += parseInt(qty_elements.eq(i).val() || 0);
            }
            $('input[name="current_stock"]').val(total_qty);
        });

        $(document).on('click', '.remove-existing-image-btn', function(){
            let key = $(this).data('key');
            let img = $(this).data('img');
            $('#existing_image_' + key).remove();
            $('form').append('<input type="hidden" name="delete_item_image[]" value="' + img + '">');
            initSpatanImagePicker();
        });
    </script>
@endpush
