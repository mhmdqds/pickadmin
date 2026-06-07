@extends('layouts.vendor.app')
@section('title', translate('Trip Details'))

@section($trip->trip_status)
active
@endsection

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('Modules/Rental/public/assets/css/provider/trip-details.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <span class="page-header-icon">
                            <img src="{{ asset('/public/assets/admin/img/car-logo.png') }}" class="w--20" alt="">
                        </span>
                        <span>
                            {{ translate('trips_details') }}
                        </span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- Page Header -->

        <div class="row flex-xl-nowrap" id="printableArea">
            <div class="col-lg-8 order-print-area-left">
                <!-- Card -->
                <div class="card mb-3 mb-lg-5">
                    <!-- Header -->
                    <div class="card-header align-items-stretch flex-column border-0 pb-0">
                        <div class="d-flex align-items-start justify-content-between flex-wrap mb-2">
                            <div class="order-invoice-left d-flex d-sm-block justify-content-between">
                                <div>
                                    <h1 class="page-header-title d-flex align-items-center __gap-5px">
                                        {{translate('Trip ID')}} # {{ $trip->id }}
                                        @if ($trip->edited)
                                        <span class="badge badge--pending text-capitalize">
                                            {{ translate('messages.edited') }}
                                        </span>
                                        @endif
                                    </h1>
                                    <span class="mt-2 d-block d-flex align-items-center __gap-5px">
                                        {{ translate('Placed on') }} {{ \App\CentralLogics\Helpers::time_date_format($trip?->created_at)  }}
                                        @if ($trip->scheduled)
                                        <br>
                                        {{ translate('Schedule At') }} {{ \App\CentralLogics\Helpers::time_date_format($trip?->schedule_at)  }}
                                        @endif
                                    </span>
                                    <div class="fs-14 text-title mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        <span>{{translate('Provider')}}</span> <span>:</span>
                                        <span class="font-bold">{{ $trip->provider->name }}</span>
                                        <button type="button" class="btn btn--primary-light px-2 py-1 shadow-none"
                                                data-toggle="modal" data-target="#providerLocationModal">
                                            <i class="tio-poi"></i> {{translate('View map')}}
                                        </button>
                                    </div>
                                    <div class="fs-14 text-title mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        <span>{{translate('Trip Type')}}</span> <span>:</span>
                                        <span class="font-bold">{{ translate($trip->trip_type) }}</span>
                                        <span>({{ $trip->scheduled ?  translate('messages.scheduled') : translate('messages.Instant') }})</span>
                                    </div>
                                    <div class="fs-14 text-title mt-2 pt-1 mb-2 d-flex align-items-center __gap-5px">
                                        @if ($trip->trip_type == 'hourly')
                                        <span>{{translate('Total ')}} {{ translate('Hour')}}</span> <span>:</span>
                                        <span class="font-bold">{{ $trip->estimated_hours }} {{ translate('hrs') }}</span>
                                        @else
                                        <span>{{translate('Total ')}} {{ translate('KM') }}</span> <span>:</span>
                                        <span class="font-bold">{{ $trip->distance }} {{  translate('KM')  }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="d-sm-none">
                                    <a class="btn btn--primary print--btn font-regular d-flex align-items-center __gap-5px"
                                       href="{{route("vendor.trip.generate-invoice",["id" => $trip->id])}}">
                                        <i class="tio-print mr-sm-1"></i>
                                        <span>{{ translate('messages.print_invoice') }}</span>
                                    </a>
                                </div>
                            </div>
                            <div class="order-invoice-right mt-3 mt-sm-0">
                                <div class="btn--container ml-auto align-items-center justify-content-end">
                                    @if($trip->trip_status == 'pending' && $is_deleted != 1)
                                        <button class="btn btn--primary btn-outline-primary font-bold" type="button"
                                                data-toggle="modal" data-target="#editTripModal">
                                            <i class="tio-edit mr-sm-1"></i> {{translate('Edit Trip')}}
                                        </button>
                                    @endif
                                    <a class="btn btn--primary print--btn font-bold d-none d-sm-block" href="{{route("vendor.trip.generate-invoice",["id" => $trip->id])}}">
                                        <i class="tio-print mr-sm-1"></i> <span>{{translate('Print invoice')}}</span>
                                    </a>
                                </div>
                                <div class="text-right mt-3 order-invoice-right-contents text-capitalize">
                                    <h6>
                                        @php
                                            $statusClasses = [
                                                'pending' => 'badge--pending-1',
                                                'completed' => 'badge--accepted',
                                                'canceled' => 'badge--cancel',
                                                'ongoing' => 'badge--pending',
                                                'payment_failed' => 'badge--cancel',
                                            ];

                                            $badgeClass = $statusClasses[$trip->trip_status] ?? 'badge--accepted';
                                        @endphp

                                        <span>{{translate('Trip Status')}}</span> <span>:</span>
                                        <span class="{{ $badgeClass }} badge  ml-2 ml-sm-3 text-capitalize">
                                            {{ translate($trip->trip_status) }}
                                        </span>
                                    </h6>
                                    <h6>
                                        <span>{{translate('Payment status')}}</span> <span>:</span>
                                        <strong class="{{ $trip->payment_status  == 'paid' ? 'text-success' :'text-danger' }}">{{ translate($trip->payment_status) }}</strong>

                                    </h6>
                                    @if ($trip->payment_method)
                                    <h6>
                                        <span>{{translate('Payment method')}}</span> <span>:</span>
                                        <span class="font-semibold">{{ translate($trip->payment_method ?? 'cash payment') }}</span>
                                    </h6>
                                    @endif

                                </div>
                            </div>
                        </div>
                        @if(!empty($trip->trip_note))
                            <div class="__bg-FAFAFA p-2 rounded">
                                <h6 class="fs-14 text-title">
                                    {{translate('Note')}}:
                                    <span class=" font-regular">{{ $trip->trip_note }}</span>
                                </h6>
                            </div>
                        @endif
                    </div>
                    <!-- End Header -->

                    <!-- Body -->
                    <div class="card-body px-0">
                        <!-- item cart -->
                        <div class="table-responsive">
                            <table
                                class="table table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                <thead class="thead-light">
                                <tr>
                                    <th class="border-0">#</th>
                                    <th class="border-0">{{translate('Vehicle Details')}}</th>
                                    <th class="border-0">{{translate('unit Fair')}}</th>
                                    <th class="border-0 text-center">{{translate('Quantity')}}</th>
                                    <th class="border-0 text-center">{{translate('Total Hour/Km')}}</th>
                                    <th class="border-0 text-center">{{translate('Fare')}}</th>
                                </tr>
                                </thead>
                                <tbody>

                                @php
                                    $subtotal = 0;
                                @endphp
                                @foreach($trip->trip_details as $detail)
                                    <tr>
                                        <td>
                                            <div>
                                                {{ $loop->iteration }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="media media--sm">
                                                <a class="avatar avatar-xl mr-3" href="{{ route('vendor.vehicle.details', $detail->vehicle_id) }}">
                                                    <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                         src="{{ data_get($detail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}"
                                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                         alt="Image Description">
                                                </a>
                                                <div class="media-body">
                                                    <div class="fs-12 text--title">
                                                        <div class="fz-12 font-semibold line--limit-1">
                                                            {{ $detail?->vehicle_details['name'] }}</div>
                                                            @if ($detail?->vehicle)

                                                            <div><span class="font-semibold mr-2">{{translate('Category')}} :</span>{{ $detail?->vehicle?->category?->name }}</div>
                                                            <div><span class="font-semibold mr-2">{{translate('Brand')}} :</span>{{ $detail?->vehicle?->brand?->name }}</div>
                                                            @else
                                                            <div><span class="text--danger mr-2">{{translate('Vehicle_Not_Found_!!!')}} </span></div>

                                                            @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @if($detail?->tripVehicleDetails->isEmpty())
                                                @if(!in_array($trip->trip_status, ['ongoing','pending', 'completed', 'canceled']) && $is_deleted != 1)
                                                    <div class="mt-2">
                                                        <button
                                                            class="btn btn--primary btn-outline-primary p-5px rounded-20 d-flex align-items-center gap-1 assign-vehicle-btn"
                                                            type="button"
                                                            data-toggle="modal"
                                                            data-target="#assignVehicleModal"
                                                            data-details_id = "{{ $detail->id }}"
                                                            data-trip_id = "{{ $detail->trip_id }}"
                                                            data-vehicle_id = "{{ $detail->vehicle_id }}"
                                                            data-quantity = "{{ $detail->quantity }}"
                                                            data-img = "{{ data_get($detail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}"
                                                            data-name = "{{ $detail?->vehicle_details['name'] }}"
                                                            data-vendor = "{{ $trip?->provider->name }}"
                                                            data-category = "{{ $detail?->vehicle?->category?->name }}"
                                                            data-brand = "{{ $detail?->vehicle?->brand?->name }}"
                                                            data-list="{{ json_encode($detail?->vehicle?->vehicleIdentities) }}"
                                                            data-trip_vehicle_details="{{ json_encode($detail->tripVehicleDetails) }}"
                                                        >
                                                            {{translate('Assign Vehicle')}} <span class="fs-24"><i
                                                                    class="tio-add-circle"></i></span>
                                                        </button>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="mt-2 bg--F6F6F6 p-2 radius-15 mb-4 d-inline-block">
                                                    <div class="d-flex justify-content-between gap-3 mb-10px text--title">
                                                        <span>{{translate('Assigned Vehicle')}}</span>
                                                        @if(!in_array($trip->trip_status, ['ongoing','pending', 'completed', 'canceled']))
                                                            <button
                                                                class="btn btn--primary p-5px rounded-circle d-flex align-items-center justify-content-center assign-vehicle-btn"
                                                                type="button"
                                                                data-toggle="modal"
                                                                data-target="#assignVehicleModal"
                                                                data-details_id = "{{ $detail->id }}"
                                                                data-trip_id = "{{ $detail->trip_id }}"
                                                                data-vehicle_id = "{{ $detail->vehicle_id }}"
                                                                data-quantity = "{{ $detail->quantity }}"
                                                                data-img = "{{ data_get($detail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}"
                                                                data-name = "{{ $detail?->vehicle_details['name'] }}"
                                                                data-vendor = "{{ $trip?->provider->name }}"
                                                                data-category = "{{ $detail?->vehicle?->category?->name }}"
                                                                data-brand = "{{ $detail?->vehicle?->brand?->name }}"
                                                                data-list="{{ json_encode($detail->vehicle->vehicleIdentities) }}"
                                                                data-trip_vehicle_details="{{ json_encode($detail->tripVehicleDetails) }}"
                                                            >
                                                                <i class="tio-edit fs-12"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <div class="text-wrap">
                                                        @php
                                                            $licensePlates = $detail?->tripVehicleDetails->map(function($tripVehicleDetails) {
                                                                return $tripVehicleDetails?->vehicle_identity_data?->license_plate_number;
                                                            });
                                                            $licensePlatesString = $licensePlates->implode(', ');
                                                        @endphp
                                                        {{ $licensePlatesString }}
                                                    </div>
                                                </div>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="fs-14 text--title">
                                                {{ \App\CentralLogics\Helpers::format_currency($detail->rental_type == 'hourly' ? $detail->vehicle_details['hourly_price'] : $detail->vehicle_details['distance_price']) }}
                                               /{{ $detail->rental_type ==  'hourly' ? translate('Hr') : translate('messages.KM')  }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fs-14  text--title font-bold">
                                                {{ $detail->quantity }}
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fs-14 text--title">
                                                @if ($trip->trip_type == 'hourly')
                                                {{ $trip->estimated_hours }} {{ translate('hrs') }}
                                                @else
                                                {{ $trip->distance }} {{  translate('KM')  }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fs-14 text--title">
                                                {{ \App\CentralLogics\Helpers::format_currency($detail->calculated_price) }}
                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                        $subtotal += $detail->calculated_price;
                                    @endphp
                                @endforeach
                                <!-- End Media -->
                                </tbody>
                            </table>
                        </div>
                        <div class="mx-3">
                            <hr>
                        </div>
                        <div class="row justify-content-md-end mb-3 mt-4 mx-0">
                            <div class="col-md-9 col-lg-8">
                                <dl class="row text-right text-title">
                                    <dt class="col-6 font-regular">{{translate('Trip Fare')}}</dt>
                                    <dd class="col-6">
                                        {{ \App\CentralLogics\Helpers::format_currency($subtotal) }}</dd>

                                    <dt class="col-6">{{ translate('Subtotal') }}
                                        @if ($trip->tax_status == 'included')
                                        ({{ translate('messages.TAX_Included') }})
                                        @endif

                                    </dt>
                                    <dd class="col-6 font-semibold">
                                        {{ \App\CentralLogics\Helpers::format_currency($subtotal) }}
                                    </dd>
                                    <dt class="col-6 font-regular">{{translate('discount')}}</dt>
                                    <dd class="col-6">
                                        -{{ \App\CentralLogics\Helpers::format_currency($trip->discount_on_trip)}}
                                    </dd>

                                    <dt class="col-6 font-regular">{{translate('Coupon discount')}}</dt>
                                    <dd class="col-6">
                                        -{{ \App\CentralLogics\Helpers::format_currency($trip->coupon_discount_amount)}}
                                    </dd>

                                    @if ($trip->ref_bonus_amount > 0)
                                    <dt class="col-6 font-regular">{{translate('Referral_Discount')}}</dt>
                                    <dd class="col-6">
                                        -{{ \App\CentralLogics\Helpers::format_currency($trip->ref_bonus_amount)}}
                                    </dd>
                                    @endif


                                    @if ($trip->tax_status == 'excluded')
                                    <dt class="col-6 font-regular text-uppercase">{{translate('Vat/tax')}}</dt>
                                    <dd class="col-6 text-right">
                                        +{{ \App\CentralLogics\Helpers::format_currency($trip->tax_amount)}}
                                    </dd>
                                    @endif
                                    <dt class="col-6 font-regular ">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??\App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}</dt>
                                    <dd class="col-6 text-right">
                                        + {{ \App\CentralLogics\Helpers::format_currency($trip->additional_charge) }}</dd>

                                        <dt class="col-6 font-bold">{{translate('Total')}}</dt>
                                    <dd class="col-6 font-bold">{{ \App\CentralLogics\Helpers::format_currency($trip->trip_amount)}}</dd>
                                </dl>
                                <!-- End Row -->
                            </div>
                        </div>
                        <!-- End Row -->
                    </div>
                    <!-- End Body -->
                </div>
                <!-- End Card -->
            </div>
            @php
                $tripDrivers = $trip?->vehicle_identity->whereNotNull('vehicle_driver_id');
                $tripVehicles = $trip?->vehicle_identity->whereNotNull('vehicle_identity_id')->count();
                $driverCount = $tripDrivers->count()
            @endphp
            <div class="col-lg-4 order-print-area-right">
                @if($trip->trip_status != 'completed' || $trip->payment_status != 'paid' )
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">{{ translate('trip_setup') }}</h5>
                        </div>
                        <div class="card-body">
                            @if($trip->trip_status != 'completed')
                                <div class="hs-unfold w-100 mb-20">
                                    <label for="" class="font-semibold text-title">{{ translate('Trip Status') }}</label>
                                    <div class="dropdown">
                                        <button
                                            class="form-control h--45px dropdown-toggle d-flex justify-content-between align-items-center w-100"
                                            type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {{ translate($trip->trip_status) }}
                                        </button>
                                        <div class="dropdown-menu text-capitalize" aria-labelledby="dropdownMenuButton">
                                            @php
                                                $statuses = ['pending', 'confirmed', 'ongoing', 'completed', 'canceled'];
                                            @endphp
                                            @foreach ($statuses as $status)
                                                @if ($status !== strtolower($trip->trip_status))
                                                    <a class="dropdown-item route-alert"
                                                       data-url="{{ route('vendor.trip.status', ['id' => $trip['id'], 'status' => $status]) }}"
                                                       data-message="Change status to {{ $status }}?" href="javascript:">
                                                        {{ translate($status) }}
                                                    </a>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="hs-unfold w-100 mb-20">
                                <label for="" class="font-semibold text-title">{{translate('Payment Status')}}</label>
                                <div class="dropdown">
                                    <button
                                        class="form-control h--45px dropdown-toggle d-flex justify-content-between align-items-center w-100"
                                        type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        {{ translate($trip->payment_status) }}
                                    </button>
                                    <div class="dropdown-menu text-capitalize" aria-labelledby="dropdownMenuButton">
                                        @php
                                            $paymentStatuses = ['paid', 'unpaid'];
                                        @endphp
                                        @foreach ($paymentStatuses as $status)
                                            @if ($status !== strtolower($trip->payment_status))
                                                <a class="dropdown-item route-alert"
                                                   data-url="{{ route('vendor.trip.payment.status', ['id' => $trip['id'], 'status' => $status]) }}"
                                                   data-message="Change status to {{ translate($status) }}?" href="javascript:">
                                                    {{ translate($status) }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @if($driverCount <= 0 && $tripVehicles > 0)
                                <button type="button"
                                        class="btn btn--primary w-100"
                                        data-toggle="modal" data-target="#assignDriverModal">
                                    <i class="tio-bike"></i>
                                    <span class="ml-2">{{translate('Assign Driver')}}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
                @if($driverCount > 0)
                    <div class="card mt-2">
                        <div class="card-header">
                            <h5 class="mb-0">{{translate('Driver List')}}</h5>
                            @if(!in_array($trip->trip_status, ['pending', 'completed', 'canceled']))
                                <a href="#" class="btn action-btn btn--primary btn-outline-primary p-0 assign-driver-modal">
                                    <i class="tio-edit"></i>
                                </a>
                            @endif
                        </div>
                        <div class="card-body">
                            <button class="btn btn--reset font-medium w-100 d-flex justify-content-between align-items-center px-3 driverListCollapseBtn" type="button" data-toggle="collapse" data-target="#driverListCollapse" aria-expanded="false" aria-controls="driverListCollapse">
                                {{ $driverCount }} {{translate('driver Assigned')}} <i class="tio-down-ui fs-10"></i>
                            </button>

                            <div class="table-responsive collapse" id="driverListCollapse">
                                <table
                                    class="table table-nowrap table-align-middle card-table no-footer mb-0">
                                    <tbody>
                                    @foreach($tripDrivers as $driverDetails)
                                        <tr>
                                            <td>
                                                <div class="d-flex gap-4 align-items-center">
                                                    <div>{{ $loop->iteration }}</div>
                                                    <div class="fs-12 font-semibold text--title">
                                                        <div>
                                                            <span>{{ $driverDetails->driver->fullName }}</span>
                                                            <span class="fs-10 opacity-70">({{ $driverDetails->driver->phone }})</span>
                                                        </div>
                                                        <div class="opacity-60">{{translate('Car No')}}: {{ $driverDetails?->vehicle_identity_data?->license_plate_number }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="card mt-2">
                    <div class="card-body">
                        <div class="position-relative">
                            <div class="map-fullscreen-btn_wrapper">
                                <button type="button" data-toggle="modal" data-target="#pickupDesModal"
                                        class="btn border-0 shadow--card-2">
                                    <i class="tio-fullscreen-1-1"></i>
                                </button>
                            </div>
                            <div class="location-map" id="pickup_location_map">
                                <div class="initial--25 rounded-8 custom_map_canvas" id="custom_route_line_map_canvas">
                                </div>
                            </div>
                        </div>
                        <hr>
                        <ul class="trip-details-address text--title px-0 pt-2">
                            <li>
                                <span class="svg">
                                    <span class="text--title bg--F6F6F6 p-10px rounded"><i class="tio-poi"></i></span>
                                </span>
                                <span class="w-0 flex-grow-1">
                                    <span class="font-medium">{{ translate('Home') }}:</span>
                                    <span class="opacity-70">{{ $trip->pickup_location['location_name'] }}</span>
                                </span>
                            </li>
                            <li>
                                <span class="svg">
                                    <span class="text--title bg--F6F6F6 p-10px rounded"><i
                                            class="tio-navigate-outlined rotate-45 d-inline-block"></i></span>
                                </span>
                                <span class="w-0 flex-grow-1 font-medium">
                                    {{ $trip->destination_location['location_name'] }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="card mt-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <h5 class="card-title mb-3 d-flex flex-wrap align-items-center">
                                <span>{{ translate('messages.Customer_Info') }}</span>
                            </h5>

                            @if ($trip->is_guest == 1)
                                <small class="badge-pill badge-soft-primary p-2 font-bold">
                                    {{ translate('Guest_user') }}
                                </small>
                            @endif
                        </div>

                        @if ($trip->customer)
                            <a class="media align-items-center deco-none customer--information-single" href="#">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img onerror-image"
                                         data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                         src="{{ $trip->customer['imageFullUrl'] }}"
                                         alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="text--title fs-14 font-semibold d-block text-hover-primary mb-1">{{ $trip->customer->fullName }}</span>

                                    <div class="text--title d-flex align-items-center gap-1">
                                        {{-- <span>
                                            <span class="font-bold">{{ $trip->customer->orders->count() }}</span>
                                            {{ translate('messages.order') }},
                                        </span> --}}
                                        <span>
                                            <span class="font-bold">{{ $trip?->customer?->trips->count() }}</span>
                                            {{ translate('messages.trip') }}
                                        </span>
                                    </div>

                                    <div class="text--title">
                                        {{ $trip->customer->phone }}
                                    </div>

                                    <div class="text--title">
                                        {{ $trip->customer->email }}
                                    </div>

                                </div>
                            </a>
                            @elseif($trip?->user_info['contact_person_name'])

                            <div class="media align-items-center deco-none customer--information-single" href="#">
                                <div class="avatar avatar-circle">
                                    <img class="avatar-img onerror-image"

                                        src="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
                                        alt="Image Description">
                                </div>
                                <div class="media-body">
                                    <span class="text--title fs-14 font-semibold d-block text-hover-primary mb-1">{{ $trip?->user_info['contact_person_name'] }}</span>

                                    <div class="text--title d-flex align-items-center gap-1">
                                        <span class="font-bold">
                                            {{ translate('Guest_user') }},
                                        </span>

                                    </div>

                                    <div class="text--title">
                                        {{ $trip?->user_info['contact_person_number'] }}
                                    </div>

                                    <div class="text--title">
                                        {{ $trip?->user_info['contact_person_email'] }}
                                    </div>

                                </div>
                            </div>



                        @else
                            <div class="text--title">
                                {{ translate('messages.Guest_user') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- End Row -->
    </div>

    <!--Assign Driver Modal -->
    <div class="modal fade" id="assignDriverModal" tabindex="-1" role="dialog"
         aria-labelledby="assignDriverModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header p-2 pb-0 justify-content-end flex-shrink-0">
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <form action="{{ route('vendor.trip.assign.driver') }}" method="post">
                    @csrf
                    <input type="hidden" name="trip_id" value="{{ $trip->id }}">
                    <div class="modal-body px-4 py-0">
                        <h5 class="font-bold">{{ translate('Assign Driver') }}</h5>
                        <div class="fs-12 mb-20">
                        <span id="vehicle-assign-count">
                            {{ count($trip->vehicle_identity->filter(fn($v) => !$v->vehicle_driver_id)) }}
                        </span>
                            {{ translate('Vehicle need to assign driver') }}
                        </div>
                        <div class="card shadow-none">
                            <div class="table-responsive">
                                <table
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="border-0">{{ translate('Vehicle List') }}</th>
                                        <th class="border-0">{{ translate('Selected Driver') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($trip?->vehicle_identity as $vehicleDetails)
                                        <tr>
                                            <td>
                                                <div class="media media--sm">
                                                    <a class="mr-3" href="#">
                                                        <img width="60" height="40" class="img--ratio-2 onerror-image rounded h--40px"
                                                             src="{{ data_get($vehicleDetails?->vehicles,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}"
                                                             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                             alt="Image Description">
                                                    </a>
                                                    <div class="media-body">
                                                        <div class="fs-12 text--title">
                                                            <div class="font-bold">{{ ucwords($vehicleDetails?->vehicles?->name)}}</div>
                                                            <div class="font-semibold opacity-60">{{translate('Car No')}}: {{ $vehicleDetails?->vehicle_identity_data?->license_plate_number }}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column w-100">
                                                    <select name="driver_ids[{{ $vehicleDetails->id }}]"
                                                            class="form-control js-select2-custom driver-select"
                                                            data-placeholder="{{ translate('messages.select_vehicle_driver') }}"
                                                            id="driver_{{ $vehicleDetails->id }}">
                                                        <option value="" selected disabled>
                                                            <span class="fs-12 text--title">{{ translate('Select Vendors') }}</span>
                                                        </option>
                                                        @foreach($trip?->provider?->vehicleDriver as $providerDriver)
                                                            <option value="{{ $providerDriver->id }}"
                                                                    {{ $vehicleDetails->vehicle_driver_id == $providerDriver->id ? 'selected' : '' }}
                                                                    data-driver-id="{{ $providerDriver->id }}">
                                                                <span class="fs-12 text--title">{{ $providerDriver?->fullName }}</span>
                                                                <br>
                                                                <span class="fs-10 text--title opacity-70">({{ $providerDriver->phone }})</span>
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 flex-shrink-0 px-4">
                        <div class="btn--container justify-content-end">
                            <button type="button" data-dismiss="modal" aria-label="Close"
                                    class="btn btn--warning-light min-w-120px">{{ translate('messages.cancel') }}</button>
                            <button type="submit"
                                    class="btn btn--primary min-w-120px">{{ translate('messages.assign') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Assign Vehicle Modal -->
    <div class="modal fade" id="assignVehicleModal" tabindex="-1" role="dialog"
         aria-labelledby="assignVehicleModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header p-2 pb-0 justify-content-end flex-shrink-0">
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <form action="{{ route('vendor.trip.assign.vehicle') }}" method="post">
                    @csrf
                    <div class="modal-body px-4 py-0">
                        <div class="media media--sm flex-wrap mb-20">
                            <a class="mr-3" href="#">
                                <img id="vehicleImage" width="160" class="img-fluid rounded aspect-2-1 onerror-image"
                                     src="{{ asset('public/assets/admin/img/car-demo.png') }}"
                                     data-onerror-image="{{ asset('public/assets/admin/img/car-demo.png') }}"
                                     alt="Image Description">
                            </a>
                            <div class="media-body">
                                <div class="text--title">
                                    <div class="fs-20 font-semibold line--limit-1" id="vehicleName">{{ translate('Vehicle Name') }}</div>
                                    <div class="mb-2"><span class="font-semibold"> {{ translate('Vendor') }}:</span> <span id="vehicleVendor">{{ translate('Vehicle Name') }}</span></div>
                                    <div class="d-flex flex-wrap gap-2 gap-sm-4">
                                        <div><span class="font-semibold"> {{ translate('Category') }}  :</span> <span id="vehicleCategory"> {{ translate('Category') }} </span></div>
                                        <div><span class="font-semibold"> {{ translate('Brand') }}  :</span> <span id="vehicleBrand"> {{ translate('Brand') }} </span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <h5 class="font-bold"> {{ translate('Vehicles List') }}<span class="fs-12 font-regular">({{ translate('Select any of') }} <span id="vehicleQuantity"></span> {{ translate('vehicle') }})</span></h5>
                        <div class="card shadow-none">
                            <div class="table-responsive">
                                <table
                                    class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th class="border-0"> {{ translate('SL.') }}</th>
                                        <th class="border-0"> {{ translate('VIN Number') }}</th>
                                        <th class="border-0"> {{ translate('License Number') }}</th>
                                        <th class="border-0 text-center">{{ translate('Action') }}</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 flex-shrink-0 px-4">
                        <div class="btn--container justify-content-end">
                            <button type="reset" id="reset_btn" data-dismiss="modal" aria-label="Close"
                                    class="btn btn--warning-light min-w-120px">{{ translate('messages.cancel') }}</button>
                            <button type="submit"
                                    class="btn btn--primary min-w-120px">{{ translate('messages.add') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Show Provider location on map Modal -->
    <div class="modal fade" id="providerLocationModal" tabindex="-1" role="dialog"
         aria-labelledby="providerLocationModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header pt-4 px-4">
                    <h4 class="modal-title" id="providerLocationModalLabel">{{ translate('messages.Trip ID #') }} {{ $trip->id }}
                    </h4>
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12 modal_body_map">
                            <div class="location-map" id="location-map">
                                <div class="initial--25 rounded-8 custom_map_canvas" id="provider_map_canvas"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Show Pickup/Destinaton route on map Modal -->
    <div class="modal fade" id="pickupDesModal" tabindex="-1" role="dialog" aria-labelledby="pickupDesModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header pt-4 px-4">
                    <h4 class="modal-title" id="pickupDesModalLabel">{{ translate('messages.Trip ID # ') }}  {{ $trip->id }}</h4>
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-12 modal_body_map">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <!--Show Edit trip Modal -->
    <div class="modal fade" id="editTripModal" tabindex="-1" role="dialog" aria-labelledby="editTripModalLabel">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content modal-scroll">
                <div class="modal-header pt-4 px-4 flex-shrink-0">
                    <h4 class="modal-title" id="editTripModalLabel">{{ translate('messages.Trip ID # ') }}  {{ $trip->id }}</h4>
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <form action="" method="post" id="updateForm">
                    @csrf
                    <input type="hidden" id="pickup-lat" name="pickup_lat">
                    <input type="hidden" id="pickup-lng" name="pickup_lng">
                    <input type="hidden" id="destination-lat" name="destination_lat">
                    <input type="hidden" id="destination-lng" name="destination_lng">
                    <input type="hidden" id="distance-input" name="distance">
                    <input type="hidden" name="trip_id" value="{{ $trip->id }}">


                    <div class="modal-body px-4 py-0">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group text-title">
                                    <label class="input-label font-semibold" for="">{{translate('Pickup Location')}}</label>
                                    <div class="position-relative w-100 d-flex align-items-center">
                                        <input type="text" name="pickup_location" id="pickup-input" class="form-control pr-2"
                                               placeholder="Enter your pickup location"
                                               value="{{ $trip?->pickup_location['location_name'] }}">
                                        <div class="input-icon fs-20 opacity-60">
                                            <i class="tio-poi"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-title">
                                    <label class="input-label font-semibold" for="">{{translate('Destination')}}</label>
                                    <div class="position-relative w-100 d-flex align-items-center">
                                        <input type="text" name="destination_location" id="destination-input" class="form-control pr-2"
                                               placeholder="Enter your destination location"
                                               value="{{ $trip?->destination_location['location_name'] }}">
                                        <div class="input-icon fs-20 opacity-60">
                                            <i class="tio-navigate-outlined rotate-45 d-block"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-title">
                                    <label class="input-label font-semibold" for="">{{translate('Trip Type')}}</label>
                                    <input type="text" class="form-control pr-2" name="trip_type" value="{{ $trip->trip_type }}" hidden>
                                    <input type="text" class="form-control pr-2"  value="{{ translate($trip->trip_type) }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group text-title">
                                    <label class="input-label font-semibold" for="trip-schedule">{{translate('Trip Schedule')}}</label>
                                    <div class="position-relative w-100 d-flex align-items-center">
                                        <input type="datetime-local" name="schedule_at" id="trip-schedule"
                                               value="{{ $trip->schedule_at }}" class="form-control pr-2 opacity-lg"
                                               placeholder="Enter your destination location">
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="card p-0">
                            <div class="card-body p-0 bg--F6F6F6">
                                <!-- item cart -->
                                <div class="table-responsive">
                                    <table
                                        class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table dataTable no-footer mb-0">
                                        <thead class="bg--EDEDED text--title">
                                        <tr>
                                            <th class="border-0">#</th>
                                            <th class="border-0">{{translate('Vehicle Details')}}</th>
                                            <th class="border-0">{{translate('unit Fair')}}</th>
                                            <th class="border-0 text-center">{{translate('Quantity')}}</th>
                                            <th class="border-0 text-center">{{translate('Total Hour/Km')}}</th>
                                            <th class="border-0 text-center">{{translate('Fare')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @php
                                        $subtotal = 0;
                                        @endphp
                                            @foreach($trip->trip_details as $editDetail)
                                        <tr>
                                            <td>
                                                <div class="eta_amount">

                                                    {{ $loop->iteration }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="media media--sm eta_amount">

                                                    <a class="avatar avatar-xl mr-3" href="{{ route('vendor.vehicle.details', $editDetail->vehicle_id) }}">
                                                        <img class="img-fluid rounded aspect-ratio-1 onerror-image"
                                                        src="{{ data_get($editDetail?->vehicle,'thumbnailFullUrl',asset('public/assets/admin/img/160x160/img2.jpg') ) }}"
                                                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                             alt="Image Description">
                                                    </a>
                                                    <div class="media-body">
                                                        <div class="fs-12 text--title">
                                                            <div class="fz-12 font-semibold line--limit-1">
                                                                {{ $editDetail?->vehicle_details['name'] }}</div>
                                                            <div><span class="font-semibold mr-2">{{ translate('Category') }} :</span>{{ $editDetail?->vehicle?->category?->name }}
                                                            </div>
                                                            <div><span class="font-semibold mr-2">{{ translate('Brand') }} :</span>{{ $editDetail?->vehicle?->brand?->name }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fs-14 eta_amount text--title">

                                                    {{ \App\CentralLogics\Helpers::format_currency($editDetail->rental_type == 'hourly' ? $editDetail->vehicle_details['hourly_price'] : $editDetail->vehicle_details['distance_price']) }}
                                                    /{{ $detail->rental_type ==  'hourly' ? translate('Hr') : translate('messages.KM')  }}
                                                </div>
                                            </td>

                                            <td class="text-center">
                                                <div class="d-flex flex-column gap-1 align-items-center">
                                                    <span class="eta_amount  d-none"> </span>
                                                <input type="number" name="quantity" class="form-control fs-14 text--title w--60px quantity-input text-center" min="1" max="{{ $editDetail->vehicle_variations_count }}"
                                                data-max_quantity="{{ $editDetail->vehicle_variations_count }}"
                                                data-max_original_quantity="{{ $editDetail->quantity }}"
                                                data-id="{{ $editDetail->id }}" data-vehicle_id="{{ $editDetail->vehicle_id }}"  value="{{ $editDetail->quantity }}" placeholder="EX:5">
                                            </div>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex flex-column align-items-center">
                                                    <span class="eta_amount  d-none"> </span>
                                                    @if ($trip->trip_type == 'hourly')
                                                    <span> {{ $trip->estimated_hours }} {{ translate('hrs') }}</span>
                                                    @else
                                                    <span class="distance-input">  {{ $trip->distance }} {{  translate('KM')  }}</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="text-center">
                                                <div class="d-flex flex-column gap-1 align-items-center">
                                                    <span class="eta_amount_mt d-none "> {{ translate('*System_EST_Fare:') }}
                                                        <small id="est_{{ $editDetail->id }}" class=" text--warning"> </small>
                                                    </span>
                                                    <input type="text" name="price" min="1" max="999999999"
                                                           data-price="{{ $editDetail->price }}"
                                                           class="form-control w--120px text-center fs-14 text--title fare-total"
                                                           data-id="{{ $editDetail->id }}"
                                                           data-vehicle_id="{{ $editDetail->vehicle_id }}"
                                                           data-old-value="{{ $editDetail->original_price  * $editDetail->quantity}}"
                                                           data-quantity="{{ $editDetail->quantity }}"
                                                           value="{{ \App\CentralLogics\Helpers::format_currency($editDetail->calculated_price) }}"
                                                           placeholder="fare">
                                                </div>
                                            </td>
                                        </tr>
                                            @php
                                            $subtotal += $editDetail->calculated_price;
                                            @endphp
                                        @endforeach
                                        <!-- End Media -->
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mx-3">
                                    <hr>
                                </div>
                                <div class="row justify-content-md-end mb-3 mt-4 mx-0">
                                    <div class="col-md-9 col-lg-8">
                                        <dl class="row text-right text-title">
                                            <dt class="col-6 font-regular">{{translate('Trip Fare')}}</dt>
                                            <dd class="col-6 total_fare">
                                                {{ \App\CentralLogics\Helpers::format_currency($subtotal) }}
                                            </dd>

                                            <dt class="col-6">{{translate('Subtotal')}}</dt>
                                            <dd class="col-6 font-semibold subtotal">
                                                {{ \App\CentralLogics\Helpers::format_currency($subtotal) }}
                                            </dd>

                                            <dt class="col-6 font-regular ">{{translate('discount')}}</dt>
                                            <dd class="col-6 discount_amount">
                                                -{{ \App\CentralLogics\Helpers::format_currency($trip->discount_on_trip)}}
                                            </dd>

                                            <dt class="col-6 font-regular ">{{translate('Coupon discount')}}</dt>
                                            <dd class="col-6 coupon_discount_amount">
                                                -{{ \App\CentralLogics\Helpers::format_currency($trip->coupon_discount_amount)}}
                                            </dd>


                                            @if ($trip->ref_bonus_amount > 0)
                                            <dt class="col-6 font-regular">{{translate('Referral_Discount')}}</dt>
                                            <dd class="col-6 ref_bonus_amount">
                                                -{{ \App\CentralLogics\Helpers::format_currency($trip->ref_bonus_amount)}}
                                            </dd>
                                            @endif

                                            <dt class="col-6 font-regular">{{translate('Vat/Tax')}}
                                                {{ \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first()->value  ? translate('(Included)') : '' }}
                                            </dt>

                                            <dd class="col-6 text-right tax_amount">
                                            {{ \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first()->value  ? '': '+'}} {{ \App\CentralLogics\Helpers::format_currency($trip->tax_amount)}}
                                            </dd>

                                            <dt class="col-6 font-regular ">{{ \App\CentralLogics\Helpers::get_business_data('additional_charge_name')??\App\CentralLogics\Helpers::get_business_data('additional_charge_name')??translate('messages.additional_charge') }}</dt>
                                            <dd class="col-6 text-right additional_charge">
                                                + {{ \App\CentralLogics\Helpers::format_currency($trip->additional_charge) }}</dd>

                                            <dt class="col-6 font-bold">{{translate('Total')}}</dt>
                                            <dd class="col-6 font-bold grand-total">{{ \App\CentralLogics\Helpers::format_currency($trip->trip_amount)}}</dd>
                                        </dl>
                                        <!-- End Row -->
                                    </div>
                                </div>
                                <!-- End Row -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 flex-shrink-0 px-4">
                        <div class="btn--container justify-content-end">
                            <button type="reset" id="reset_btn"  class="btn btn--warning-light min-w-120px close-modal">{{ translate('messages.cancel') }}</button>
                            <button id="edit-trip" type="button"  class="btn btn--primary  min-w-120px">{{ translate('messages.update') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- End Modal -->

    <div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header pt-4 px-4 flex-shrink-0">
                    <h4 class="modal-title">{{ translate('messages.Trip ID # ') }}  {{ $trip->id }}</h4>
                    <button type="button" class="close p-0 m-0" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <input id="search-input" type="text" class="form-control mb-3" placeholder="Search location...">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="currency_symbol" value="{{ \App\CentralLogics\Helpers::currency_symbol() }}">
    <input type="hidden" id="tax_included" value="{{ \App\Models\BusinessSetting::where(['key'=>'tax_included'])->first()?->value }}">
    <div id="max_quantity_msg" data-max-quantity-msg="{{ translate('Maximum available quantity is') }}"></div>
    <div id="get_calculation_url" data-url="{{ route('vendor.trip.get-calculation') }}"></div>

    <div id="provider_latitude" data-provider-latitude="{{ $trip?->provider?->latitude ?? 0 }}"></div>
    <div id="provider_longitude" data-provider-longitude="{{ $trip?->provider?->longitude ?? 0 }}"></div>
    <div id="provider_name" data-provider-name="{{ Str::limit($trip?->provider?->name ?? '', 15, '...') }}"></div>
    <div id="map-marker-image" data-map-marker-image="{{ asset('public/assets/admin/img/icons/pickup.svg') }}"></div>
    <div id="provider_logo" data-provider-logo="{{ $trip?->provider?->logo_full_url ?? asset('public/assets/admin/img/100x100/1.png') }}"></div>
    <div id="provider_address" data-provider-address="{{ $trip?->provider?->address ?? '' }}"></div>
    <div id="pickup-location-lat" data-pickup-location-lat="{{ $trip->pickup_location['lat'] }}"></div>
    <div id="pickup-location-lng" data-pickup-location-lng="{{ $trip->pickup_location['lng'] }}"></div>
    <div id="destination-location-lat" data-destination-location-lat="{{ $trip->destination_location['lat'] }}"></div>
    <div id="destination-location-lng" data-destination-location-lng="{{ $trip->destination_location['lng'] }}"></div>

@endsection

@push('script_2')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8"></script>
    <script src="{{asset('Modules/Rental/public/assets/js/view-pages/provider/trip-details.js')}}"></script>
@endpush
