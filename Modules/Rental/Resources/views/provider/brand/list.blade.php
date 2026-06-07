@extends('layouts.vendor.app')

@section('title',translate('messages.brand list'))

@section('content')
    <div class="content container-fluid">
        <div class="card mt-3">
            <div class="card-header py-2 border-0">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.brand_list')}}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$brands->total()}}</span></h5>

                    <form class="search-form" method="get" action="">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input type="search" name="search" value="{{ request()?->search ?? null }}" class="form-control min-height-45" placeholder="{{translate('messages.search_by_brand_name')}}" aria-label="{{translate('messages.ex_:_categories')}}">
                            <button type="submit" class="btn btn--secondary min-height-45"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>
                    @if(request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-brand" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif
                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                           data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item" href="{{ route('vendor.vehicle_brand.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="{{translate('Image Description')}}">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{ route('vendor.vehicle_brand.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                     alt="{{translate('Image Description')}}">
                                {{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive datatable-custom">
                    <table id="columnSearchDatatable"
                           class="table table-borderless table-thead-bordered table-align-middle"
                           data-hs-datatables-options='{
                            "isResponsive": false,
                            "isShowPaging": false,
                            "paging":false,
                        }'>
                        <thead class="thead-light">
                        <tr>
                            <th class="border-0">{{ translate('sl') }}</th>
                            <th class="border-0">{{ translate('messages.brand_id') }}</th>
                            <th class="border-0">{{ translate('messages.brand_image') }}</th>
                            <th class="border-0">{{ translate('messages.brand_name') }}</th>
                        </tr>
                        </thead>

                        <tbody id="table-div">
                        @foreach($brands as $key=>$brand)
                            <tr>
                                <td>{{ $key+$brands->firstItem() }}</td>
                                <td>{{ $brand->id }}</td>
                                <td>
                                    <span class="media align-items-center">
                                    <img class="w-auto h--50px aspect-2-1 rounded onerror-image" src="{{ $brand['image_full_url'] }}" data-onerror-image="{{ $brand['image_full_url'] }}" alt="{{translate('brand image')}}">
                                </span>
                                </td>
                                <td>
                                    <span class="d-block font-size-sm text-body">
                                        {{ Str::limit($brand['name'], 20,'...') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if(count($brands) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $brands->appends($_GET)->links() !!}
            </div>
            @if(count($brands) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
            @endif
        </div>
    </div>
@endsection
