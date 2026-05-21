<div class="card card-body shadow-none h-100 pb-2 report-equal-height-card">
    <h3 class="mb-3">{{ translate('messages.Top Earning From Stores') }}</h3>
    <div class="report-scroll-list">

        @forelse ($topStores as $store)
            <div class="border-top py-3 d-flex gap-3 justify-content-between align-items-center flex-wrap">
                <div class="flex-grow-1 d-flex gap-2 align-items-center">
                    <a href="{{ route('admin.store.view', $store->id) }}" target="_blank" rel="noopener noreferrer">
                        <img class="w-40px aspect-1 object-cover rounded"
                            src="{{ \App\CentralLogics\Helpers::get_full_url('store', $store->logo, $store->storage) }}"
                            alt="">
                    </a>
                    <div>
                        <h5 class="fs-13 font-medium mb-0 line--limit-1">{{ $store->store_name }}</h5>
                        <p class="fs-12 mb-0">{{ $store->zone_name }}</p>
                    </div>
                </div>
                <div>
                    <h5 class="font-bold mb-1">
                        {{ \App\CentralLogics\Helpers::format_currency($store->total_earning) }}
                    </h5>
                    <p class="fs-12 mb-0">{{ $store->total_transactions }} {{ translate('orders') }}</p>
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