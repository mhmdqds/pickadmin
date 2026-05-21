@extends('layouts.vendor.app')

@section('title',translate('Item Bulk Export'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/category.png')}}" class="w--20" alt="">
                </span>
                <span>
                    {{translate('messages.export_items')}}
                </span>
            </h1>
        </div>
        <div class="card mt-2 rest-part">
            <div class="card-body">
                <div class="export-steps">
                    @includeIf('partials._bulk_export_common_instruction')
                    <form class="product-form" action="{{route('vendor.item.bulk-export')}}" method="POST">
                        @csrf
                       @includeIf('partials._bulk_export_common_filter')
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/view-pages/common-import-export.js') }}"></script>
@endpush
