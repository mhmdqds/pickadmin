@extends('layouts.landing.app')
@section('title', translate('messages.rider_registration'))

@push('css_or_js')
    <style>
        .capcha-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
.capcha-spin:not(.active) {
    animation-play-state: paused;
    -webkit-animation-play-state: paused;
    -moz-animation-play-state: paused;
}

    </style>
@endpush
@section('content')

<?php
  $country=\App\Models\BusinessSetting::where('key','country')->first();
$countryCode= strtolower($country?$country->value:'auto');

?>
    <!-- Page Hero Banner -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.Rider') }} {{ translate('messages.registration') }}</h1>
            <div class="breadcrumb">
                <a href="{{ route('home') }}">{{ translate('messages.home') }}</a> / {{ translate('messages.Rider') }} {{ translate('messages.registration') }}
            </div>
        </div>
    </section>

    <section class="reg-section">
        <div class="reg-container">
            <div class="reg-card">
                <form class="validate-form" action="{{ route('rider.store') }}" method="post" enctype="multipart/form-data" id="form-id">
                    @csrf
                    <!-- Section 1: Rider Info -->
                    <h3 class="sec-head">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        {{ translate('messages.rider_info') }}
                    </h3>

                    <!-- Left: First + Last name | Right: Rider image -->
                    <div class="row-2">
                        <div>
                            <div class="form-group">
                                <label>{{ translate('messages.first_name') }} <span class="req">*</span></label>
                                <input type="text" name="f_name" placeholder="{{ translate('messages.first_name') }}" required value="{{ old('f_name') }}">
                            </div>
                            <div class="form-group">
                                <label>{{ translate('messages.last_name') }} <span class="req">*</span></label>
                                <input type="text" name="l_name" placeholder="{{ translate('messages.last_name') }}" required value="{{ old('l_name') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('messages.rider_image') }} <span class="req">*</span> <span style="font-weight:400;color:var(--text);font-size:.75rem">({{ translate('messages.ratio') }} 1:1)</span></label>
                            <div class="upload-area" id="riderImageArea" onclick="document.getElementById('customFileEg1').click()">
                                <img id="viewer" class="preview-img" src="" alt="" style="display:none">
                                <div class="upload-placeholder">
                                    <div class="upload-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    </div>
                                    <p><strong>{{ translate('Drop Here') }}</strong></p>
                                    <div class="upload-note">{{ translate('Drag & Drop or Click to upload') }} &middot; JPG, PNG ({{ translate('Max') }} 2MB)</div>
                                </div>
                                <div class="upload-change">{{ translate('Click to change image') }}</div>
                                <input type="file" name="image" id="customFileEg1" class="single_file_input" accept=".webp, .jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" required>
                            </div>
                        </div>
                    </div>

                    <!-- Email | Referral Code -->
                    <div class="row-2">
                        <div class="form-group">
                            <label>{{ translate('messages.email') }} <span class="req">*</span></label>
                            <input type="email" name="email" placeholder="{{ translate('messages.Ex:') }} ex@example.com" value="{{ old('email') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('messages.referral_code') }}</label>
                            <input type="text" name="referral_code" placeholder="{{ translate('messages.Ex: STAKXPFIDK') }}" value="{{ old('referral_code') }}">
                        </div>
                    </div>

                    <!-- Zone -->
                    <div class="row-2">
                        <div class="form-group">
                            <label>{{ translate('messages.zone') }} <span class="req">*</span></label>
                            <select name="zone_id" required>
                                <option value="" hidden>{{ translate('messages.select_zone') }}</option>
                                @foreach (\App\Models\Zone::active()->get() as $zone)
                                    @if (isset(auth('admin')->user()->zone_id))
                                        @if (auth('admin')->user()->zone_id == $zone->id)
                                            <option value="{{ $zone->id }}" selected>{{ $zone->name }}</option>
                                        @endif
                                    @else
                                        <option value="{{ $zone->id }}" {{ old('zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div></div>
                    </div>

                    <!-- Section 2: Identity Verification -->
                    <h3 class="sec-head section-gap">
                        <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M6 12h4M6 16h8M14 8h4"/><circle cx="9" cy="8" r="2"/></svg>
                        {{ translate('messages.identity_verification') }}
                    </h3>

                    <!-- Identity type | Identity number -->
                    <div class="row-2">
                        <div class="form-group">
                            <label>{{ translate('messages.identity_type') }} <span class="req">*</span></label>
                            <select name="identity_type">
                                <option value="passport" {{ old('identity_type', 'passport') == 'passport' ? 'selected' : '' }}>{{ translate('messages.passport') }}</option>
                                <option value="driving_license" {{ old('identity_type') == 'driving_license' ? 'selected' : '' }}>{{ translate('messages.driving_license') }}</option>
                                <option value="nid" {{ old('identity_type') == 'nid' ? 'selected' : '' }}>{{ translate('messages.nid') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('messages.identity_number') }} <span class="req">*</span></label>
                            <input type="text" name="identity_number" value="{{ old('identity_number') }}" placeholder="{{ translate('messages.Ex:') }} DH-23434-LS" required>
                        </div>
                    </div>

                    <!-- Identity images -->
                    <div class="form-group">
                        <label>{{ translate('messages.identity_image') }} <span class="req">*</span></label>
                        <div class="row" id="coba"></div>
                    </div>

                    <!-- Section 3: Login Info -->
                    <h3 class="sec-head section-gap">
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        {{ translate('messages.login_info') }}
                    </h3>

                    <!-- Phone | Password -->
                    <div class="row-2">
                        <div class="form-group">
                            <label>{{ translate('messages.phone') }} <span class="req">*</span></label>
                            <input type="tel" name="phone" id="phone" placeholder="{{ translate('messages.Ex:') }} 017********" value="{{ old('phone') }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ translate('messages.password') }} <span class="req">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" name="password" id="rider-password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="{{ translate('messages.Must_contain_at_least_one_number_and_one_uppercase_and_lowercase_letter_and_symbol,_and_at_least_8_or_more_characters') }}" placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}" value="{{ old('password') }}" required>
                                <button type="button" class="eye-btn"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="row-2">
                        <div class="form-group">
                            <label>{{ translate('messages.confirm_password') }} <span class="req">*</span></label>
                            <div class="pw-wrap">
                                <input type="password" id="rider-confirm-password" placeholder="{{ translate('messages.password_length_placeholder', ['length' => '8+']) }}" required>
                                <button type="button" class="eye-btn"><svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                            </div>
                            <div id="pw-match-error" class="form-hint" style="color:#e74c3c;display:none">{{ translate('messages.password_does_not_match') }}</div>
                        </div>
                        <div></div>
                    </div>

                    @include('admin-views.partials._recaptcha')
                    <div class="terms-check">
                        <input type="checkbox" id="riderTerms" />
                        <label for="riderTerms">{{ translate('messages.i_agree_to_the') }} <a href="{{ route('privacy-policy') }}" target="_blank">{{ translate('messages.privacy_policy') }}</a> {{ translate('messages.and') }} <a href="{{ route('terms-and-conditions') }}" target="_blank">{{ translate('messages.terms_and_condition') }}</a></label>
                    </div>
                    <button type="submit" class="submit-btn" id="signInBtn" disabled>{{ translate('messages.submit') }}</button>
                </form>
            </div>
        </div>
    </section>

@endsection

@push('script_2')

    <script>
        class FormValidation {
            constructor(formSelector = '.validate-form') {
                this.formSelector = formSelector;
                this.init();
            }

            init() {
                document.addEventListener('DOMContentLoaded', () => {
                    this.attachValidators();
                    this.initPasswordValidation();
                });
            }

            attachValidators() {
                const forms = document.querySelectorAll(this.formSelector);
                forms.forEach(form => {
                    if (form.dataset.validationInitialized === "true") return;

                    form.setAttribute('novalidate', true);

                    form.addEventListener('submit', (e) => {
                        let isFormValid = FormValidation.validateForm(form);
                        let isFileValid = true;

                        // Confirm password check
                        var pw = document.getElementById('rider-password');
                        var cpw = document.getElementById('rider-confirm-password');
                        if (pw && cpw && pw.value !== cpw.value) {
                            isFormValid = false;
                            document.getElementById('pw-match-error').style.display = 'block';
                            cpw.style.borderColor = '#e74c3c';
                        }

                        // Identity image check
                        var identityImages = form.querySelectorAll('input[name="identity_image[]"]');
                        var hasIdentityImage = false;
                        identityImages.forEach(function(inp) {
                            if (inp.files && inp.files.length > 0) hasIdentityImage = true;
                        });
                        var cobaEl = document.getElementById('coba');
                        if (!hasIdentityImage && cobaEl) {
                            isFormValid = false;
                            var cobaGroup = cobaEl.closest('.form-group');
                            var existingErr = cobaGroup ? cobaGroup.querySelector('.form-validation-error') : null;
                            if (!existingErr && cobaGroup) {
                                var errDiv = document.createElement('div');
                                errDiv.className = 'form-validation-error text-danger mt-1 small';
                                errDiv.textContent = '{{ translate("Please upload at least one identity image.") }}';
                                cobaEl.insertAdjacentElement('afterend', errDiv);
                            }
                        }

                        if (window.fileValidators) {
                            const formValidators = window.fileValidators.filter(validator =>
                                form.contains(validator.input)
                            );
                            if (formValidators.length > 0 && !FileUploadValidator.validateAll(formValidators)) {
                                isFileValid = false;
                            }
                        }

                        if (!isFormValid || !isFileValid) {
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });

                    form.querySelectorAll('input, textarea, select').forEach(input => {
                        input.addEventListener('input', () => {
                            FormValidation.validateInput(input);
                        });
                        input.addEventListener('change', () => {
                            FormValidation.validateInput(input);
                        });
                    });

                    form.dataset.validationInitialized = "true";
                });
            }

            initPasswordValidation() {
                let passwordInput = document.getElementById("signupSrPassword");
                let confirmPasswordInput = document.querySelector("input[name='confirmPassword'], input[name='confirm-password']");
                if (!passwordInput) {
                    passwordInput = document.querySelector("input[name='password']");
                }

                if (!passwordInput) return;

                let rulesContainer = document.getElementById("password-rules");

                if (!rulesContainer) {
                    rulesContainer = document.createElement('div');
                    rulesContainer.id = 'password-rules';
                    rulesContainer.className = 'gap-4 mt-2 small list-unstyled text-muted';
                    rulesContainer.style.display = 'none';
                    rulesContainer.innerHTML = `
                        <ul class="fs-12 d-flex flex-wrap gap-1 list-unstyled">
                            <li id="rule-length"><i class="text-danger">&#10060;</i> 8+ characters</li>
                            <li id="rule-lower"><i class="text-danger">&#10060;</i> Lowercase letter</li>
                            <li id="rule-upper"><i class="text-danger">&#10060;</i> Uppercase letter</li>
                            <li id="rule-number"><i class="text-danger">&#10060;</i> Number</li>
                            <li id="rule-symbol"><i class="text-danger">&#10060;</i> Symbol</li>
                        </ul>
                    `;
                    const container = passwordInput.closest('.form-group') || passwordInput.parentNode;
                    container.appendChild(rulesContainer);
                }

                const rules = {
                    length: rulesContainer.querySelector("#rule-length"),
                    lower: rulesContainer.querySelector("#rule-lower"),
                    upper: rulesContainer.querySelector("#rule-upper"),
                    number: rulesContainer.querySelector("#rule-number"),
                    symbol: rulesContainer.querySelector("#rule-symbol"),
                };

                passwordInput.addEventListener("input", function () {
                    const val = passwordInput.value;

                    if (val.length > 0) {
                        rulesContainer.style.display = "block";
                    } else {
                        rulesContainer.style.display = "none";
                    }

                    FormValidation.updateRule(rules.length, val.length >= 8);
                    FormValidation.updateRule(rules.lower, /[a-z]/.test(val));
                    FormValidation.updateRule(rules.upper, /[A-Z]/.test(val));
                    FormValidation.updateRule(rules.number, /\d/.test(val));
                    FormValidation.updateRule(rules.symbol, /[!@#$%^&*(),.?":{}|<>]/.test(val));
                });

                passwordInput.addEventListener("blur", function () {
                    if (passwordInput.value.length === 0) {
                        rulesContainer.style.display = "none";
                    }
                });

                if (confirmPasswordInput) {
                    const validateMatch = () => {
                        if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                            FormValidation.showError(confirmPasswordInput, confirmPasswordInput.getAttribute('data-msg') || 'Password does not match');
                        } else {
                            FormValidation.clearError(confirmPasswordInput);
                        }
                    };

                    confirmPasswordInput.addEventListener('input', validateMatch);
                    passwordInput.addEventListener('input', () => {
                        if (confirmPasswordInput.value) validateMatch();
                    });
                }
            }

            static updateRule(element, isValid) {
                if (!element) return;
                const icon = element.querySelector("i");
                if (icon) {
                    icon.className = isValid ? "text-success" : "text-danger";
                    icon.innerHTML = isValid ? "&#10004;" : "&#10060;"; // ✓ or ✗
                }
            }

            static validateForm(form) {
                let isValid = true;
                const inputs = form.querySelectorAll('input, textarea, select');

                inputs.forEach(input => {
                    if (!FormValidation.validateInput(input)) {
                        isValid = false;
                    }
                });

                return isValid;
            }

            static validateInput(input) {
                if (input.type === 'hidden' || input.disabled) return true;

                let isValid = true;
                let errorMessage = '';

                FormValidation.clearError(input);

                // Required check — text/select/textarea
                if (input.type !== 'file' && input.hasAttribute('required') && !input.value.trim()) {
                    isValid = false;
                    errorMessage = '{{ translate("This field is required.") }}';
                }

                // Phone validation — must have digits beyond just the country code
                else if (input.type === 'tel' && input.hasAttribute('required')) {
                    const digits = input.value.replace(/[^0-9]/g, '');
                    if (digits.length < 10) {
                        isValid = false;
                        errorMessage = '{{ translate("Please enter a valid phone number.") }}';
                    }
                }

                // Email validation
                else if (input.type === 'email' && input.value.trim()) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(input.value.trim())) {
                        isValid = false;
                        errorMessage = '{{ translate("Please enter a valid email address.") }}';
                    }
                }

                // Confirm password match
                else if ((input.name === 'confirmPassword' || input.name === 'confirm-password') && input.value.trim()) {
                    const passwordInput = document.querySelector("input[name='password']");
                    if (passwordInput && input.value !== passwordInput.value) {
                        isValid = false;
                        errorMessage = input.getAttribute('data-msg') || '{{ translate("Password does not match.") }}';
                    }
                }

                if (!isValid) {
                    FormValidation.showError(input, errorMessage);
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                }

                return isValid;
            }

            static showError(input, message) {
                const formGroup = input.closest('.form-group');
                const container = formGroup ? formGroup : input.parentNode;

                const inputName = input.getAttribute('name') || input.getAttribute('id');
                let errorDiv = container.querySelector(`.form-validation-error[data-for="${inputName}"]`);

                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'form-validation-error text-danger mt-1 small';
                    errorDiv.setAttribute('data-for', inputName);

                    // Find the outermost wrapper to insert after (not inside)
                    const wrapper = input.closest('.pw-wrap') || input.closest('.iti') || input.closest('.input-group');

                    if (wrapper) {
                        wrapper.insertAdjacentElement('afterend', errorDiv);
                    } else {
                        input.insertAdjacentElement('afterend', errorDiv);
                    }
                }

                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }

            static clearError(input) {
                const formGroup = input.closest('.form-group');
                const container = formGroup ? formGroup : input.parentNode;

                const inputName = input.getAttribute('name');
                const errorDiv = container.querySelector(`.form-validation-error[data-for="${inputName}"]`);

                if (errorDiv) {
                    errorDiv.remove();
                }
            }
        }

        window.FormValidation = FormValidation;
        window.formValidation = new FormValidation();

        (function () {
            if (typeof window.FileUploadValidator !== "undefined") {
                return;
            }

            class FileUploadValidator {
                constructor(inputElement, options = {}) {
                    this.config = {
                        maxSize: 2, // MB
                        allowedTypes: ['webp', 'jpg', 'jpeg', 'png', 'gif'],
                        errorElementId: null,
                        required: true,
                        ...options
                    };

                    this.input = inputElement;

                    if (!this.input) {
                        console.error('File input element not found');
                        return;
                    }

                    this.errorElement = this.initErrorElement();
                    this.attachEventListeners();
                }

                initErrorElement() {
                    if (this.config.errorElementId) {
                        return document.getElementById(this.config.errorElementId);
                    }

                    const parentDiv = this.input.closest('.icon-file');
                    if(parentDiv){
                        let errorElement = parentDiv.parentElement.nextElementSibling;
                        if (!errorElement || !errorElement.classList.contains('file-upload-error')) {
                            errorElement = document.createElement('div');
                            errorElement.className = 'file-upload-error text-danger mt-1 small';
                            parentDiv.parentElement.after(errorElement);
                        }
                        return errorElement;
                    }

                    // Fallback
                    const parent = this.input.parentElement;
                    let errorElement = parent.nextElementSibling;
                    if (!errorElement || !errorElement.classList.contains('file-upload-error')) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'file-upload-error text-danger mt-1 small';
                        parent.after(errorElement);
                    }
                    return errorElement;
                }

                attachEventListeners() {
                    this.input.addEventListener('change', () => {
                        this.validate();
                        if (this.input.files && this.input.files.length === 0) {
                            this.removePreview();
                        }
                    });
                }

                removePreview() {
                    this.input.value = '';
                    const viewer = document.getElementById('viewer');
                    if (viewer) {
                        viewer.src = '{{ asset('public/assets/admin/img/upload-img.png') }}';
                    }
                }

                clearError() {
                    if (this.errorElement) {
                        this.errorElement.textContent = '';
                        this.errorElement.style.display = 'none';
                    }
                    this.input.classList.remove('is-invalid');
                }

                showError(message) {
                    if (this.errorElement) {
                        this.errorElement.textContent = message;
                        this.errorElement.style.display = 'block';
                    }
                    this.input.classList.add('is-invalid');
                    return false;
                }

                validate() {
                    this.clearError();

                    if (!this.input.files || this.input.files.length === 0) {
                        if (this.config.required) {
                            return this.showError('{{ translate('messages.please_select_image') }}');
                        }
                        return true;
                    }

                    const file = this.input.files[0];

                    const fileExtension = file.name.split('.').pop().toLowerCase();
                    if (!this.config.allowedTypes.includes(fileExtension)) {
                        return this.showError(`{{ translate('messages.invalid_file_type') }} ${this.config.allowedTypes.join(', ')}`);
                    }

                    const fileSizeMB = file.size / (1024 * 1024);
                    if (fileSizeMB > this.config.maxSize) {
                        return this.showError(`{{ translate('messages.file_size_too_big. ') }}{{ translate('messages.max_file_size_is') }} ${this.config.maxSize}MB.`);
                    }

                    return true;
                }

                clear() {
                    this.input.value = '';
                    this.clearError();
                    this.removePreview();
                }

                static initByClass(className, options = {}) {
                    const inputs = document.querySelectorAll(`.${className}`);
                    const validators = [];

                    inputs.forEach(input => {
                        let maxSize = options.maxSize || 2;
                        if (input.dataset.maxSize) {
                            maxSize = parseFloat(input.dataset.maxSize);
                        }

                        let allowedTypes = options.allowedTypes || ['webp', 'jpg', 'jpeg', 'png', 'gif'];

                        if (input.dataset.allowedTypes) {
                            allowedTypes = input.dataset.allowedTypes.split(',').map(t => t.trim());
                        } else if (input.hasAttribute('accept')) {
                            const acceptTypes = input.getAttribute('accept')
                                .split(',')
                                .map(type => type.trim().replace(/^\./, '').toLowerCase())
                                .filter(type => type.length > 0);

                            if (acceptTypes.length > 0) {
                                allowedTypes = acceptTypes;
                            }
                        }

                        const elementOptions = {
                            maxSize: maxSize,
                            allowedTypes: allowedTypes,
                            required: input.hasAttribute('required')
                        };

                        validators.push(new FileUploadValidator(input, elementOptions));
                    });

                    return validators;
                }

                static validateAll(validators) {
                    let allValid = true;
                    validators.forEach(validator => {
                        if (validator && !validator.validate()) {
                            allValid = false;
                        }
                    });
                    return allValid;
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                window.fileValidators = FileUploadValidator.initByClass('single_file_input', {
                    allowedTypes: ['webp', 'jpg', 'jpeg', 'png', 'gif']
                });
            });

            window.validateFileInputs = function () {
                return FileUploadValidator.validateAll(window.fileValidators || []);
            };
            window.FileUploadValidator = FileUploadValidator;
        })();

        // Rider image preview with upload-area state
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var viewer = document.getElementById('viewer');
                    viewer.src = e.target.result;
                    viewer.style.display = 'block';
                    document.getElementById('riderImageArea').classList.add('has-preview');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function() {
            readURL(this);
        });

        // Rider image drag & drop
        (function(){
            var area = document.getElementById('riderImageArea');
            if(!area) return;
            area.addEventListener('dragover', function(e){ e.preventDefault(); area.style.borderColor='var(--green)'; });
            area.addEventListener('dragleave', function(){ area.style.borderColor=''; });
            area.addEventListener('drop', function(e){
                e.preventDefault(); area.style.borderColor='';
                var inp = document.getElementById('customFileEg1');
                if(inp){ inp.files = e.dataTransfer.files; readURL(inp); }
            });
        })();

        // Password eye toggle
        document.querySelectorAll('.eye-btn').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                var inp = btn.parentElement.querySelector('input');
                var show = inp.type === 'password';
                inp.type = show ? 'text' : 'password';
                btn.innerHTML = show
                    ? '<svg viewBox="0 0 24 24"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                    : '<svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            });
        });

        // Confirm password match validation
        (function(){
            var pw = document.getElementById('rider-password');
            var cpw = document.getElementById('rider-confirm-password');
            var err = document.getElementById('pw-match-error');
            if(!pw || !cpw) return;
            function check(){
                if(cpw.value.length === 0){ err.style.display='none'; cpw.style.borderColor=''; return; }
                if(pw.value !== cpw.value){
                    err.style.display='block';
                    cpw.style.borderColor='#e74c3c';
                } else {
                    err.style.display='none';
                    cpw.style.borderColor='var(--green)';
                }
            }
            cpw.addEventListener('input', check);
            pw.addEventListener('input', function(){ if(cpw.value) check(); });
        })();

        // Terms checkbox - enable/disable submit
        (function(){
            var cb = document.getElementById('riderTerms');
            var btn = document.getElementById('signInBtn');
            if(!cb || !btn) return;
            cb.addEventListener('change', function(){
                btn.disabled = !cb.checked;
            });
        })();

    </script>

    <script src="{{ asset('public/assets/admin/js/spartan-multi-image-picker.js') }}"></script>
    <script type="text/javascript">
        $(function() {
            $("#coba").spartanMultiImagePicker({
                fieldName: 'identity_image[]',
                maxCount: 5,
                rowHeight: '120px',
                groupClassName: 'col-lg-2 col-md-4 col-sm-4 col-6',
                maxFileSize: '{{ MAX_FILE_SIZE * 1024 * 1024 }}',
                placeholderImage: {
                    image: '{{ asset('public/assets/admin/img/upload-img.png') }}',
                    width: '100%',
                },
                dropFileLabel: "Drop Here",
                onAddRow: function(index, file) {

                },
                onRenderedPreview: function(index) {

                },
                onRemoveRow: function(index) {

                },
                onExtensionErr: function(index, file) {
                    toastr.error('{{ translate('messages.please_only_input_png_or_jpg_type_file') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                },
                onSizeErr: function(index, file) {
                    toastr.error('{{ translate('messages.file_size_too_big') }}', {
                        CloseButton: true,
                        ProgressBar: true
                    });
                }
            });
        });

    </script>


    <script>
    $(document).on('click', '.reloadCaptcha', function () {
        $.ajax({
            url: "{{ route('reload-captcha') }}",
            type: "GET",
            dataType: 'json',
            beforeSend: function () {
                $('#loading').show()
                $('.capcha-spin').addClass('active')
            },
            success: function (data) {
                $('#reload-captcha').html(data.view);
            },
            complete: function () {
                $('#loading').hide()
                $('.capcha-spin').removeClass('active')
            }
        });
    });

</script>

@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script src="https://www.google.com/recaptcha/api.js?render={{$recaptcha['site_key']}}"></script>
@endif
@if(isset($recaptcha) && $recaptcha['status'] == 1)
    <script>
        $(document).ready(function () {
            $('#signInBtn').click(function (e) {
                if ($('#set_default_captcha_value').val() == 1) {
                    $('#form-id').submit();
                    return true;
                }
                e.preventDefault();
                if (typeof grecaptcha === 'undefined') {
                    toastr.error('Invalid recaptcha key provided. Please check the recaptcha configuration.');
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');

                    return;
                }
                grecaptcha.ready(function () {
                    grecaptcha.execute('{{$recaptcha['site_key']}}', { action: 'submit' }).then(function (token) {
                        $('#g-recaptcha-response').val(token);
                        $('#form-id').submit();
                    });
                });
                window.onerror = function (message) {
                    var errorMessage = 'An unexpected error occurred. Please check the recaptcha configuration';
                    if (message.includes('Invalid site key')) {
                        errorMessage = 'Invalid site key provided. Please check the recaptcha configuration.';
                    } else if (message.includes('not loaded in api.js')) {
                        errorMessage = 'reCAPTCHA API could not be loaded. Please check the recaptcha API configuration.';
                    }
                    $('#reload-captcha').removeClass('d-none');
                    $('#set_default_captcha_value').val('1');
                    toastr.error(errorMessage)
                    return true;
                };
            });
        });
    </script>
@endif
{{-- recaptcha scripts end --}}
@endpush
