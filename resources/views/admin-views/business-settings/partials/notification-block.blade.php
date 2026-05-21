@foreach($notifications as $key => $userNotification)
    @php
        $opm = $notificationMessages[$user_type .'_'. $userNotification['key']] ?? null;

        $data = $opm;
        $translate = [];

        if (!empty($opm) && $opm->translations->isNotEmpty()) {
            $translatedMessage = $opm->translations->first(function ($t) use ($lang) {
                return $t->locale == $lang;
            });

            if ($translatedMessage) {
                $translate[$lang]['message'] = $translatedMessage->value;
            }
        }
    @endphp

    <div class="col-md-6 col-lg-4">
        <div class="form-group">
            <div class="d-flex flex-wrap justify-content-between mb-2">
                <span class="d-block form-label">
                    {{ translate($userNotification['value']) }} ({{ strtoupper($lang) }})
                </span>

                <label class="switch--custom-label toggle-switch d-flex align-items-center" for="{{ $user_type .'_'. $userNotification['key'] }}_status_{{ $user_type.''.$lang }}">
                    <input type="checkbox"
                           data-id="{{ $user_type .'_'. $userNotification['key'] }}_status_{{ $user_type.''.$lang }}"
                           data-type="toggle"
                           data-image-on="{{ asset('/public/assets/admin/img/modal/pending-order-on.png') }}"
                           data-image-off="{{ asset('/public/assets/admin/img/modal/pending-order-off.png') }}"
                           data-title-on="{{ translate('By Turning ON') }} <strong>{{ translate($userNotification['key'].' Message') }}</strong>"
                           data-title-off="{{ translate('By Turning OFF') }} <strong>{{ translate($userNotification['key'].' Message') }}</strong>"
                           data-text-on='{!! "<p>" . translate("messages.user_will_get_message_key_" . $userNotification["key"]) . "</p>" !!}'
                           data-text-off='{!! "<p>" . translate("messages.user_will_not_get_message_key_" . $userNotification["key"]) . "</p>" !!}'
                           class="status toggle-switch-input add-required-attribute dynamic-checkbox-toggle"
                           name="{{$user_type .'_'. $userNotification['key'] }}_status"
                           data-textarea-name="{{ $user_type .'_'. $userNotification['key'] }}_messages"
                           value="1"
                           id="{{ $user_type .'_'. $userNotification['key'] }}_status_{{ $user_type.''.$lang }}"
                        {{ isset($data['status']) && $data['status'] == 1 ? 'checked' : '' }}>
                    <span class="toggle-switch-label">
                        <span class="toggle-switch-indicator"></span>
                    </span>
                </label>
            </div>
            <textarea name="{{ $user_type .'_'. $userNotification['key'] }}_message[]" placeholder="{{translate('Write your message')}}" class="form-control pending_messages"@if ($lang == 'en'){{$data?($data['status']==1?'required':''):''}}@endif>{{(isset($translate) && isset($translate[$lang]))?$translate[$lang]['message']:($data?$data['message']:'')}}</textarea>
        </div>
    </div>
@endforeach
