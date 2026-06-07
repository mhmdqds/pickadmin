@extends('layouts.admin.app')

@section('title', translate('messages.vehicle_list'))


@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/rental/veh.png') }}" class="w--22" alt="">
                        </span>
                        <span>{{ translate('messages.vehicle_list') }}</span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <div class="card">
            <div class="card-body">
                <form action="" method="get">
                    <div class="row g-2 mb-20">
                        <div class="col-sm-6 col-md-4">
                            <div class="select-item">
                                <label for="brand-select" class="input-label">{{ translate('messages.brand') }}</label>
                                <select id="brand-select" class="select-30 js-data-example-ajax form-control set-filter opacity-70"
                                        name="brand_id">
                                    <option value="" selected disabled>{{ translate('messages.select_vehicle_brand') }}
                                    </option>
                                    @foreach($brands as $brand)
                                        <option  value="{{ $brand->id }}" {{request()->brand_id == $brand->id ? 'selected' : ''}}>{{ $brand->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="select-item">
                                <label for="category-select" class="input-label">{{ translate('messages.category') }}</label>
                                <select id="category-select" class="js-data-example-ajax form-control set-filter opacity-70"
                                        name="category_id">
                                    <option value="" selected disabled>{{ translate('messages.select_vehicle_category') }}
                                    </option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{request()->category_id == $category->id ? 'selected' : ''}}>{{ $category->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="select-item">
                                <label for="type-select" class="input-label">{{ translate('messages.type') }}</label>
                                <select id="type-select" class="js-data-example-ajax form-control set-filter opacity-70"
                                        name="vehicle_type">
                                    <option value="" selected disabled>
                                        {{ translate('messages.select_vehicle_type') }}
                                    </option>
                                    <option value="family" {{request()->vehicle_type == 'family' ? 'selected' : ''}}>{{ translate('messages.family') }}</option>
                                    <option value="luxury" {{request()->vehicle_type == 'luxury' ? 'selected' : ''}}>{{ translate('messages.Luxury') }}</option>
                                    <option value="affordable" {{request()->vehicle_type == 'affordable' ? 'selected' : ''}}>{{ translate('messages.Affordable') }}</option>
                                    <option value="executives" {{request()->vehicle_type == 'executives' ? 'selected' : ''}}>{{ translate('messages.Executives') }}</option>
                                    <option value="compact" {{request()->vehicle_type == 'compact' ? 'selected' : ''}}>{{ translate('messages.Compact') }}</option>
                                    <option value="full-size" {{request()->vehicle_type == 'full-size' ? 'selected' : ''}}>{{ translate('messages.Full-Size') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="btn--container justify-content-end mt-4">
                                <button type="reset" id="reset_btn"
                                        class="btn btn--reset min-w-120px">{{ translate('messages.reset') }}</button>
                                <button type="submit"
                                        class="btn btn--primary min-w-120px">{{ translate('messages.filter') }}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title text--title">
                        {{ translate('messages.Total_Vehicles') }}
                        <span class="badge badge-soft-dark ml-2 rounded-circle" id="itemCount">{{ $vehicles->total() }}</span>
                    </h5>
                    <form class="search-form flex-grow-1 max-w-353px">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}"
                                   name="search" class="form-control"
                                   placeholder="{{ translate('Search by name, owner info...') }}"
                                   aria-label="{{ translate('messages.Search by name, owner info...') }}">
                            <button type="submit" class="btn btn--secondary bg--primary"><i
                                    class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form>
                    @if (request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base"
                                data-url="{{ url()->full() }}">{{ translate('messages.reset') }}</button>
                    @endif


                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40 font-semibold"
                           href="javascript:;"
                           data-hs-unfold-options='{
                    "target": "#usersExportDropdown",
                    "type": "css-animation"
                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item"
                               href="{{ route('admin.rental.provider.vehicle.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                               href="{{ route('admin.rental.provider.vehicle.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                     alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                    <a class="btn btn--primary font-weight-bold float-right mr-2 mb-0"
                       href="{{ route('admin.rental.provider.vehicle.create') }}">{{ translate('messages.new_vehicle') }}
                    </a>
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="columnSearchDatatable"
                       class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table"
                       data-hs-datatables-options='{
                        "order": [],
                        "orderCellsTop": true,
                        "paging":false

                    }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{ translate('sl') }}</th>
                        <th class="border-0">{{ translate('messages.Vehicle_Info') }}</th>
                        <th class="border-0">{{ translate('messages.Provider') }}</th>
                        <th class="border-0">{{ translate('messages.Category') }}</th>
                        <th class="border-0">{{ translate('messages.Brand') }}</th>
                        <th class="border-0">{{ translate('messages.Total_Trip') }}</th>
                        <th class="border-0">{{ translate('messages.Trip Fair') }}</th>
                        <th class="text-center border-0">{{ translate('messages.New_Tag') }}</th>
                        <th class="text-center border-0">{{ translate('messages.Status') }}</th>
                        <th class="text-center border-0">{{ translate('messages.Action') }}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($vehicles as $key => $vehicle)
                        <tr>
                            <td>{{$key+$vehicles->firstItem()}}</td>
                            <td>
                                <div class="text--title">
                                    <a href="{{ route('admin.rental.provider.vehicle.details', $vehicle->id)}}?vehicle_list=true" class="font-medium">
                                        {{ $vehicle->name }}
                                    </a>
                                    <div class="opacity-lg">
                                        {{ $vehicle->model }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text--title">
                                    <a href="{{ route('admin.rental.provider.details', $vehicle->provider_id)}}" class="font-medium">
                                        {{ $vehicle?->provider?->name ?? translate('provider_not_found') }}
                                    </a>
                                    <div class="opacity-lg">
                                        {{ $vehicle?->provider?->email }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text--title font-medium">
                                    {{ $vehicle?->category?->name }}
                                </div>
                            </td>
                            <td>
                                <div class="text--title font-medium">
                                    {{ $vehicle?->brand?->name }}
                                </div>
                            </td>
                            <td>
                                <div class="text--title font-medium">
                                    {{ count($vehicle->tripDetails) }}
                                </div>
                            </td>
                            <td>
                                <div class="text--title">
                                    @if($vehicle->trip_hourly)
                                        <div>
                                            <span class="opacity-lg">{{translate('Hourly')}}: </span>
                                            <span class="font-semibold">{{\App\CentralLogics\Helpers::format_currency($vehicle['hourly_price'])}}</span>
                                        </div>
                                    @endif
                                    @if($vehicle->trip_distance)
                                        <div>
                                            <span class="opacity-lg">{{translate('Distance Wise')}}: </span>
                                            <span class="font-semibold">{{\App\CentralLogics\Helpers::format_currency($vehicle['distance_price'])}}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckboxNew{{$vehicle->id}}">
                                        <input type="checkbox" data-url="{{route('admin.rental.provider.vehicle.new-tag',[$vehicle['id'],$vehicle->new_tag?0:1])}}"
                                               class="toggle-switch-input redirect-url" id="stocksCheckboxNew{{$vehicle->id}}" {{$vehicle->new_tag?'checked':''}}>
                                        <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center">
                                    <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$vehicle->id}}">
                                        <input type="checkbox" data-url="{{route('admin.rental.provider.vehicle.status',[$vehicle['id'],$vehicle->status?0:1])}}"
                                               class="toggle-switch-input redirect-url" id="stocksCheckbox{{$vehicle->id}}" {{$vehicle->status?'checked':''}}>
                                        <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{ route('admin.rental.provider.vehicle.details', $vehicle->id)}}?vehicle_list=true"
                                       title="{{ translate('messages.view') }}"><i class="tio-visible-outlined"></i>
                                    </a>
                                    <a class="btn action-btn btn-outline-primary" href="{{ route('admin.rental.provider.vehicle.edit', $vehicle->id)}}"
                                       title="{{ translate('messages.edit_store') }}"><i class="tio-edit"></i>
                                    </a>
                                    <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                       data-id="vehicle-{{$vehicle['id']}}" data-message="{{ translate('Want to delete this vehicle') }}" title="{{translate('messages.delete_vehicle')}}"><i
                                            class="tio-delete-outlined"></i>
                                    </a>

                                </div>
                                <form action="{{route('admin.rental.provider.vehicle.delete',[$vehicle['id']])}}" method="post" id="vehicle-{{$vehicle->id}}">
                                    @csrf @method('delete')
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
            @if(count($vehicles) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $vehicles->appends($_GET)->links() !!}
            </div>
            @if(count($vehicles) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>
@endsection


@push('script_2')
<script src="{{ asset('Modules/Rental/public/assets/js/admin/view-pages/vehicle-list.js') }}"></script>
@endpush
