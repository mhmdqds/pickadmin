@extends('layouts.admin.app')

@section('title',translate('vehicle Bulk Import'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="{{ asset('public/assets/admin/css/tags-input.min.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/items.png')}}" class="w--22" alt="">
                </span>
                <span>
                    {{translate('messages.vehicle_bulk_import')}}
                </span>
            </h1>
        </div>
        <!-- Content Row -->
        <div class="card">
            <div class="card-body">
                <div class="export-steps-2">
                    <div class="row g-4">
                        <div class="col-sm-6 col-lg-4">
                            <div class="export-steps-item-2 h-100">
                                <div class="top">
                                    <div>
                                        <h3 class="fs-20">{{translate('Step 1')}}</h3>
                                        <div>
                                            {{translate('Download_Excel_File')}}
                                        </div>
                                    </div>
                                    <img src="{{asset('/public/assets/admin/img/bulk-import-1.png')}}" alt="">
                                </div>
                                <h4>{{ translate('Instruction') }}</h4>
                                <ul class="m-0 pl-4">
                                    <li>
                                        {{ translate('Download_the_format_file_and_fill_it_with_proper_data.') }}
                                    </li>
                                    <li>
                                        {{ translate('You_can_download_the_example_file_to_understand_how_the_data_must_be_filled.') }}
                                    </li>
                                    <li>
                                        {{ translate('Have_to_upload_excel_file.') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="export-steps-item-2 h-100">
                                <div class="top">
                                    <div>
                                        <h3 class="fs-20">{{translate('Step 2')}}</h3>
                                        <div>
                                            {{translate('Match_Spread_sheet_data_according_to_instruction')}}
                                        </div>
                                    </div>
                                    <img src="{{asset('/public/assets/admin/img/bulk-import-2.png')}}" alt="">
                                </div>
                                  <h4>{{ translate('Instruction') }}</h4>
                                <ul class="m-0 pl-4">
                                    <li>
                                        {{ translate('Download the format file and fill it with proper data.') }}
                                    </li>
                                    <li>
                                        {{ translate('You can download the example file to understand how the data must be filled.') }}
                                    </li>
                                    <li>
                                        {{ translate('Have to upload zip file') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg-4">
                            <div class="export-steps-item-2 h-100">
                                <div class="top">
                                    <div>
                                        <h3 class="fs-20">{{translate('Step 3')}}</h3>
                                        <div>
                                            {{translate('Validate data and complete import')}}
                                        </div>
                                    </div>
                                    <img src="{{asset('/public/assets/admin/img/bulk-import-3.png')}}" alt="">
                                </div>
                                  <h4>{{ translate('Instruction') }}</h4>
                                <ul class="m-0 pl-4">
                                    <li>
                                       {{ translate('Download the format file and fill it with proper data.') }}
                                    </li>
                                    <li>
                                       {{ translate('You can download the example file to understand how the data must be filled.') }}
                                    </li>
                                    <li>
                                       {{ translate('Have to upload zip file') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center pb-4">
                    <h3 class="mb-3 export--template-title font-regular">{{translate('download_spreadsheet_template')}}</h3>
                    <div class="btn--container justify-content-center export--template-btns">
                        <a href="{{asset('public/assets/vehicle_bulk_format.xlsx')}}" download="" class="btn btn--primary btn-outline-primary">{{translate('With Current Data')}}</a>
                        <a href="{{asset('public/assets/vehicle_bulk_format_nodata.xlsx')}}" download="" class="btn btn--primary">{{translate('Without Any Data')}}</a>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <form class="product-form" id="import_form" action="" method="POST"
                enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="button" id="btn_value">
            <div class="card mt-2 rest-part">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <h5 class="text-capitalize mb-3">{{ translate('Select_Data_Upload_type') }}</h5>
                            <div class="module-radio-group border rounded">
                                <label class="form-check form--check">
                                    <input class="form-check-input "   value="import" type="radio" name="upload_type" checked>
                                    <span class="form-check-label py-20">
                                        {{ translate('Upload_New_Data') }}
                                    </span>
                                </label>
                                <label class="form-check form--check">
                                    <input class="form-check-input " value="update" type="radio" name="upload_type">
                                    <span class="form-check-label py-20">
                                        {{ translate('Update_Existing_Data') }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <h5 class="text-capitalize mb-3">{{ translate('Import_vehicle_file') }}</h5>
                            <label class="uploadDnD d-block">
                                <div class="form-group inputDnD input_image input_image_edit position-relative">
                                    <div class="upload-text">
                                        <div>
                                            <img src="{{asset('/public/assets/admin/img/bulk-import-3.png')}}" alt="">
                                        </div>
                                        <div class="filename">{{translate('Must_be_Excel_files_using_our_Excel_template_above')}}</div>
                                    </div>
                                    <input type="file" name="products_file" class="form-control-file text--primary font-weight-bold action-upload-section-dot-area" id="products_file">
                                </div>
                            </label>

                        </div>
                    </div>

                    <div class="btn--container justify-content-end mt-3">
                        <button id="reset_btn" type="reset"
                                data-alert="{{translate('Must_be_Excel_files_using_our_Excel_template_above')}}"
                                class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="button"
                                class="btn btn--primary update_or_import"
                                data-no="{{ translate('no') }}"
                                data-yes="{{ translate('yes') }}"
                                data-title="{{ translate('Are you sure?') }}"
                                data-desc="{{ translate('You_want_to_') }}"
                                data-text="{{ translate(' Data') }}">
                                {{translate('messages.Upload')}}
                        </button>
                    </div>



                </div>
            </div>
        </form>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin') }}/js/tags-input.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/view-pages/product-import.js"></script>
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/vehicle-bulk-import.js') }}"></script>
@endpush
