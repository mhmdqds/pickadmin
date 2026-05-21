
@foreach($language as $lang)
    <div class="{{ $lang != $defaultLang ? 'd-none' : '' }} lang_form" id="{{ $lang }}-form">
        <div class="row">
            <div class="col-12 mb-2">
                <h3>{{ translate('messages.Customer Notification') }}</h3>
                <hr>
            </div>

            @php($notifications = NOTIFICATION_FOR_RIDE_SHARE_CUSTOMER)
            @include('admin-views.business-settings.partials.notification-block', [
                'notifications' => $notifications,
                'lang' => $lang,
                'mod_type' => $mod_type,
                'user_type' => 'customer',
                'notificationMessages' => $notificationMessages,
            ])

            <div class="col-12 mb-2 mt-3">
                <h3>{{ translate('messages.Driver Notification') }}</h3>
                <hr>
            </div>

            @php($notifications = NOTIFICATION_FOR_RIDE_SHARE_DRIVER)
            @include('admin-views.business-settings.partials.notification-block', [
                'notifications' => $notifications,
                'lang' => $lang,
                'mod_type' => $mod_type,
                'user_type' => 'driver',
                'notificationMessages' => $notificationMessages,
            ])

            <div class="col-12 mb-2 mt-3">
                <h3>{{ translate('messages.Driver Registration Notification') }}</h3>
                <hr>
            </div>

            @php($notifications = NOTIFICATION_FOR_RIDE_SHARE_DRIVER_REGISTRATION)
            @include('admin-views.business-settings.partials.notification-block', [
                'notifications' => $notifications,
                'lang' => $lang,
                'mod_type' => $mod_type,
                'user_type' => 'driver_registration',
                'notificationMessages' => $notificationMessages,
            ])

            <div class="col-12 mb-2 mt-3">
                <h3>{{ translate('messages.Other Notification') }}</h3>
                <hr>
            </div>

            @php($notifications = NOTIFICATION_FOR_RIDE_SHARE_OTHERS)
            @include('admin-views.business-settings.partials.notification-block', [
                'notifications' => $notifications,
                'lang' => $lang,
                'mod_type' => $mod_type,
                'user_type' => 'other',
                'notificationMessages' => $notificationMessages,
            ])

            <input type="hidden" name="lang[]" value="{{ $lang }}">
            <input type="hidden" name="module_type" value="{{ $mod_type }}">
        </div>
    </div>
@endforeach
