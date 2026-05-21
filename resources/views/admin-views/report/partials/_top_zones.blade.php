<div class="card card-body shadow-none h-100 pb-2 report-equal-height-card">
    <h3 class="mb-3">{{ translate('messages.Zone-wise_Earnings') }}</h3>
    <div class="report-scroll-list">

        @forelse ($topZones as $zone)
            <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                <div class="flex-grow-1 d-flex gap-2 align-items-center">
                    <img class="w-40px aspect-1 object-cover rounded"
                        src="{{ asset('public/assets/admin/img/report/earning-breakdown/zone.svg') }}" alt="">
                    <div>
                        <h5 class="fs-13 font-medium mb-0 line--limit-1">{{ $zone['zone_name'] }}</h5>
                        <p class="fs-12 mb-0">
                            {{ $zone['total_order_count'] ?? $zone['total_stores'] }}
                            {{ isset($zone['total_order_count']) ? translate('parcels') : translate('stores') }}
                        </p>
                    </div>
                </div>
                <div>
                    <h5 class="font-bold mb-1">
                        {{ \App\CentralLogics\Helpers::format_currency($zone['total_earning']) }}
                    </h5>
                    <p class="fs-12 mb-0">{{ $zone['percentage_of_earning'] }}% {{ translate('of total') }}</p>
                </div>
            </div>

        @empty
            <div class="empty--data text-center">
                <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                <h5>
                    {{ translate('no_data_found') }}
                </h5>
            </div>
        @endforelse

    </div>
</div>