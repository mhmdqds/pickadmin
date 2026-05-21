@extends('layouts.admin.app')

@section('title', translate('Product_Gallery'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center g-2">
                <div class="col-md-9 col-12">
                    <h1 class="page-header-title">
                        <span class="page-header-icon w-40px">
                            <img src="{{ asset('public/assets/admin/img/group.png') }}" class="w-100 h-100" alt="">
                        </span>
                        <span>
                            <span>
                                {{ translate('messages.Product_Gallery') }} <span class="badge badge-soft-dark ml-2"
                                    id="foodCount">{{ $items->total() }}</span>
                            </span> <br>
                            <span class="color-484848 fs-12">
                                {{ translate('messages.Search product and use its info to create your food item') }}
                            </span>
                        </span>
                    </h1>
                </div>
            </div>

        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card mb-20">
            <!-- Header -->
            <div class="card-body border-0">
                <form  class="search-form">
                    <input type="hidden" value="1" name="product_gallery">
                    <div class="d-lg-flex align-items-center gap-3 flex-lg-nowrap flex-wrap">
                        <div class="flex-grow-1">
                            <div class="row g-2">
                                <div class="col-sm-6 col-md-4 col-lg-4">
                                    <select name="store_id" id="store" data-url="{{ url()->full() }}"
                                        data-placeholder="{{ translate('messages.select_store') }}"
                                        class="js-data-example-ajax form-control store-filter" required title="Select Store"
                                        oninvalid="this.setCustomValidity('{{ translate('messages.please_select_store') }}')">
                                        @if ($store)
                                            <option value="{{ $store->id }}" selected>{{ $store->name }}</option>
                                        @else
                                            <option value="all" selected>{{ translate('messages.all_stores') }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-4 col-lg-4">
                                    <select name="category_id" id="category_id"
                                        data-placeholder="{{ translate('messages.select_category') }}"
                                        class="js-data-example-ajax form-control set-filter" id="category_id"
                                        data-url="{{ url()->full() }}" data-filter="category_id">
                                        @if ($category)
                                            <option value="{{ $category->id }}" selected>{{ $category->name }}</option>
                                        @else
                                            <option value="all" selected>{{ translate('messages.all_category') }}
                                            </option>
                                        @endif
                                    </select>
                                </div>
                                <div class="col-sm-6 col-md-4 col-lg-4">
                                    <input id="" type="search" value="{{ request()?->search ?? null }}"
                                        name="search" class="form-control h--42px"
                                        placeholder="{{ translate('messages.ex_search_name') }}"
                                        aria-label="{{ translate('messages.search_here') }}">
                                </div>
                            </div>
                        </div>
                        <div class="max-w-130px mt-lg-0 mt-3">
                            <button type="submit"
                                class="btn min-w-120px btn--primary w-100 h-100">{{ translate('messages.search') }}</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Header -->
        </div>

        <div class="" id="set-rows">
            @include('admin-views.product.partials._gallery', [$items])
        </div>

        @if (count($items) !== 0)
            <hr>
            <div class="page-area px-4 pb-3">
                {!! $items->withQueryString()->links() !!}
            </div>
        @endif

        @if (count($items) === 0)
            <div class="empty--data">
                <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                <h5>
                    {{ translate('no_data_found') }}
                </h5>
            </div>
        @endif
    </div>
    <!-- End Table -->


@endsection

@push('script_2')
    <script>
        "use strict";


        $('#store').select2({
            ajax: {
                url: '{{ route('admin.store.get-stores') }}',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
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

        $('#category_id').select2({
            ajax: {
                url: '{{ route('admin.category.get-all') }}',
                data: function(params) {
                    return {
                        q: params.term, // search term
                        all: true,
                        module_id: {{ Config::get('module.current_module_id') }},
                        page: params.page
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

        // $('#search-form').on('submit', function(e) {
        //     e.preventDefault();
        //     let formData = new FormData(this);
        //     let queryParams = $(this).serialize();
        //     $.ajaxSetup({
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         }
        //     });

        //     $.post({
        //         url: '{{ route('admin.item.search') }}?' + queryParams,
        //         data: formData,
        //         cache: false,
        //         contentType: false,
        //         processData: false,
        //         beforeSend: function() {
        //             $('#loading').show();
        //         },
        //         success: function(data) {
        //             $('#set-rows').html(data.view);
        //             $('.page-area').hide();
        //             $('#foodCount').html(data.count);
        //         },
        //         complete: function() {
        //             $('#loading').hide();
        //         },
        //     });
        // });



        $(document).on('click', '.data-info-show', function() {
            let id = $(this).data('id');
            let url = $(this).data('url');
            fetch_data(id, url)
        })

        function fetch_data(id, url) {
            $.ajax({
                url: url,
                type: "get",
                beforeSend: function() {
                    $('#data-view').empty();
                    $('#loading').show()
                },
                success: function(data) {
                    $("#data-view").append(data.view);

                    initSelect2Dropdowns();

                },
                complete: function() {
                    $('#loading').hide()
                }
            })
        }

        function initSelect2Dropdowns() {
            $('.offcanvas-close, #offcanvasOverlay').on('click', function() {
                $('.custom-offcanvas').removeClass('open');
                $('#offcanvasOverlay').removeClass('show');
            });
        }
    </script>
@endpush
