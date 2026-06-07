@extends('layouts.vendor.app')

@section('title', translate('messages.driver'))

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{ asset('public/assets/admin/img/banner.png') }}" class="w--26" alt="">
                </span>
                <span>
                    {{ translate('messages.drivers') }}
                </span>
            </h1>
        </div>

        <div class="row g-2 mb-20">
            <div class="col-sm-6 col-lg-4">
                <a class="order--card h-100" href="javascript:">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle m-0">
                            <span>{{ translate('All') }}</span>
                        </h6>
                        <span class="card-title text-title">
                            {{ $totalDrivers ?? 0}}
                        </span>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-4">
                <a class="order--card h-100" href="javascript:">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle m-0">
                            <span>{{ translate('messages.Active') }}</span>
                        </h6>
                        <span class="card-title text--success">
                            {{ $activeDrivers?? 0 }}
                        </span>
                    </div>
                </a>
            </div>
            <div class="col-sm-6 col-lg-4">
                <a class="order--card h-100" href="javascript:">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle m-0">
                            <span>{{ translate('messages.Inactive') }}</span>
                        </h6>
                        <span class="card-title text--info">
                            {{ $inactiveDrivers ?? 0}}
                        </span>
                    </div>
                </a>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">



                <div class="card">
                    <div class="card-header py-2">
                        <div class="search--button-wrapper gap-20px">
                            <h5 class="card-title text--title flex-grow-1">{{ translate('messages.Driver_List') }}<span class="badge badge-soft-dark ml-2" id="itemCount">{{$drivers->count()}}</span></h5>

                            <form class="search-form m-0 flex-grow-1 max-w-353px" method="get" action="">
                                <!-- Search -->
                                <div class="input-group input--group">
                                    <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}"
                                           name="search" class="form-control"
                                           placeholder="{{ translate('Search by driver name...') }}"
                                           aria-label="{{ translate('messages.Search by driver name...') }}">
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
                            <div class="hs-unfold m-0">
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
                                       href="{{ route('vendor.driver.export', ['type' => 'excel', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                             src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                             alt="Image Description">
                                        {{ translate('messages.excel') }}
                                    </a>
                                    <a id="export-csv" class="dropdown-item"
                                       href="{{ route('vendor.driver.export', ['type' => 'csv', request()->getQueryString()]) }}">
                                        <img class="avatar avatar-xss avatar-4by3 mr-2"
                                             src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                             alt="Image Description">
                                        .{{ translate('messages.csv') }}
                                    </a>

                                </div>
                            </div>
                            <a class="btn btn--primary font-weight-bold float-right mr-2 mb-0"
                            href="{{ route('vendor.driver.create', request()->id) }}">{{ translate('messages.new_driver') }}</a>
                            <!-- End Unfold -->
                        </div>
                    </div>
                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                               class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.Driver_Info') }}</th>
                                <th class="border-0">{{ translate('messages.Total_Trip') }}</th>
                                <th class="border-0">{{ translate('messages.Complete') }}</th>
                                <th class="border-0">{{ translate('messages.Cancel_Trip') }}</th>
                                <th class="text-center border-0">{{ translate('messages.Driver_Status') }}</th>
                                <th class="text-center border-0">{{ translate('messages.Action') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($drivers as $key=>$driver)
                                <tr>
                                    <td>{{ $key+$drivers->firstItem() }}</td>
                                    <td>
                                        <div class="text--title">
                                            <div class="font-medium">
                                                {{ Str::limit($driver->fullName, 20,'...') }}
                                            </div>
                                            <div class="opacity-lg font-regular">
                                                {{ $driver->phone }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $driver->trips()->count() }}
                                    </td>
                                    <td>
                                        {{ count($driver->completedTrips) }}
                                    </td>
                                    <td>
                                        {{ count($driver->canceledTrips) }}
                                    </td>
                                    <td>
                                        <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{$driver->id}}">
                                            <input type="checkbox" data-url="{{route('vendor.driver.status',[$driver['id'],$driver->status?0:1])}}" class="toggle-switch-input redirect-url" id="stocksCheckbox{{$driver->id}}" {{$driver->status?'checked':''}}>
                                            <span class="toggle-switch-label mx-auto">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                        </label>
                                    </td>
                                    <td>
                                        <div class="btn--container justify-content-center">
                                            <a class="btn action-btn btn--warning btn-outline-warning" href="{{ route('vendor.driver.details', $driver->id)}}"
                                               title="{{ translate('messages.edit_store') }}"><i class="tio-visible-outlined"></i>
                                            </a>
                                            <a class="btn action-btn btn-outline-primary" href="{{ route('vendor.driver.edit', $driver->id)}}"
                                               title="{{ translate('messages.edit_store') }}"><i class="tio-edit"></i>
                                            </a>
                                            <a class="btn action-btn btn--danger btn-outline-danger form-alert" href="javascript:"
                                               data-id="brand-{{$driver['id']}}" data-message="{{ translate('Want to delete this driver') }}" title="{{translate('messages.delete_driver')}}"><i class="tio-delete-outlined"></i>
                                            </a>
                                            <form action="{{route('vendor.driver.delete',[$driver['id']])}}" method="post" id="brand-{{$driver['id']}}">
                                                @csrf @method('delete')
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(count($drivers) !== 0)
                        <hr>
                    @endif
                    <div class="page-area">
                        {!! $drivers->links() !!}
                    </div>
                    @if(count($drivers) === 0)
                        <div class="empty--data">
                            <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                            <h5>
                                {{translate('no_data_found')}}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
            <!-- End Table -->
        </div>
    </div>
@endsection
