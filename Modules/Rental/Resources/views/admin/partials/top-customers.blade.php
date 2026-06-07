@forelse($topCustomers as $customer)
    <a class="grid--card" href="{{ route('admin.users.customer.view', $customer->id)}}">
        <img class="onerror-image"
             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
             src="{{ $customer['image_full_url'] }}">
        <div class="cont pt-2">
            <h6 class="mb-1">{{ $customer->fullName }}</h6>
            <span>{{ $customer->phone }}</span>
        </div>
        <div class="ml-auto">
            <span class="badge badge-soft">{{ translate('Trips') }} : {{ count($customer->trips) }}</span>
        </div>
    </a>
@empty
    <div class="empty--data">
        <img src="{{ asset('/public/assets/admin/svg/illustrations/empty-state.svg') }}" alt="public">
        <h5>
            {{ translate('no_data_found') }}
        </h5>
    </div>
@endforelse
