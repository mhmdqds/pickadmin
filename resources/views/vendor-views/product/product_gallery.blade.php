@extends('layouts.vendor.app')

@section('title',translate('messages.Product_Gallery'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
@php
$store_data=\App\CentralLogics\Helpers::get_store_data();
@endphp
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="btn--container align-items-center mb-0">
                <div class="d-flex gap-2">
                    <img class="h--50px"
                        src="{{ asset('public/assets/admin/img/group.png') }}" alt="Product_Gallery">
                    <div>
                        <h1 class="page-header-title"> {{translate('messages.Product_Gallery')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{ $items->total() }}</span></h1>
                    <p>{{ translate('search_product_and_use_its_info_to_create_own_product') }}</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Page Header -->
        <!-- Card -->
        <div class="card mb-3">
            <!-- Header -->
            <div class="card-body border-0">
                <form   class="search-form">

                    <input type="hidden" value="1" name="product_gallery">
                    <div class="d-flex gap-3 align-items-stretch">
                        <div class="flex-grow-1">
                            <input id="datatableSearch" type="search" value="{{  request()?->search ?? null }}" name="search" class="form-control" placeholder="{{translate('messages.ex_search_name')}}" aria-label="{{translate('messages.search_here')}}">
                        </div>
                        <div>
                            <button type="submit" class="btn btn--primary h-45px">{{ translate('messages.search') }}</button>
                        </div>
                    </div>
                </form>
            </div>
            <!-- End Header -->
        </div>
        <!-- End Card -->
        <div>
            <h2>{{ translate('messages.Product_List') }}</h2>
            <p>{{ translate('search_product_and_use_its_info_to_create_own_product') }}</p>
        </div>

                    <div class="" id="set-rows">
                        @include('vendor-views.product.partials._gallery', [
                            $items,
                        ])
                    </div>
                      @if(count($items) !== 0)
            <hr>
            <div class="page-area px-4 pb-3">
                {!! $items->withQueryString()->links() !!}
            </div>
        @endif
                @if(count($items) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
                @endif

            <!-- End Table -->
    </div>

@endsection

@push('script_2')
    <script>
        "use strict";


        $('#category').select2({
            ajax: {
                url: '{{route("vendor.category.get-all")}}',
                data: function (params) {
                    return {
                        q: params.term, // search term
                        all:true,
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                    results: data
                    };
                },
                __port: function (params, success, failure) {
                    let $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });

        // $('#search-form').on('submit', function (e) {
        //     e.preventDefault();
        //     let formData = new FormData(this);
        //     $.ajaxSetup({
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //         }
        //     });
        //     $.post({
        //         url: '{{route('vendor.item.search')}}',
        //         data: formData,
        //         cache: false,
        //         contentType: false,
        //         processData: false,
        //         beforeSend: function () {
        //             $('#loading').show();
        //         },
        //         success: function (data) {
        //             $('#set-rows').html(data.view);
        //             $('#itemCount').html(data.count);
        //             $('.page-area').hide();
        //         },
        //         complete: function () {
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
             $('.offcanvas-close, #offcanvasOverlay').on('click', function () {
                $('.custom-offcanvas').removeClass('open');
                $('#offcanvasOverlay').removeClass('show');
            });
        }
    </script>
@endpush
