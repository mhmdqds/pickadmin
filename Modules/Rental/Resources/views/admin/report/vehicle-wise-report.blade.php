@extends('layouts.admin.app')

@section('title',translate('messages.vehicle_report'))

@push('css_or_js')

@endpush

@section('content')

    @php
        $from = session('from_date');
        $to = session('to_date');
    @endphp
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/report.png')}}" class="w--22" alt="">
                </span>
                <span>
                    {{translate('messages.vehicle_report')}}
                    @if (isset($filter) && $filter != 'all_time')
                    <span class="mb-0 h6 badge badge-soft-success ml-2"
                        id="itemCount">( {{ session('from_date') }} - {{ session('to_date') }} )</span>
                        @endif
                </span>
            </h1>
        </div>
        <!-- End Page Header -->

        <div class="card mb-20">
            <div class="card-body">
                <h4 class="">{{translate('Search Data')}}</h4>
                <form action="{{ route('admin.transactions.rental.report.set-date') }}" method="post">
                    @csrf
                <div class="row g-3">
                    <div class="col-sm-6 col-md-3">
                        <select name="zone_id" class="form-control js-select2-custom set-filter" data-url="{{ url()->full() }}" data-filter="zone_id" id="zone">
                    <option value="all">{{ translate('messages.All_Zones') }}</option>
                    @foreach(\App\Models\Zone::orderBy('name')->get() as $z)
                        <option
                            value="{{$z['id']}}" {{isset($zone) && $zone->id == $z['id']?'selected':''}}>
                            {{$z['name']}}
                        </option>
                    @endforeach
                </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <select name="provider_id" id="provider_id"

                        data-get-provider-url="{{route('admin.store.get-providers')}}"
                        data-zone-id="{{ isset($zone) ? $zone->id : '' }}"
                        data-module-id="{{ request('module_id') ?? '' }}"

                        data-placeholder="{{translate('messages.select_provider')}}" class="js-data-example-ajax form-control set-filter" data-url="{{ url()->full() }}" data-filter="provider_id" >
                            @if(isset($provider))
                            <option value="{{$provider->id}}" selected>{{$provider->name}}</option>
                            @else
                            <option value="all" selected>{{translate('messages.all_providers')}}</option>
                            @endif
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <select name="category_id"

                        data-get-category-url="{{route('admin.rental.category.get-categories')}}"
                        data-zone-id="{{ isset($zone) ? $zone->id : '' }}"
                        data-module-id="{{ request('module_id') ?? '' }}"

                        class="js-data-example-ajax form-control set-filter" data-url="{{ url()->full() }}" data-filter="category_id"  id="category_id">
                        @if(isset($category))
                        <option value="{{$category->id}}" selected>{{$category->name}}</option>
                        @else
                        <option value="all" selected>{{ translate('messages.All Categories') }}</option>
                        @endif
                    </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <select class="form-control set-filter" data-url="{{ url()->full() }}" data-filter="filter"  name="filter">
                            <option value="all_time" {{ isset($filter) && $filter == "all_time" ? 'selected' : '' }}>{{ translate('messages.All Time') }}</option>
                            <option value="this_year" {{ isset($filter) && $filter == "this_year" ? 'selected' : '' }}>{{ translate('messages.This Year') }}</option>
                            <option value="previous_year" {{ isset($filter) && $filter == "previous_year" ? 'selected' : '' }}>{{ translate('messages.Previous Year') }}</option>
                            <option value="this_month" {{ isset($filter) && $filter == "this_month" ? 'selected' : '' }}>{{ translate('messages.This Month') }}</option>
                            <option value="this_week" {{ isset($filter) && $filter == "this_week" ? 'selected' : '' }}>{{ translate('messages.This Week') }}</option>
                            <option value="custom" {{ isset($filter) && $filter == 'custom' ? 'selected' : '' }}>
                                {{ translate('messages.Custom') }}</option>
                        </select>
                    </div>
                    @if (isset($filter) && $filter == 'custom')
                    <div class="col-sm-6 col-md-3">

                            <input type="date" name="from" id="from_date" class="form-control" placeholder="{{ translate('Start Date') }}" {{session()->has('from_date')?'value='.session('from_date'):''}} required>

                    </div>
                    <div class="col-sm-6 col-md-3">

                            <input type="date" name="to" id="to_date" class="form-control" placeholder="{{ translate('End Date') }}" {{session()->has('to_date')?'value='.session('to_date'):''}} required>

                    </div>
                    @endif
                    <div class="col-sm-6 col-md-3 ml-auto">
                        <button type="submit" class="btn btn-primary btn-block h--45px">{{translate('Filter')}}</button>
                    </div>
                </div>
            </form>
            </div>
        </div>
        <!-- Card -->
        <div class="row card mt-4">
            <!-- Header -->
            <div class="card-header border-0 py-2">
                <div class="search--button-wrapper">
                    <h3 class="card-title">
                        {{translate('Vehicle report table')}}<span class="badge badge-soft-secondary" id="countItems">{{ $vehicles->total() }}</span>
                    </h3>
                    <form class="search-form">
                    <!-- Search -->
                    <div class="input--group input-group">
                        <input id="datatableSearch" name="search" type="search" class="form-control" placeholder="{{translate('ex_:_search_vehicle_name')}}" value="{{ request()?->search ?? null}}" aria-label="{{translate('messages.search_here')}}">
                        <button type="button" class="btn btn--secondary"><i class="tio-search"></i></button>
                    </div>
                    <!-- End Search -->
                    </form>
                    @if(request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif<!-- Unfold -->
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
                            <a id="export-excel" class="dropdown-item" href="{{route('admin.transactions.rental.report.vehicle-wise-export', ['type'=>'excel',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.transactions.rental.report.vehicle-wise-export', ['type'=>'csv',request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>
                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
                <!-- End Row -->
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom" id="table-div">
                <table id="datatable" class="table table-borderless table-thead-bordered table-nowrap card-table">
                    <thead class="thead-light">
                    <tr>
                        <th>{{translate('sl')}}</th>
                        <th class="w--2">{{translate('messages.Vehicle Info')}}</th>
                        <th>{{translate('messages.Number of Vehicles')}}</th>
                        <th class="w--2">{{translate('messages.provider')}}</th>
                        <th>{{translate('messages.hourly_rate')}}</th>
                        <th>{{translate('messages.distance_wise_rate')}}</th>
                        <th>{{translate('messages.total_trip_count')}}</th>
                        <th>{{translate('messages.total_trip_vehicles')}}</th>
                        <th>{{translate('messages.total_trip_amount')}}</th>
                        <th>{{translate('messages.total_discount_given')}}</th>
                        <th>{{translate('messages.Average Trip Value')}}</th>
                        <th>{{translate('messages.average_ratings')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">

                    @foreach($vehicles as $key=>$vehicle)
                        <tr>
                            <td>{{$key+$vehicles->firstItem()}}</td>
                            <td>

                                <a class="media align-items-center" href="{{ route('admin.rental.provider.vehicle.details', $vehicle->id)}}">
                                    <img class="avatar avatar-lg mr-3 onerror-image"
                                    src="{{ $vehicle?->thumbnail_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"


                                    data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}" alt="{{$vehicle->name}} image">
                                    <div class="media-body">
                                        <h5 class="text-hover-primary mb-0" title="{{ $vehicle['name'] }}">
                                            {{ strlen($vehicle['name']) > 30 ? substr($vehicle['name'], 0, 30).'...' : $vehicle['name'] }}
                                        </h5>
                                    </div>
                                </a>
                            </td>
                            <td>
                                {{$vehicle->vehicle_identities_count ?? 0}}
                            </td>
                            <td>
                                @if($vehicle->provider)
                                {{Str::limit($vehicle->provider->name,20,'...')}}
                                @else
                                {{translate('messages.provider_deleted')}}
                                @endif
                            </td>
                            <td>
                                {{ \App\CentralLogics\Helpers::format_currency($vehicle->hourly_price) }}
                            </td>
                            <td>
                                {{ \App\CentralLogics\Helpers::format_currency($vehicle->distance_price) }}
                            </td>
                            <td>
                                {{$vehicle->trips_count ?? 0}}
                            </td>
                            <td>
                                {{$vehicle->trip_details_sum_quantity ?? 0}}
                            </td>
                            <td>
                                {{ \App\CentralLogics\Helpers::format_currency($vehicle->trips_sum_price) }}
                            </td>
                            <td>
                                {{ \App\CentralLogics\Helpers::format_currency($vehicle->total_discount) }}
                            </td>
                            <td>
                                {{ $vehicle->trips_count>0? \App\CentralLogics\Helpers::format_currency(($vehicle->trips_sum_price-$vehicle->total_discount)/($vehicle->trip_details_sum_quantity ?? 0) ) :0 }}
                            </td>
                            <td>
                                <div class="rating">
                                    <span><i class="tio-star"></i></span>{{ round($vehicle->avg_rating,1) }} ({{ $vehicle->total_reviews }})
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @if(count($vehicles) !== 0)
            <hr>
            @endif
            <div class="page-area">
                {!! $vehicles->links() !!}
            </div>
            @if(count($vehicles) === 0)
            <div class="empty--data">
                <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                <h5>
                    {{translate('no_data_found')}}
                </h5>
            </div>
            @endif
            </div>
                <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>
@endsection

@push('script')

@endpush

@push('script_2')

    <script src="{{asset('public/assets/admin')}}/vendor/chart.js/dist/Chart.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/vendor/chartjs-chart-matrix/dist/chartjs-chart-matrix.min.js"></script>
    <script src="{{asset('public/assets/admin')}}/js/hs.chartjs-matrix.js"></script>
    <script src="{{ asset('public/assets/admin') }}/js/view-pages/admin-reports.js"></script>
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/vehicle-report.js')}}"></script>

@endpush
