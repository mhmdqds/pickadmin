@extends('layouts.landing.app')

@section('title', 'Delete Account')

@push('css_or_js')
<meta name="robots" content="noindex, nofollow">
<style>
    .delete-account-hero { padding: 64px 0 32px; text-align: center; }
    .delete-account-hero h1 { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 38px; color: #1f1f1f; }
    .delete-account-hero p { color: #555; max-width: 640px; margin: 12px auto 0; font-size: 16px; }
    .da-card {
        max-width: 560px;
        margin: 0 auto;
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        border: 1px solid #eee;
    }
    .da-method-toggle { display: flex; gap: 12px; margin-bottom: 24px; }
    .da-method-toggle button {
        flex: 1;
        padding: 14px;
        border: 2px solid #e6e6e6;
        border-radius: 12px;
        background: #fafafa;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.15s;
    }
    .da-method-toggle button.active { border-color: #ff6b00; background: #fff7f0; color: #ff6b00; }
    .da-method-toggle button:hover:not(.active) { border-color: #bbb; }
    .da-field { margin-bottom: 18px; }
    .da-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 14px; }
    .da-field input {
        width: 100%;
        padding: 12px 14px;
        border: 1px solid #d0d0d0;
        border-radius: 10px;
        font-size: 15px;
        transition: border-color 0.15s;
    }
    .da-field input:focus { outline: none; border-color: #ff6b00; }
    .da-submit {
        background: #c0392b;
        color: #fff;
        padding: 14px 24px;
        border: 0;
        border-radius: 10px;
        font-weight: 700;
        font-size: 15px;
        cursor: pointer;
        width: 100%;
        transition: background 0.15s;
    }
    .da-submit:hover { background: #a93226; }
    .da-error { background: #fde8e8; color: #c0392b; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
    .da-warning { background: #fff8e1; color: #8a6d00; padding: 14px 16px; border-radius: 10px; margin-top: 24px; font-size: 13px; line-height: 1.5; }
    .da-link { color: #ff6b00; text-decoration: none; }
    .da-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="delete-account-hero">
    <h1>Delete Your Account</h1>
    <p>Use this page to permanently delete your account and the personal data we hold about you. This action is irreversible.</p>
</div>

<div class="container" style="max-width: 720px; margin: 0 auto 80px;">
    <div class="da-card">
        @if ($errors->any())
            <div class="da-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <h2 style="margin-top: 0; font-size: 20px;">How would you like to verify?</h2>

        <form method="POST" action="{{ route('delete-account.start') }}">
            @csrf

            <div class="da-method-toggle">
                <button type="button" id="btn-email" class="active" onclick="daSwitch('email')">
                    Email + Password
                </button>
                <button type="button" id="btn-phone" onclick="daSwitch('phone')">
                    Phone + OTP
                </button>
            </div>

            <input type="hidden" name="identifier" id="identifier" value="email">

            <div id="email-block" class="da-field">
                <label for="email">Email address</label>
                <input type="email" name="email" id="email" placeholder="you@example.com" autocomplete="email">
            </div>

            <div id="phone-block" class="da-field" style="display: none;">
                <label for="phone">Phone number (with country code)</label>
                <input type="tel" name="phone" id="phone" placeholder="+1 555 0100" autocomplete="tel">
            </div>

            <button type="submit" class="da-submit">Continue</button>

            <div class="da-warning">
                <strong>Heads up:</strong> After verification, we'll ask you to confirm once more before the deletion is queued. Certain financial records (e.g. completed order history) may be retained in anonymised form to comply with legal and accounting obligations, but all personal identifiers will be removed.
                <br><br>
                See our <a href="{{ url('privacy-policy') }}" class="da-link">Privacy Policy</a> for details.
            </div>
        </form>
    </div>
</div>

<script>
    function daSwitch(which) {
        document.getElementById('identifier').value = which;
        document.getElementById('btn-email').classList.toggle('active', which === 'email');
        document.getElementById('btn-phone').classList.toggle('active', which === 'phone');
        document.getElementById('email-block').style.display = which === 'email' ? 'block' : 'none';
        document.getElementById('phone-block').style.display = which === 'phone' ? 'block' : 'none';
        if (which === 'email') {
            document.getElementById('email').setAttribute('required', 'required');
            document.getElementById('phone').removeAttribute('required');
        } else {
            document.getElementById('phone').setAttribute('required', 'required');
            document.getElementById('email').removeAttribute('required');
        }
    }
    daSwitch('email');
</script>
@endsection
