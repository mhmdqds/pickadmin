<div class="border rounded-10 p-3 p-xxl-20">
    <div class="row g-lg-5 gx-3 gy-3 earnings-breakdown">
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                 <div class="flex-shrink-0 purple rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{asset('public/assets/admin/img/report/earning-breakdown/total-delivery-charge.svg')}}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Total Delivery Charge') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">{{ \App\CentralLogics\Helpers::format_currency($summary['breakdown']['delivery_charge'] ?? 0) }}</h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $summary['breakdown']['delivery_charge_percentage'] ?? ($summary['total_earnings'] > 0 ? round(($summary['breakdown']['delivery_charge'] / $summary['total_earnings']) * 100, 1) : 0) }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="item">
                 <div class="flex-shrink-0 success rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{asset('public/assets/admin/img/report/earning-breakdown/total-tips.svg')}}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Total Tips') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">{{ \App\CentralLogics\Helpers::format_currency($summary['breakdown']['dm_tips'] ?? 0) }}</h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $summary['breakdown']['dm_tips_percentage'] ?? ($summary['total_earnings'] > 0 ? round(($summary['breakdown']['dm_tips'] / $summary['total_earnings']) * 100, 1) : 0) }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
    </div>
</div>
