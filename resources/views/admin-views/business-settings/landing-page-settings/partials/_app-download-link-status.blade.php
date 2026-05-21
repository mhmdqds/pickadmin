@if ($isConfigured)
    <div class="info-notes-bg px-3 py-2 rounded fz-11 gap-2 d-flex align-items-start">
        <img src="{{ asset('public/assets/admin/img/info-idea.svg') }}" alt="">
        <span>
            {{ translate('App download button URL link is setup successfully. Data is synced from ') }}
            <a href="{{ route('admin.business-settings.app-settings') }}" class="fw-semibold text-decoration-underline text-info">{{ translate('App Setup') }}</a>
        </span>
    </div>
@else
    <div class="bg-danger-10 px-3 py-2 rounded fz-11 gap-2 d-flex align-items-start">
        <i class="tio-info text-danger fs-14"></i>
        <span>
            {{ translate('The app download button link is not set up yet. Please complete the setup from ') }}
            <a href="{{ route('admin.business-settings.app-settings') }}" class="fw-semibold text-decoration-underline text-info">{{ translate('App Setup') }}</a>
            {{ translate(' to enable this button. ') }}
        </span>
    </div>
@endif
