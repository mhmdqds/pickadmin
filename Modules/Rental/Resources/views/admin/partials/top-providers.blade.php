@forelse($topProviders as $provider)
    <a class="grid--card" href="{{ route('admin.rental.provider.details', $provider->id)}}">
        <img class="onerror-image"
             data-onerror-image="{{ asset('public/assets/admin/img/160x160/img1.jpg') }}"
             src="{{ $provider['logo_full_url'] }}">
        <div class="cont pt-2">
            <h6 class="mb-1">{{ $provider->name }}</h6>
            <span>{{ $provider->phone }}</span>
        </div>
        <div class="ml-auto">
            <span class="badge badge-soft">{{ translate('Trips') }} : {{ count($provider->trips) }}</span>
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
