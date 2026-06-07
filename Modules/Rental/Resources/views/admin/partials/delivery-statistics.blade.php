<div class="row g-4" id="order_stats">
    <div class="col-lg-3">
        <a class="__card-1 bg-E6F6EE h-100" href="{{ route('admin.rental.trip.list') }}?status=all">
            <img src="{{ asset('public/assets/admin/img/rental/1.png') }}"class="icon"
                 alt="report/new">
            <h3 class="title text-success">{{ $totalCount }}</h3>
            <h6 class="subtitle font-regular">{{ translate('messages.total_trip') }}</h6>
        </a>
    </div>
    <div class="col-lg-9">
        <div class="row g-2">
            <div class="col-sm-6">
                <!-- Card -->
                <a class="resturant-card dashboard--card __dashboard-card card--bg-1" href="{{ route('admin.rental.trip.list') }}?status=pending">
                        <span class="meter">
                            <span style="height:{{ $totalCount > 0 ? ($pendingCount / $totalCount) * 100 : 0 }}%"></span>
                        </span>
                    <h4 class="title">{{ $pendingCount }}</h4>
                    <span class="subtitle font-regular">{{ translate('messages.pending_trip') }}</span>
                    <img src="{{ asset('public/assets/admin/img/rental/5.png') }}" alt="img"
                         class="resturant-icon top-50px">
                </a>
                <!-- End Card -->
            </div>
            <div class="col-sm-6">
                <!-- Card -->
                <a class="resturant-card dashboard--card __dashboard-card card--bg-2" href="{{ route('admin.rental.trip.list') }}?status=ongoing">
                        <span class="meter">
                            <span style="height:{{ $totalCount > 0 ? ($ongoingCount / $totalCount) * 100 : 0 }}%"></span>
                        </span>
                    <h4 class="title">{{ $ongoingCount }}</h4>
                    <span class="subtitle font-regular"> {{ translate('messages.Ongoing_Trip') }}
                        </span>
                    <img src="{{ asset('public/assets/admin/img/rental/2.png') }}" alt="img"
                         class="resturant-icon top-50px">
                </a>
                <!-- End Card -->
            </div>
            <div class="col-sm-6">
                <!-- Card -->
                <a class="resturant-card dashboard--card __dashboard-card bg-F1E8FA" href="{{ route('admin.rental.trip.list') }}?status=completed">
                        <span class="meter">
                            <span style="height:{{ $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0 }}%"></span>
                        </span>
                    <h4 class="title text-success">{{ $completedCount }}</h4>
                    <span class="subtitle font-regular"> {{ translate('messages.Completed') }}
                        </span>
                    <img src="{{ asset('public/assets/admin/img/rental/3.png') }}" alt="img"
                         class="resturant-icon top-50px">
                </a>
                <!-- End Card -->
            </div>
            <div class="col-sm-6">
                <!-- Card -->
                <a class="resturant-card dashboard--card __dashboard-card card--bg-4" href="{{ route('admin.rental.trip.list') }}?status=canceled">
                        <span class="meter">
                            <span style="height:{{ $totalCount > 0 ? ($canceledCount / $totalCount) * 100 : 0 }}%"></span>
                        </span>
                    <h4 class="title">{{ $canceledCount }}</h4>
                    <span class="subtitle font-regular"> {{ translate('messages.Canceled_Trip') }}
                        </span>
                    <img src="{{ asset('public/assets/admin/img/rental/4.png') }}" alt="img"
                         class="resturant-icon top-50px">
                </a>
                <!-- End Card -->
            </div>
        </div>
    </div>
</div>
