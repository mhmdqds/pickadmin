<div class="js-nav-scroller hs-nav-scroller-horizontal mb-20">
    <ul class="nav mb-0 nav-tabs border-0 nav--tabs nav--pills">
        <li class="nav-item">
            <a class="nav-link {{ (!request()->tab && !request()->routeIs('admin.transactions.ride-share.report.admin-earning-report')) || request()->tab == 'all' ? 'active' : '' }}"
                href="{{ route('admin.transactions.report.admin-earning-report', array_merge(['tab' => 'all'], $parcelOrderTypes)) }}"
                aria-disabled="true">{{ translate('messages.All Modules') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->tab == 'parcel' ? 'active' : '' }}"
                href="{{ route('admin.transactions.report.admin-earning-report', array_merge(['tab' => 'parcel'], $parcelOrderTypes)) }}"
                aria-disabled="true">{{ translate('messages.Parcel Module') }}</a>
        </li>
        @if (addon_published_status('Rental'))
            <li class="nav-item">
                <a class="nav-link {{ request()->tab == 'rental' ? 'active' : '' }}"
                    href="{{ route('admin.transactions.report.admin-earning-report', ['tab' => 'rental']) }}"
                    aria-disabled="true">{{ translate('messages.Rental Module') }}</a>
            </li>
        @endif
        @if (addon_published_status('RideShare'))
            <li class="nav-item">
                <a class="nav-link {{ request()->tab == 'ride-share' || request()->routeIs('admin.transactions.ride-share.report.admin-earning-report') ? 'active' : '' }}"
                    href="{{ route('admin.transactions.ride-share.report.admin-earning-report') }}"
                    aria-disabled="true">{{ translate('messages.Ride Share') }}</a>
            </li>
        @endif
    </ul>
</div>
