<div class="footer">
    <div class="d-flex justify-content-between align-items-baseline flex-wrap gap-2">
        <div class="text-md-start">
            <p class="font-size-sm mb-0">
                &copy; {{\App\CentralLogics\Helpers::get_business_settings('business_name') }}. <span
                    class="d-none d-sm-inline-block">{{\App\CentralLogics\Helpers::get_business_settings('footer_text')}}</span>
            </p>
        </div>
        <div class="">
            <div class="d-flex justify-content-end">
                <!-- List Dot -->
                <ul class="list-inline list-separator list-separator-before text-left">
                    <li class="list-inline-item">
                        <a class="list-separator-link" href="{{route('admin.business-settings.business-setup')}}">{{translate('messages.business_setup')}}</a>
                    </li>

                    <li class="list-inline-item">
                        <a class="list-separator-link" href="{{route('admin.settings')}}">{{translate('messages.profile')}}</a>
                    </li>

                    <li class="list-inline-item">
                        <!-- Keyboard Shortcuts Toggle -->
                        {{-- <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker h-unset btn btn-icon btn-ghost-secondary rounded-circle"
                               href="{{route('admin.dashboard')}}">
                                {{translate('messages.home')}}
                            </a>
                        </div> --}}
                        <!-- End Keyboard Shortcuts Toggle -->
                        <a class="list-separator-link" href="{{route('admin.dashboard')}}">{{translate('messages.home')}}</a>
                    </li>
                    <li class="list-inline-item d-inline-block">
                        <label class="badge badge-soft-primary m-0">
                            {{translate('messages.software_version')}} : {{env('SOFTWARE_VERSION')}}
                        </label>
                    </li>
                </ul>
                <!-- End List Dot -->
            </div>
        </div>
    </div>
</div>
