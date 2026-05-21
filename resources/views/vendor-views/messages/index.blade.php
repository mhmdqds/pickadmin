@extends('layouts.vendor.app')

@section('title', 'Messages')

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')

    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">{{ translate('messages.conversation_list') }}</h1>
        </div>
        <!-- End Page Header -->

        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <!-- Card -->
                <div class="card">
                    <div class="card-header border-0">
                        <div class="input-group input---group">
                            <div class="input-group-prepend border-inline-end-0">
                                <span class="input-group-text border-inline-end-0" id="basic-addon1"><i class="tio-search"></i></span>
                            </div>
                            <input type="text" class="form-control border-inline-start-0 pl-1" id="serach" placeholder="{{translate('Search')}}" aria-label="Username"
                                aria-describedby="basic-addon1" autocomplete="off">
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="card-body p-0 initial--11"  id="conversation-list">
                        <div class="border-bottom"></div>
                        @include('vendor-views.messages.data')
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>

             <div class="col-lg-8 col-nd-6" id="view-conversation">
                    <div class="h-100 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div class="empty-conversation-content d-flex flex-column align-items-center gap-3">
                                <img width="128" height="128" src="{{asset('/public/assets/admin/img/icons/empty-conversation.png')}}" alt="public">
                                <h5 class="text-muted">
                                    {{translate('no_conversation_found')}}
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>

        </div>
        <!-- End Row -->
    </div>

@endsection

@push('script_2')
<script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script>
        "use strict";


        let empty_page = `<div class="h-100 d-flex align-items-center justify-content-center">
                        <div class="text-center">
                            <div class="empty-conversation-content d-flex flex-column align-items-center gap-3">
                                <img width="128" height="128" src="{{asset('/public/assets/admin/img/icons/empty-conversation.png')}}" alt="public">
                                <h5 class="text-muted">
                                    {{translate('no_conversation_found')}}
                                </h5>
                            </div>
                        </div>
                    </div>`;

        function viewConvs(url, id_to_active, conv_id, sender_id) {
            $('.customer-list').removeClass('conv-active');
            $('#' + id_to_active).addClass('conv-active');
            let new_url= "{{ route('vendor.message.list') }}" + '?conversation=' + conv_id+ '&user=' + sender_id;
            $.get({
                url: url,
                success: function(data) {
                    window.history.pushState('', 'New Page Title', new_url);
                    $('#view-conversation').html(data.view);
                    conversationList();
                }
            });

        }

        let page = 1;
        $('#conversation-list').scroll(function() {
            if ($('#conversation-list').scrollTop() + $('#conversation-list').height() >= $('#conversation-list')
                .height()) {
                page++;
                loadMoreData(page);
            }
        });

        function loadMoreData(page) {
            $.ajax({
                    url: "{{ route('vendor.message.list') }}" + '?page=' + page,
                    type: "get",
                    beforeSend: function() {

                    }
                })
                .done(function(data) {
                    if (data.html === " ") {
                        return;
                    }
                    $("#conversation-list").append(data.html);
                })
                .fail(function() {
                    alert('server not responding...');
                });
        }

        function fetch_data(page, query) {
            $.ajax({
                url: "{{ route('vendor.message.list') }}" + '?page=' + page + "&key=" + query,
                success: function(data) {
                    $('#conversation-list').empty();
                    $("#conversation-list").append(data.html);
                }
            })
        }

        $(document).on('keyup', '#serach', function() {
            let query = $('#serach').val();
            fetch_data(page, query);
            $('#view-conversation').empty();
            $('#view-conversation').append(empty_page);
        });

    </script>
@endpush
