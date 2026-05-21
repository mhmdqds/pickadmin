@if (addon_published_status('Rental') || addon_published_status('RideShare'))
    <ul class="nav nav-tabs border-0 nav--tabs nav--pills mb-4">
        <li class="nav-item">
            <a class="nav-link {{ ($moduleType == 'normal') ? 'active' : '' }}   " href="{{ route('admin.users.customer.view', $customer->id)}}">{{ translate('All_Module') }}</a>
        </li>
        @if (addon_published_status('Rental'))
            <li class="nav-item">
                <a class="nav-link {{ ($moduleType == 'rental') ? 'active' : '' }} " href="{{ route('admin.users.customer.rental.view',['module'=> true,'user_id'=>$customer->id])  }}">{{ translate('Rental_Module') }}</a>
            </li>
        @endif
        
        @if (addon_published_status('RideShare'))
        <li class="nav-item">
            <a class="nav-link {{ ($moduleType == 'ride-share') ? 'active' : '' }} " href="{{ route('admin.users.customer.ride-share.view',['module'=> true,'user_id'=>$customer->id])  }}">{{ translate('RideShare_Module') }}</a>
        </li>
        @endif
    </ul>
@endif