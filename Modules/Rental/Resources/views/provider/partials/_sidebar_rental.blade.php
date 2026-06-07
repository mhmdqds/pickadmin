<div id="sidebarMain" class="d-none">
    <aside
        class="js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered">
        <div class="navbar-vertical-container">
            <div class="navbar-brand-wrapper justify-content-between">
                <!-- Logo -->

                @php($store_data = \App\CentralLogics\Helpers::get_store_data())
                <a class="navbar-brand" href="{{ route('vendor.dashboard') }}" aria-label="Front">
                    <img class="navbar-brand-logo initial--36  onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ $store_data->logo_full_url }}" alt="Logo">
                    <img class="navbar-brand-logo-mini initial--36 onerror-image"
                        data-onerror-image="{{ asset('public/assets/admin/img/160x160/img2.jpg') }}"
                        src="{{ $store_data->logo_full_url }}" alt="Logo">
                </a>
                <!-- End Logo -->

                <!-- Navbar Vertical Toggle -->
                <button type="button"
                    class="js-navbar-vertical-aside-toggle-invoker navbar-vertical-aside-toggle btn btn-icon btn-xs btn-ghost-dark">
                    <i class="tio-clear tio-lg"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <div class="navbar-nav-wrap-content-left">
                    <!-- Navbar Vertical Toggle -->
                    <button type="button" class="js-navbar-vertical-aside-toggle-invoker close">
                        <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip"
                            data-placement="right" title="Collapse"></i>
                        <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                            data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'></i>
                    </button>
                    <!-- End Navbar Vertical Toggle -->
                </div>

            </div>

            <!-- Content -->
            <div class="navbar-vertical-content text-capitalize bg--005555" id="navbar-vertical-content">
                <form class="sidebar--search-form">
                    <div class="search--form-group">
                        <button type="button" class="btn"><i class="tio-search"></i></button>
                        <input type="text" class="form-control form--control"
                            placeholder="{{ translate('messages.Search Menu...') }}" id="search-sidebar-menu">
                    </div>
                </form>
                <ul class="navbar-nav navbar-nav-lg nav-tabs">
                    <!-- Dashboards -->
                    <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/provider-dashboard*') ? 'active' : '' }}">
                        <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('vendor.providerDashboard') }}"
                            title="{{ translate('messages.dashboard') }}">
                            <i class="tio-home-vs-1-outlined nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.dashboard') }}
                            </span>
                        </a>
                    </li>

                    <?php
                    $tripCount = Illuminate\Support\Facades\DB::select("SELECT
                            COUNT(*) AS total_trips,
                            COALESCE(SUM(CASE WHEN scheduled = 1 THEN 1 ELSE 0 END), 0) AS scheduled_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'confirmed' THEN 1 ELSE 0 END), 0) AS confirmed_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'ongoing' THEN 1 ELSE 0 END), 0) AS ongoing_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'canceled' THEN 1 ELSE 0 END), 0) AS canceled_trips,
                            COALESCE(SUM(CASE WHEN trip_status = 'payment_failed' THEN 1 ELSE 0 END), 0) AS payment_failed_trips
                        FROM trips
                        WHERE provider_id = :provider_id", ['provider_id' => \App\CentralLogics\Helpers::get_store_id()]);

                    $tripCount = (array) $tripCount[0];
                    ?>

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('trip'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.Trip_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/trip*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.Trips') }}">
                                <i class="tio-taxi nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Trips') }}
                                </span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ request()->status == 'all' ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.trip.list') }}?status=all"
                                        title="{{ translate('messages.all_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.all') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ $tripCount['total_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'scheduled' ? 'active' : '' }}">
                                    <a class="nav-link" href="{{ route('vendor.trip.list') }}?status=scheduled"
                                        title="{{ translate('messages.scheduled_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.scheduled') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ $tripCount['scheduled_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'pending' ? 'active' : '' }} @yield('pending')">
                                    <a class="nav-link " href="{{ route('vendor.trip.list') }}?status=pending"
                                        title="{{ translate('messages.pending_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.pending') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{ $tripCount['pending_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                                <li class="nav-item {{ request()->status == 'confirmed' ? 'active' : '' }}  @yield('confirmed')">
                                    <a class="nav-link " href="{{ route('vendor.trip.list') }}?status=confirmed"
                                        title="{{ translate('messages.confirmed_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.confirmed') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{  $tripCount['confirmed_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'ongoing' ? 'active' : '' }} @yield('ongoing')">
                                    <a class="nav-link " href="{{ route('vendor.trip.list') }}?status=ongoing"
                                        title="{{ translate('messages.Ongoing_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.Ongoing') }}
                                            <span class="badge badge-soft-info badge-pill ml-1">
                                                {{  $tripCount['ongoing_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'completed' ? 'active' : '' }} @yield('completed')">
                                    <a class="nav-link text-capitalize"
                                        href="{{ route('vendor.trip.list') }}?status=completed"
                                        title="{{ translate('messages.Completed_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.Completed') }}
                                            <span class="badge badge-soft-success badge-pill ml-1">
                                                {{  $tripCount['completed_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'canceled' ? 'active' : '' }} @yield('canceled')">
                                    <a class="nav-link " href="{{ route('vendor.trip.list') }}?status=canceled"
                                        title="{{ translate('messages.canceled_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container">
                                            {{ translate('messages.canceled') }}
                                            <span class="badge badge-soft-danger  badge-pill ml-1">
                                                {{  $tripCount['canceled_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav-item {{ request()->status == 'payment_failed' ? 'active' : '' }}  @yield('payment_failed')">
                                    <a class="nav-link "
                                        href="{{ route('vendor.trip.list') }}?status=payment_failed"
                                        title="{{ translate('messages.payment_failed_trips') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate sidebar--badge-container text-capitalize">
                                            {{ translate('messages.payment_failed') }}
                                            <span class="badge badge-soft-danger  badge-pill ml-1">
                                                {{  $tripCount['payment_failed_trips'] }}
                                            </span>
                                        </span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                        <!-- Order refund End-->
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('vehicle'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.vehicle_management') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/vehicle/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('Vehicle Setup') }}">
                                <i class="tio-car nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate text-capitalize">{{ translate('Vehicle Setup') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li
                                    class="nav-item {{ Request::is('vendor-panel/vehicle/create')  ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.vehicle.create') }}"
                                        title="{{ translate('messages.create_new') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.create_new') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor-panel/vehicle/list') || Request::is('vendor-panel/vehicle/details/*')|| Request::is('vendor-panel/vehicle/update/*') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.vehicle.list') }}"
                                        title="{{ translate('messages.vehicle_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor-panel/vehicle/bulk-import') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.vehicle.bulk_import') }}"
                                        title="{{ translate('messages.bulk_import') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_import') }}</span>
                                    </a>
                                </li>
                                <li
                                    class="nav-item {{ Request::is('vendor-panel/vehicle/bulk-export') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.vehicle.bulk-export-index') }}"
                                        title="{{ translate('messages.bulk_export') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate text-capitalize">{{ translate('messages.bulk_export') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/vehicle-category*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.vehicle_category.list') }}"
                                title="{{ translate('messages.category list') }}">
                                <i class="tio-category nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.categories') }}
                                </span>
                            </a>
                        </li>

                        <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/vehicle-brand*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.vehicle_brand.list') }}" title="{{ translate('messages.Brand list') }}">
                                <i class="tio-medal nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Brands') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('driver'))
                    <!-- driver -->
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.driver_section') }}">{{ translate('messages.driver_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/driver/create') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.driver.create') }}"
                                title="{{ translate('messages.add_driver') }}">
                                <i class="tio-running nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.add_driver') }}
                                </span>
                            </a>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/driver/list') || Request::is('vendor-panel/driver/details/*') || Request::is('vendor-panel/driver/update/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.driver.list') }}" title="{{ translate('messages.driver') }}">
                                <i class="tio-filter-list nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.driver list') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('marketing'))
                        <li class="nav-item">
                            <small class="nav-subtitle">{{ translate('messages.marketing_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/rental-coupon*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.rental_coupon.list') }}"
                                title="{{ translate('messages.coupons') }}">
                                <i class="tio-ticket nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.coupons') }}</span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/rental-banner*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.rental_banner.list') }}"
                                title="{{ translate('messages.banners') }}">
                                <i class="tio-image nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.banners') }}</span>
                            </a>
                        </li>
                    @endif

                    <li class="nav-item">
                        <small class="nav-subtitle"
                            title="{{ translate('messages.business_section') }}">{{ translate('messages.business_section') }}</small>
                        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                    </li>

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('store_setup'))
                        <li
                            class="nav-item {{ Request::is('vendor-panel/business-settings/store-setup') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.business-settings.store-setup') }}"
                                title="{{ translate('messages.Provider_Config') }}">
                                <span class="tio-settings nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.Provider_Config') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('store_setup'))
                    <li
                        class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/business-settings/notification-setup') ? 'active' : '' }}">
                        <a class="nav-link " href="{{ route('vendor.business-settings.notification-setup') }}"
                            title="{{ translate('messages.notification_setup') }}">
                            <span class="tio-notifications nav-icon"></span>
                            <span class="text-truncate">{{ translate('messages.notification_setup') }}</span>
                        </a>
                    </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('my_shop'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/store/*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.shop.view') }}"
                                title="{{ translate('messages.my_shop') }}">
                                <i class="tio-home nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.my_shop') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('store_setup'))
                    <li class="navbar-vertical-aside-has-menu @yield('subscriberList')">
                        <a class="js-navbar-vertical-aside-menu-link nav-link"
                            href="{{ route('vendor.subscriptionackage.subscriberDetail') }}"
                            title="{{ translate('messages.My_Subscription') }}">
                            <i class="tio-crown nav-icon"></i>
                            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                {{ translate('messages.My_Business_Plan') }}
                            </span>
                        </a>
                    </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('wallet'))
                        <!-- StoreWallet -->
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/wallet') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet.index') }}"
                                title="{{ translate('messages.my_wallet') }}">
                                <i class="tio-table nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.my_wallet') }}</span>
                            </a>
                        </li>


                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/withdraw-method*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.wallet-method.index') }}"
                                title="{{ translate('messages.my_wallet') }}">
                                <i class="tio-museum nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.disbursement_method') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('reviews'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/rental-reviews') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.rental.reviews') }}" title="{{ translate('messages.reviews') }}">
                                <i class="tio-star-outlined nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.reviews') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('chat'))
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/message*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.message.list') }}"
                                title="{{ translate('messages.chat') }}">
                                <i class="tio-chat nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                                    {{ translate('messages.Chat') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('report'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                       title="{{ translate('messages.Report_section') }}">{{ translate('messages.Report_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/report/expense-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.expense-report') }}"
                                title="{{ translate('messages.expense_report') }}">
                                <span class="tio-money nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.expense_report') }}</span>
                            </a>
                        </li>

                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/report/disbursement-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.disbursement-report') }}"
                                title="{{ translate('messages.disbursement_report') }}">
                                <span class="tio-saving nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.disbursement_report') }}</span>
                            </a>
                        </li>
                        <li class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/report/trip-report') ? 'active' : '' }}">
                            <a class="nav-link " href="{{ route('vendor.report.trip-report') }}"
                               title="{{ translate('messages.trip_report') }}">
                                <span class="tio-chart-bar-4 nav-icon"></span>
                                <span class="text-truncate">{{ translate('messages.trip_report') }}</span>
                            </a>
                        </li>
                    @endif

                    @if (\App\CentralLogics\Helpers::employee_module_permission_check('employee'))
                        <li class="nav-item">
                            <small class="nav-subtitle"
                                title="{{ translate('messages.employee_section') }}">{{ translate('messages.employee_section') }}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/custom-role*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                                href="{{ route('vendor.custom-role.list') }}"
                                title="{{ translate('messages.employee_Role') }}">
                                <i class="tio-incognito nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.employee_Role') }}</span>
                            </a>
                        </li>
                        <li
                            class="navbar-vertical-aside-has-menu {{ Request::is('vendor-panel/employee*') ? 'active' : '' }}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                                title="{{ translate('messages.employees') }}">
                                <i class="tio-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ translate('messages.employees') }}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                <li class="nav-item {{ Request::is('vendor-panel/employee/add-new') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.employee.add-new') }}"
                                        title="{{ translate('messages.add_new_Employee') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.add_new') }}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{ Request::is('vendor-panel/employee/list') ? 'active' : '' }}">
                                    <a class="nav-link " href="{{ route('vendor.employee.list') }}"
                                        title="{{ translate('messages.Employee_list') }}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{ translate('messages.list') }}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
            <!-- End Content -->
        </div>
    </aside>
</div>

<div id="sidebarCompact" class="d-none">

</div>

@push('script_2')
    <script src="{{ asset('Modules/Rental/public/assets/js/view-pages/provider/sidebar.js') }}"></script>
@endpush
