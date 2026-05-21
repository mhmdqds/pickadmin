<div class="border rounded-10 p-3 p-xxl-20">
    <div class="row g-lg-5 gx-3 gy-3 earnings-breakdown">
        @if(!in_array('parcel',$expenses['order_types']))
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                <div
                    class="flex-shrink-0 danger rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/earning-breakdown/free-delivery.svg') }}"
                        alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Free Delivery Costs') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['free_delivery']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $expenses['free_delivery_percentage'] }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                <div
                    class="flex-shrink-0 warning rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/earning-breakdown/coupon-offers.svg') }}"
                        alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Coupon Offers') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['coupon_discount']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $expenses['coupon_discount_percentage'] }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        @endif
        @if ($include_subscription)

        <div class="col-lg-4 col-sm-6">
            <div class="item">
                <div
                    class="flex-shrink-0 warning rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/4.svg') }}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Discount on Item') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['discount_on_item']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $expenses['discount_on_item_percentage'] }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                <div
                    class="flex-shrink-0 warning rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/new/admin-earning.png') }}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('Add Fund Bonus') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['add_fund_bonus']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">
                    {{ $expenses['add_fund_bonus_percentage'] }}% {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        @endif
        <div class="col-lg-4 col-sm-6">
            <div class="item border-right">
                <div
                    class="flex-shrink-0 warning rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/new/refunded.png') }}" alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Cashback') }}</div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['cashback']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">{{ $expenses['cashback_percentage'] }}%
                    {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        @if(!in_array('parcel',$expenses['order_types']) && $expenses['other'] > 0)
        <div class="col-lg-4 col-sm-6">
            <div class="item">
                <div
                    class="flex-shrink-0 info-light rounded-10 w-40px aspect-1-1 d-flex justify-content-center align-items-center mb-3">
                    <img src="{{ asset('public/assets/admin/img/report/earning-breakdown/tax-payments.svg') }}"
                        alt="earning">
                </div>
                <div class="mb-2">{{ translate('messages.Other') }}
                    <span class="input-label-secondary text--title ml-0 mr-1" data-toggle="tooltip" data-placement="top"
                        data-original-title="{{ translate('Includes User Referral Discounts.') }}">
                        <i class="tio-info text-gray1 fs-16"></i>
                    </span>
                </div>
                <h2 class="font-medium fs-24 fs-18-mobile mb-2">
                    {{ App\CentralLogics\Helpers::format_currency($expenses['other']) }}
                </h2>
                <div class="fs-12 bg-light px-2 py-1 rounded-lg w-max-content">{{ $expenses['other_percentage'] }}%
                    {{ translate('messages.of Total') }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
