@extends('layouts.admin.app')

@section('title', translate('messages.Driver_Details'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="page-header-title text-break">
                        <span class="page-header-icon">
                            <img src="{{ asset('public/assets/admin/img/car-logo.png') }}" alt="">
                        </span>
                        <span>{{ translate('messages.Driver_Details') }}
                    </h1>
                </div>
                <div class="d-flex align-items-start flex-wrap gap-2">
                    <a class="btn btn--cancel h--45px d-flex gap-2 align-items-center form-alert" href="javascript:"
                        data-id="brand-{{ $driver['id'] }}" data-message="{{ translate('Want to delete this driver') }}"
                        title="{{ translate('messages.delete_driver') }}">
                        <i class="tio-delete-outlined"></i>
                        {{ translate('messages.delete') }}
                    </a>
                    <form action="{{ route('admin.rental.provider.driver.delete', [$driver['id']]) }}" method="post"
                        id="brand-{{ $driver['id'] }}">
                        @csrf @method('delete')
                    </form>
                    <a href="javascript:"
                        class="btn btn--reset d-flex justify-content-between align-items-center gap-4 lh--1 h--45px">
                        {{ translate('messages.status') }}
                        <label class="toggle-switch toggle-switch-sm" for="stocksCheckbox{{ $driver->id }}">
                            <input type="checkbox"
                                data-url="{{ route('admin.rental.provider.driver.status', [$driver['id'], $driver->status ? 0 : 1]) }}"
                                class="toggle-switch-input redirect-url" id="stocksCheckbox{{ $driver->id }}"
                                {{ $driver->status ? 'checked' : '' }}>
                            <span class="toggle-switch-label mx-auto">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                    </a>
                    <a href="{{ route('admin.rental.provider.driver.edit', $driver->id) }}"
                        class="btn btn--primary h--45px d-flex gap-2 align-items-center">
                        <i class="tio-edit"></i>
                        {{ translate('messages.Edit_Driver') }}
                    </a>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card mb-20">
            <div class="card-body p-4">
                <div class="card border p-3 p-sm-4 shadow-none mb-3">
                    <div class="media align-items-sm-center flex-column flex-sm-row">
                        <div class="mb-3 mb-sm-0">
                            <img height="115" class="aspect-ratio-1 w-auto rounded mr-4 onerror-image"
                                src="{{ $driver['image_full_url'] }}" alt="">
                        </div>
                        <div class="media-body text--title d-flex justify-content-around flex-column flex-lg-row gap-3">
                            <div class="mr-0 mr-lg-4">
                                <h3 class="fs-20 mb-0">{{ $driver?->fullName }}</h3>
                                <div class="d-flex gap-3">
                                    <span class="min-w-110px">{{ translate('Phone') }}</span>
                                    <span>: {{ $driver->phone }}</span>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="min-w-110px">{{ translate('Email') }}</span>
                                    <span>: {{ $driver->email }}</span>
                                </div>
                            </div>

                            <div class="mr-0 mr-lg-4">
                                <h5 class="">{{ translate('Identity Information') }}</h5>
                                <div class="d-flex gap-3">
                                    <span class="min-w-110px">{{ translate('Identity Type') }}</span>
                                    <span>: {{ translate($driver->identity_type) }}</span>
                                </div>
                                <div class="d-flex gap-3">
                                    <span class="min-w-110px">{{ translate('Identity Number') }}</span>
                                    <span>: {{ $driver->identity_number }}</span>
                                </div>
                            </div>
                            <div class="mr-0 mr-lg-4">
                                <h5 class="">{{ translate('Provider Info') }}</h5>
                                <div class="align-items-center d-flex gap-2 resturant--information-single text-left">
                                    <img height="45" class="aspect-ratio-1 onerror-image rounded"
                                        src="{{ $driver?->provider?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}"
                                        alt="{{ translate('Image Description') }}">
                                    <div class="text--title">
                                        <a class="media align-items-center deco-none resturant--information-single"
                                            href="{{ isset($driver?->provider) ? route('admin.rental.provider.details', $driver?->provider?->id) : '#' }}">
                                            <h5 class="text-capitalize font-semibold text-hover-primary d-block mb-1">
                                                {{ $driver?->provider?->name }}
                                                <span class="btn btn--warning fs-12 rounded-20 text-white py-1 px-2 ml-1">
                                                    <i
                                                        class="tio-star mr-1"></i>{{ number_format($driver?->provider?->vehicle_reviews->avg('rating'), 1) ?? 0.0 }}
                                                </span>
                                            </h5>
                                        </a>
                                        <span class="opacity-lg">
                                            {{ $driver?->provider?->phone }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    @if (count($driver['identity_image_full_url']) > 0)

                        <h5 class="text--title mb-20">{{ translate('Identity Image') }}</h5>
                        <div class="d-flex gap-4 flex-wrap">
                            @foreach ($driver['identity_image_full_url'] as $key => $img)
                                <div class="identify-image-single position-relative">
                                    <img width="275" data-toggle="modal" data-target="#imagemodal{{ $key }}"
                                        class="aspect-2-1 object--cover rounded-10" src="{{ $img }}"
                                        alt="Identity image">
                                    <a href="{{ $img }}" class="download-btn" download="">
                                        <i class="tio-download-to"></i>
                                    </a>
                                </div>

                                <div class="modal fade" id="imagemodal{{ $key }}" tabindex="-1" role="dialog"
                                    aria-labelledby="order_proof_{{ $key }}" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h4 class="modal-title" id="order_proof_{{ $key }}">
                                                    {{ translate('Identity Image') }}</h4>
                                                <button type="button" class="close" data-dismiss="modal"><span
                                                        aria-hidden="true">&times;</span><span
                                                        class="sr-only">{{ translate('messages.cancel') }}</span></button>
                                            </div>
                                            <div class="modal-body scroll-down">
                                                <img src="{{ $img }}" class="initial--22 w-100">
                                            </div>

                                            <div class="modal-footer">
                                                <a href="{{ $img }}" download class="btn btn-primary"
                                                    class="download-icon mt-3">
                                                    <img src="{{ asset('/public/assets/admin/new-img/download-icon.svg') }}"
                                                        alt="">
                                                    {{ translate('messages.download') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- End Card -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper gap-20px">
                    <h5 class="card-title text--title flex-grow-1">{{ translate('messages.Total_Trips') }}
                        <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $driverTrips->total() }}</span>

                    </h5>
                    <form class="search-form flex-grow-1 max-w-353px">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}"
                                name="search" class="form-control" placeholder="{{ translate('Search by trip ID') }}"
                                aria-label="{{ translate('messages.Search by trip ID...') }}">
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
                                href="{{ route('admin.rental.provider.driver.trip.export', ['id' => request()->id, 'type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                                href="{{ route('admin.rental.provider.driver.trip.export', ['id' => request()->id, 'type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                {{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
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
                            <th class="border-0">{{ translate('messages.Trip ID') }}</th>
                            <th class="border-0">{{ translate('messages.Booking_Date') }}</th>
                            <th class="border-0">{{ translate('messages.Schedule_At') }}</th>
                            <th class="border-0">{{ translate('messages.Customer_Info') }}</th>
                            <th class="border-0">{{ translate('messages.Vehicle_Info') }}</th>
                            <th class="border-0">{{ translate('messages.Trip_Type') }}</th>
                            <th class="text-center border-0">{{ translate('messages.Trip_Status') }}</th>
                            <th class="text-center border-0">{{ translate('messages.Action') }}</th>
                        </tr>
                    </thead>

                    <tbody id="set-rows">
                        @foreach ($driverTrips as $key => $driverTrip)
                            <tr>
                                <td>{{ $key + $driverTrips->firstItem() }}</td>
                                <td>
                                    <a href="{{ route('admin.rental.trip.details', $driverTrip?->trip?->id) }}"
                                        target="_blank" rel="noopener noreferrer">
                                        <div class="text--title font-semibold">

                                            {{ $driverTrip?->trip?->id }}
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div class="text--title">
                                        {{ \App\CentralLogics\Helpers::date_format($driverTrip?->trip?->created_at)  }}
                                        <br>
                                        {{ \App\CentralLogics\Helpers::time_format($driverTrip?->trip?->created_at)  }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text--title">
                                        {{ \App\CentralLogics\Helpers::date_format($driverTrip?->trip?->schedule_at)  }}
                                        <br>
                                        {{ \App\CentralLogics\Helpers::time_format($driverTrip?->trip?->schedule_at)  }}
                                    </div>

                                </td>
                                <td>
                                    <div class="text--title">
                                        @if ($driverTrip?->trip?->customer)
                                        <a href="{{ route('admin.users.customer.rental.view', $driverTrip?->trip->user_id) }}?module=1" target="_blank" rel="noopener noreferrer">
                                            <div class="font-medium">
                                                {{ $driverTrip?->trip?->customer?->fullName }}
                                            </div>
                                        </a>
                                            <div class="opacity-lg">
                                                {{ $driverTrip?->trip?->customer?->email }}
                                            </div>
                                        @elseif($driverTrip?->trip?->user_info['contact_person_name'])
                                            <div class="font-medium">
                                                {{ $driverTrip?->trip?->user_info['contact_person_name'] }}
                                            </div>
                                            <div class="opacity-lg">
                                                {{ $driverTrip?->trip?->user_info['contact_person_email'] }}
                                            </div>
                                        @else
                                            {{ translate('messages.Guest_user') }}
                                        @endif
                                    </div>
                                </td>
                                @php
                                    $maxDisplay = 3;
                                    $totalVehicle = count($driverTrip?->trip?->assignedVehicle);
                                @endphp
                                <td>
                                    @if ($totalVehicle > 0)
                                        <div class="text-primary text-underline font-weight-medium" data-html="true"
                                            data-toggle="tooltip"
                                            title="<div class='d-flex flex-column p-2'>
                                         @foreach ($driverTrip?->trip?->trip_details as $index => $detail)
                                            <div class='media gap-3 {{ !$loop->last ? 'border-bottom mb-2 pb-2' : '' }}'>
                                                <img src='{{ $detail->vehicle?->thumbnailFullUrl }}' class='rounded ratio-1-1' width='40' alt='...'>
                                                <div class='media-body'>
                                                    <h5 class='d-flex align-items-center gap-2 text-white mb-0'>{{ $detail->vehicle_details['name'] }}</h5>
                                                    <div class='d-flex align-items-center gap-2 fs-10'>{{ translate('messages.car_Assigned') }}: {{ $detail->tripVehicleDetails->count() }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>">
                                            {{ $totalVehicle }} {{ translate('messages.vehicles') }}
                                        </div>
                                    @else
                                        <div class="text--warning font-medium">
                                            {{ translate('messages.Unassigned') }}
                                        </div>
                                    @endif

                                </td>
                                <td>
                                    <div class="text--title">
                                        <div class="font-medium">
                                            {{ translate($driverTrip?->trip?->trip_type) }}
                                        </div>
                                        <div class="opacity-lg">
                                            {{ $driverTrip?->trip?->scheduled ? translate('messages.scheduled') : translate('messages.Instant') }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">

                                        @php
                                        $statusClasses = [
                                            'pending' => 'badge-soft-info',
                                            'completed' => 'badge-soft-success',
                                            'canceled' => 'badge-soft-danger',
                                            'ongoing' => 'badge-soft-warning',
                                            'payment_failed' => 'badge-soft-danger',
                                        ];

                                        $badgeClass = $statusClasses[$driverTrip?->trip?->trip_status] ?? 'badge-soft-info';
                                    @endphp
                                    <label class="badge {{ $badgeClass }} border-0">
                                        {{ translate($driverTrip?->trip?->trip_status) }}
                                    </label>

                                    </div>
                                </td>
                                <td>
                                    <div class="btn--container justify-content-center">
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.rental.trip.generate-invoice', ['id' => $driverTrip?->trip?->id]) }}"
                                            title="{{ translate('messages.download') }}"><i class="tio-download-to"></i>
                                        </a>
                                        <a class="btn action-btn btn--primary btn-outline-primary"
                                            href="{{ route('admin.rental.trip.details', $driverTrip?->trip?->id) }}"
                                            title="{{ translate('messages.view') }}"><i class="tio-visible-outlined"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
            @if (count($driverTrips) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $driverTrips->appends($_GET)->links() !!}
            </div>
            @if (count($driverTrips) === 0)
                <div class="empty--data">
                    <img src="{{ asset('/public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>

@endsection


@push('script_2')
@endpush
