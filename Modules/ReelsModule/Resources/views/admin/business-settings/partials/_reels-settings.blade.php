@php
    $vendor_can_upload_reels = \App\Models\BusinessSetting::where('key', 'vendor_can_upload_reels')->first()?->value ?? 0;
    $reels_max_upload_size_mb = \App\Models\BusinessSetting::where('key', 'reels_max_upload_size_mb')->first()?->value ?? '';
    $reels_max_duration = \App\Models\BusinessSetting::where('key', 'reels_max_duration')->first()?->value ?? '';
    $reels_max_duration_unit = \App\Models\BusinessSetting::where('key', 'reels_max_duration_unit')->first()?->value ?? 'min';
    $reels_upload_limit = \App\Models\BusinessSetting::where('key', 'reels_upload_limit')->first()?->value ?? '';
    $reels_upload_limit_type = \App\Models\BusinessSetting::where('key', 'reels_upload_limit_type')->first()?->value ?? 'week';
    $reels_upload_limit_unlimited = \App\Models\BusinessSetting::where('key', 'reels_upload_limit_unlimited')->first()?->value ?? 1;
@endphp

<div class="card mb-20" id="vendor_can_upload_reels_section">
    <div class="card-body">
        <div class="mb-20">
            <div class="row g-1 align-items-center">
                <div class="col-xxl-9 col-lg-8 col-md-7 col-sm-6">
                    <div>
                        <h4 class="mb-1">
                            {{ translate('Vendor Can Upload Reels') }}
                        </h4>
                        <p class="mb-0 fs-12">
                            {{ translate('When this feature is enabled, Vendors can create and upload reels on the platform.') }}
                        </p>
                    </div>
                </div>
                <div class="col-xxl-3 col-lg-4 col-md-5 col-sm-6">
                    <div class="form-group mb-0">
                        <label class="toggle-switch h--45px toggle-switch-sm d-flex justify-content-between border rounded px-3 py-0 form-control">
                            <span class="pr-1 d-flex align-items-center switch--label">
                                <span class="line--limit-1">
                                    {{ translate('Status') }}
                                </span>
                            </span>
                            <input type="checkbox" data-id="vendor_can_upload_reels"
                                data-type="toggle"
                                data-image-on="{{ asset('/public/assets/admin/img/modal/store-reg-on.png') }}"
                                data-image-off="{{ asset('/public/assets/admin/img/modal/store-reg-off.png') }}"
                                data-title-on="<strong>{{ translate('Want to enable vendor reels upload?') }}</strong>"
                                data-title-off="<strong>{{ translate('Want to disable vendor reels upload?') }}</strong>"
                                data-text-on="<p>{{ translate('If enabled, vendors will be able to create and upload reels on the platform.') }}</p>"
                                data-text-off="<p>{{ translate('If disabled, vendors will no longer be able to upload reels on the platform.') }}</p>"
                                class="status toggle-switch-input dynamic-checkbox-toggle"
                                value="1"
                                name="vendor_can_upload_reels" id="vendor_can_upload_reels"
                                {{ $vendor_can_upload_reels == 1 ? 'checked' : '' }}>
                            <span class="toggle-switch-label text">
                                <span class="toggle-switch-indicator"></span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <div class="bg-opacity-warning-10 px-3 py-2 rounded fz-11  gap-2 align-items-center d-flex ">
                        <img src="{{asset('public/assets/admin/img/info-idea.svg')}}" alt="">
                        <span>
                            {{translate('The upload size limit depends entirely on the server’s file upload configuration settings. Based on those settings, you should configure the Max Upload Size accordingly.')}}
                        </span>
                    </div>
                </div>

            </div>
        </div>
        <div class="bg-light2 rounded p-xxl-20 p-3 {{ $vendor_can_upload_reels == 1 ? '' : 'd-none' }}" id="reels_settings_box">
            <div class="row g-3">
                <div class="col-lg-4 co-sm-6">
                    <div class="form-group mb-0">
                        <label class="input-label text-capitalize" for="reels_max_upload_size_mb">
                            <span class="text-title">
                                {{ translate('Max Upload Size (Mb)') }}
                            </span>
                            <span class="text-danger">*</span>
                            <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                data-original-title="{{ translate('Max upload size for reels (MB)') }}"><i class="tio-info text-muted ps--3"></i></span>
                        </label>
                        <input type="number" name="reels_max_upload_size_mb" id="reels_max_upload_size_mb" class="form-control"
                            placeholder="{{ translate('Ex: 50') }}" value="{{ $reels_max_upload_size_mb }}" min="1"
                            {{ $vendor_can_upload_reels == 1 ? 'required' : '' }}>
                    </div>
                </div>
                <div class="col-lg-4 co-sm-6">
                    <div class="form-group mb-0">
                        <label class="input-label text-capitalize" for="reels_max_duration">
                            <span class="text-title">
                                {{ translate('Max Duration (Min)') }}
                            </span>
                            <span class="text-danger">*</span>
                            <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                data-original-title="{{ translate('Max duration for reels (minutes)') }}"><i class="tio-info text-muted ps--3"></i></span>
                        </label>
                        <div class="d-flex border rounded overflow-hidden">
                            <input type="number" name="reels_max_duration" class="form-control rounded-0 border-0" id="reels_max_duration"
                                value="{{ $reels_max_duration }}" min="1" placeholder="30"
                                {{ $vendor_can_upload_reels == 1 ? 'required' : '' }}>
                            <select name="reels_max_duration_unit" id="reels_max_duration_unit"
                                class="custom-select rounded-0 border-0 bg-modal-btn form-control w-90px fs-12">
                                <option value="min" {{ $reels_max_duration_unit === 'min' ? 'selected' : '' }}>{{ translate('Minutes') }}</option>
                                <option value="hour" {{ $reels_max_duration_unit === 'hour' ? 'selected' : '' }}>{{ translate('Hour') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 co-sm-6">
                    <div class="form-group mb-0">
                        <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between mb-2">
                            <label class="input-label text-capitalize mb-0" for="reels_upload_limit">
                                <span class="text-title">
                                    {{ translate('Reels Upload Quantity') }}
                                </span>
                                <span class="text-danger">*</span>
                                <span class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                    data-original-title="{{ translate('Max number of reels that can be uploaded') }}"><i class="tio-info text-muted ps--3"></i></span>
                            </label>
                            <div class="custom-control custom-checkbox p-0">
                                <input class="mx-2 custom-control-input" type="checkbox" id="reels_upload_limit_unlimited" value="1"
                                    name="reels_upload_limit_unlimited" {{ $reels_upload_limit_unlimited == 1 ? 'checked' : '' }}>
                                <label class="custom-control-label text-title rtl mx-4" for="reels_upload_limit_unlimited">{{ translate('Unlimited') }}</label>
                            </div>
                        </div>
                        <div class="d-flex border rounded overflow-hidden quantity-div" id="reels_quantity_div">
                            <input type="number" name="reels_upload_limit" class="form-control rounded-0 border-0" id="reels_upload_limit"
                                value="{{ $reels_upload_limit }}" min="1" placeholder="30"
                                {{ $vendor_can_upload_reels == 1 && $reels_upload_limit_unlimited != 1 ? 'required' : '' }}>
                            <select name="reels_upload_limit_type" id="reels_upload_limit_type"
                                class="custom-select rounded-0 border-0 bg-modal-btn form-control w-90px fs-12">
                                <option value="week" {{ $reels_upload_limit_type === 'week' ? 'selected' : '' }}>{{ translate('Weekly') }}</option>
                                <option value="month" {{ $reels_upload_limit_type === 'month' ? 'selected' : '' }}>{{ translate('Monthly') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script_2')
    <script>
        $(function () {
            function toggleReelsSettings() {
                let isEnabled = $('#vendor_can_upload_reels').is(':checked');

                $('#reels_settings_box').toggleClass('d-none', !isEnabled);
                $('#reels_max_upload_size_mb, #reels_max_duration').prop('required', isEnabled);

                if (!isEnabled) {
                    $('#reels_max_upload_size_mb, #reels_max_duration').prop('required', false);
                    $('#reels_upload_limit').prop('required', false);
                }

                toggleReelsUnlimited();
            }

            function toggleReelsUnlimited() {
                let isEnabled = $('#vendor_can_upload_reels').is(':checked');
                let isUnlimited = $('#reels_upload_limit_unlimited').is(':checked');

                $('#reels_quantity_div').toggleClass('disabled', isUnlimited || !isEnabled);
                $('#reels_upload_limit, #reels_upload_limit_type').prop('disabled', isUnlimited || !isEnabled);
                $('#reels_upload_limit').prop('required', isEnabled && !isUnlimited);
            }

            toggleReelsSettings();
            $(document).on('change', '#vendor_can_upload_reels', function () {
                toggleReelsSettings();
            });

            $(document).on('change', '#reels_upload_limit_unlimited', function () {
                toggleReelsUnlimited();
            });
        });
    </script>
@endpush
