@extends('layouts.admin.app')

@section('title',$store->name."'s ".translate('messages.orders'))

@push('css_or_js')
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('Modules/Rental/public/assets/css/provider/trip-list.css')}}">
@endpush

@section('content')
<div class="content container-fluid">
    @include('rental::admin.provider.details.partials._header',['store'=>$store])

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="order--card flex-grow-1" href="javascript:">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle m-0">
                    <span>{{ translate('All') }}</span>
                </h6>
                <span class="card-title text-title">{{ $trips->total() }}</span>
            </div>
        </a>

        <a class="order--card flex-grow-1" href="javascript:">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle m-0">
                    <span>{{ translate('messages.ongoing') }}</span>
                </h6>
                <span class="card-title text--warning">{{ $store->trips->where('trip_status','ongoing')->count() }}</span>
            </div>
        </a>

        <a class="order--card flex-grow-1" href="javascript:">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle m-0">
                    <span>{{ translate('messages.pending') }}</span>
                </h6>
                <span class="card-title text--info">{{ $store->trips->where('trip_status','pending')->count() }}</span>
            </div>
        </a>

        <a class="order--card flex-grow-1" href="javascript:">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle m-0">
                    <span>{{ translate('messages.completed') }}</span>
                </h6>
                <span class="card-title text--success">{{ $store->trips->where('trip_status','completed')->count() }}</span>
            </div>
        </a>

        <a class="order--card flex-grow-1" href="javascript:">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-subtitle m-0">
                    <span>{{ translate('messages.canceled') }}</span>
                </h6>
                <span class="card-title text--danger">{{ $store->trips->where('trip_status','canceled')->count() }}</span>
            </div>
        </a>
    </div>
    <!-- Page Heading -->
    <div class="content">
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header justify-content-between gap-3 py-2 flex-wrap">

                <!-- Page Header -->
                <div class="page-header">
                    <div class="d-flex justify-content-between flex-wrap gap-3">
                        <div>
                            <h1 class="page-header-title text-break">
                                <span>{{ translate('messages.All_Trips') }}
                            <span class="badge badge-soft-dark ml-2" id="itemCount">{{ $trips->total() }}</span>
                        </span>
                            </h1>
                        </div>
                    </div>
                </div>

                <div class="search--button-wrapper justify-content-end gap-20px">
                    <form action="" method="get" class="search-form flex-grow-1 max-w-450px" data-route="{{route('admin.order.store-search')}}">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}"
                                   name="search" class="form-control"
                                   placeholder="{{ translate('Search by trip ID, customer name, email') }}"
                                   aria-label="{{ translate('messages.Search by trip ID, customer name, email') }}">
                            <button type="submit" class="btn btn--secondary bg--primary"><i class="tio-search"></i></button>
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
                               href="{{ route('admin.rental.trip.export', ['provider_id'=>request()->id,'type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                     alt="{{translate('Image Description')}}">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item"
                               href="{{ route('admin.rental.trip.export', ['provider_id'=>request()->id,'type' => 'csv', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                     alt="{{translate('Image Description')}}">
                                .{{ translate('messages.csv') }}
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
                        <th class="border-0">{{ translate('messages.Driver_Info') }}</th>
                        <th class="border-0">{{ translate('messages.Vehicle_Info') }}</th>
                        <th class="border-0">{{ translate('messages.Trip_Type') }}</th>
                        <th class="text-end border-0">{{ translate('messages.Trip_Amount') }}</th>
                        <th class="text-center border-0">{{ translate('messages.Trip_Status') }}</th>
                        <th class="text-center border-0">{{ translate('messages.Action') }}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($trips as $key=> $trip)
                        <tr>
                            <td>{{$key+$trips->firstItem()}}</td>
                            <td>
                                <a href="{{ route('admin.rental.trip.details', $trip->id) }}" class="text--title font-semibold">
                                    {{ $trip->id }}
                                </a>
                            </td>
                            <td>
                                <div class="text--title">
                                    {{ \App\CentralLogics\Helpers::date_format($trip?->created_at)  }}
                                    <br>
                                    {{ \App\CentralLogics\Helpers::time_format($trip?->created_at)  }}
                                </div>
                            </td>
                            <td>
                                <div class="text--title">
                                    {{ \App\CentralLogics\Helpers::date_format($trip?->schedule_at)  }}
                                    <br>
                                    {{ \App\CentralLogics\Helpers::time_format($trip?->schedule_at)  }}
                                </div>


                            </td>
                            <td>
                                @if ($trip->customer)
                                    <div class="text--title">
                                        <a href="{{ route('admin.users.customer.rental.view', $trip->user_id) }}?module=1" class="font-medium">
                                            {{ $trip->customer->fullName }}
                                        </a>
                                        <div class="opacity-lg">
                                            {{ $trip->customer->email }}
                                        </div>
                                    </div>
                                @elseif($trip?->user_info['contact_person_name'])
                                    <div class="text--title">
                                        <div class="font-medium">
                                            {{$trip?->user_info['contact_person_name'] }}
                                        </div>
                                        <div class="opacity-lg">
                                            {{ $trip?->user_info['contact_person_email'] }}
                                        </div>
                                    </div>
                                @else
                                    {{ translate('messages.Guest_user') }}
                                @endif
                            </td>
                            @php
                                $maxDisplay = 3;
                                $totalVehicle = count($trip->assignedVehicle);
                                $totalDriver = count($trip->assignedDriver);
                                $totalTripe = count($trip->trip_details);
                            @endphp
                            <td>
                                @if($totalDriver)
                                    @if($totalDriver > 1)
                                        <div class="d-flex align-items-center gap-2" data-html="true" data-toggle="tooltip"
                                             title="<div class='d-flex flex-column p-2'>
                                             @foreach($trip->assignedDriver as $index => $tooltipDriver)
                                                <div class='media gap-3 {{ !$loop->last ? 'border-bottom mb-2 pb-2' : '' }}'>
                                                    <img height='40' src='{{ $tooltipDriver->driver['imageFullUrl'] }}'  class='rounded ratio-1-1' width='40' alt='...'>
                                                    <div class='media-body'>
                                                        <h5 class='d-flex align-items-center gap-2 text-white mb-0'>
                                                            <a href='{{ route('admin.rental.provider.driver.details', $tooltipDriver->driver->id) }}' class='text-white'>{{ Str::limit($tooltipDriver->driver['fullName'],12,'...')  }}</a>
                                                        </h5>
                                                        <div class='d-flex align-items-center gap-2 fs-10'>{{Str::limit( $tooltipDriver->driver->email,12,'...') }}</div>
                                                    </div>
                                                </div>
                                             @endforeach
                                         </div>">
                                            <div class="d-flex">
                                                @foreach ($trip->assignedDriver->take($maxDisplay) as $key => $assignedDriver)
                                                    <img width="35"  class="rounded-circle aspect-1-1 border border-white shadow-sm {{ $key > 0 ? 'ml-n2' : '' }}" src="{{ $assignedDriver->driver['imageFullUrl'] }}" alt="">
                                                @endforeach
                                            </div>
                                            @if ($totalDriver > $maxDisplay)
                                                <span>+{{ $totalDriver - $maxDisplay }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text--title">
                                            @if ($trip->assignedDriver->isNotEmpty())
                                                <a href="{{ route('admin.rental.provider.driver.details', $trip->assignedDriver->first()?->driver->id) }}" class="font-medium">
                                                    {{ $trip->assignedDriver->first()?->driver?->fullName }}
                                                </a>
                                                <div class="opacity-lg">
                                                    {{ $trip->assignedDriver->first()?->driver?->email }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @else
                                    <div class="text-muted fs-12 mt-1">
                                        {{ translate('messages.Unassigned') }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($totalVehicle > 0)
                                    <div class="text-primary text-underline font-weight-medium" data-html="true" data-toggle="tooltip"
                                         title="<div class='d-flex flex-column p-2'>
                                         @foreach($trip->trip_details as $index => $detail)
                                             @if($detail?->vehicle)
                                                 <div class='media gap-3 {{ !$loop->last ? 'border-bottom mb-2 pb-2' : '' }}'>
                                                     <img src='{{  data_get($detail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}' class='rounded ratio-1-1' width='40' alt='...'>
                                                     <div class='media-body'>
                                                         <h5 class='d-flex align-items-center gap-2 text-white mb-0'>
                                                            <a href='{{ route('admin.rental.provider.vehicle.details', $detail->vehicle_id) }}' class='text-white'>{{ Str::limit($detail->vehicle_details['name'],12,'...')  }}</a>
                                                         </h5>
                                                         <div class='d-flex align-items-center gap-2 fs-10'>{{ translate('messages.car_Assigned') }}: {{ $detail->tripVehicleDetails->count() }}</div>
                                                     </div>
                                                 </div>
                                             @else
                                                 <div class='media gap-3 {{ !$loop->last ? 'border-bottom mb-2 pb-2' : '' }}'>
                                                     <img src='{{  data_get($detail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}' class='rounded ratio-1-1' width='40' alt='...'>
                                                     <div class='media-body'>
                                                         <h5 class='d-flex align-items-center gap-2 text-white mb-0'>
                                                            <span class='text-white'>{{ Str::limit($detail->vehicle_details['name'],12,'...')  }}</span>
                                                        </h5>
                                                         <div class='d-flex align-items-center text-danger gap-2 fs-10'>{{ translate('Vehicle_Not_Found_!!!') }}</div>
                                                     </div>
                                                 </div>
                                             @endif
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
                                        {{ translate(str_replace('-', ' ', translate($trip->trip_type))) }}
                                    </div>
                                    <div class="opacity-lg">
                                        {{ $trip->scheduled ? translate('messages.Instant') : translate('messages.scheduled') }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text--title text-end">
                                    <div class="font-semobold">
                                        {{ \App\CentralLogics\Helpers::format_currency($trip->trip_amount) }}
                                    </div>


                                    <div class="opacity-lg font-medium {{ $trip->payment_status == 'paid' ? 'text--success' : 'text--danger'  }} ">
                                        {{ translate($trip->payment_status) }}
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

                                        $badgeClass = $statusClasses[$trip->trip_status] ?? 'badge-soft-info';
                                    @endphp
                                    <label class="badge {{ $badgeClass }} border-0">
                                        {{ translate($trip->trip_status) }}
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="btn--container justify-content-center">
                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{route("admin.rental.trip.generate-invoice",["id" => $trip->id])}}"
                                       title="{{ translate('messages.download') }}"><i class="tio-download-to"></i>
                                    </a>
                                    <a class="btn action-btn btn--primary btn-outline-primary" href="{{ route('admin.rental.trip.details', $trip->id) }}"
                                       title="{{ translate('messages.view') }}"><i class="tio-visible-outlined"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>

            </div>
            @if(count($trips) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $trips->appends($_GET)->links() !!}
            </div>
            @if(count($trips) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="{{translate('public')}}">
                    <h5>
                        {{translate('no_data_found')}}
                    </h5>
                </div>
            @endif
            <!-- End Table -->
        </div>
        <!-- End Card -->
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-trip.js')}}"></script>
@endpush
