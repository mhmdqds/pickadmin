<div class="row g-2" id="order_stats">
    <div class="col-sm-6 col-lg-3">
        <!-- Card -->
        <a class="resturant-card dashboard--card __dashboard-card card--bg-1" href="{{ route('vendor.trip.list', ['status'=>'confirmed']) }}">
            <h4 class="title">{{ $confirmedCount }}</h4>
            <span class="subtitle font-regular ">{{ translate('messages.confirmed') }}</span>
            <img src="{{ asset('public/assets/admin/img/rental/1.png') }}" alt="{{translate('img')}}" class="resturant-icon top-50px">
        </a>
        <!-- End Card -->
    </div>

    <div class="col-sm-6 col-lg-3">
        <!-- Card -->
        <a class="resturant-card dashboard--card __dashboard-card card--bg-2" href="{{ route('vendor.trip.list', ['status'=>'ongoing']) }}">
            <h4 class="title">{{ $ongoingCount }}</h4>
            <span class="subtitle font-regular ">{{ translate('messages.Ongoing_Trip') }}</span>
            <img src="{{ asset('public/assets/admin/img/rental/2.png') }}" alt="{{translate('img')}}" class="resturant-icon top-50px">
        </a>
        <!-- End Card -->
    </div>

    <div class="col-sm-6 col-lg-3">
        <!-- Card -->
        <a class="resturant-card dashboard--card __dashboard-card card--bg-3"
            href="{{ route('vendor.trip.list', ['status'=>'completed']) }}">
            <h4 class="title">{{ $completedCount }}</h4>
            <span class="subtitle font-regular ">{{ translate('messages.completed') }}</span>
            <img src="{{ asset('public/assets/admin/img/rental/3.png') }}" alt="{{translate('img')}}" class="resturant-icon top-50px">
        </a>
        <!-- End Card -->
    </div>

    <div class="col-sm-6 col-lg-3">
        <!-- Card -->
        <a class="resturant-card dashboard--card __dashboard-card card--bg-4"
            href="{{ route('vendor.trip.list',['status'=>'canceled'])  }}">
            <h4 class="title">{{ $canceledCount }}</h4>
            <span class="subtitle font-regular ">{{ translate('messages.canceled') }}</span>
            <img src="{{ asset('public/assets/admin/img/rental/4.png') }}" alt="{{translate('img')}}" class="resturant-icon top-50px">
        </a>
        <!-- End Card -->
    </div>


    <div class="col-12">
        <div class="row g-2">
            <div class="col-sm-6 col-lg-3">
                <a class="order--card badge--accepted h-100" href="{{ route('vendor.trip.list', ['status'=>'all']) }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                            <span>{{ translate('messages.All') }}</span>
                        </h6>
                        <span class="card-title text-success">
                            {{ $totalCount }}
                        </span>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a class="order--card badge--accepted h-100" href="{{ route('vendor.trip.list',  ['status'=>'pending'])  }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                            <span>{{ translate('messages.pending') }}</span>
                        </h6>
                        <span class="card-title text-danger">
                            {{ $pendingCount }}
                        </span>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a class="order--card badge--accepted h-100" href="{{ route('vendor.trip.list', ['status'=>'scheduled']) }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                            <span>{{ translate('messages.scheduled') }}</span>
                        </h6>
                        <span class="card-title text-primary">
                            {{ $scheduledCount }}
                        </span>
                    </div>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a class="order--card badge--accepted h-100" href="{{ route('vendor.trip.list',  ['status'=>'instant']) }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-subtitle d-flex justify-content-between m-0 align-items-center">
                            <span>{{ translate('Instant_Booking') }}</span>
                        </h6>
                        <span class="card-title text-info">
                            {{ $instantCount }}
                        </span>
                    </div>
                </a>
            </div>
        </div>
    </div>

</div>
