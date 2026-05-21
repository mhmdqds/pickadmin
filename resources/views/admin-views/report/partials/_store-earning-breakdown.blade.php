<div class="border rounded-10 p-3 p-xxl-20 mb-20">
    <div class="row g-lg-5 gx-3 gy-3 earnings-breakdown">
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                 <div class="flex-shrink-0 info rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{asset('public/assets/admin/img/report/earning-breakdown/order-commission.svg')}}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Order Sales') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">{{ \App\CentralLogics\Helpers::format_currency($summary['breakdown']['order_sales'] ?? 0) }}</h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $summary['breakdown']['order_sales_percentage'] ?? 0 }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                 <div class="flex-shrink-0 success rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{asset('public/assets/admin/img/report/earning-breakdown/tax-collected.svg')}}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Tax Collected') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">{{ \App\CentralLogics\Helpers::format_currency($summary['breakdown']['tax_collected'] ?? 0) }}</h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $summary['breakdown']['tax_collected_percentage'] ?? 0 }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="item text-sm-left">
                 <div class="flex-shrink-0 info-light rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{asset('public/assets/admin/img/report/earning-breakdown/other-income.svg')}}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Packaging Charge') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">{{ \App\CentralLogics\Helpers::format_currency($summary['breakdown']['packaging_fee_collected'] ?? 0) }}</h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $summary['breakdown']['packaging_fee_collected_percentage'] ?? 0 }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
    </div>
</div>
