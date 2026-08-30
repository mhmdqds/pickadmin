@extends('layouts.landing.app')

@section('title', 'Verify Identity — Delete Account')

@push('css_or_js')
<meta name="robots" content="noindex, nofollow">
<style>
    .delete-account-hero { padding: 64px 0 24px; text-align: center; }
    .delete-account-hero h1 { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 30px; color: #1f1f1f; }
    .delete-account-hero p { color: #555; max-width: 540px; margin: 8px auto 0; font-size: 15px; }
    .da-card { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); border: 1px solid #eee; }
    .da-field { margin-bottom: 18px; }
    .da-field label { display: block; font-weight: 600; margin-bottom: 6px; color: #333; font-size: 14px; }
    .da-field input { width: 100%; padding: 12px 14px; border: 1px solid #d0d0d0; border-radius: 10px; font-size: 15px; }
    .da-field input:focus { outline: none; border-color: #ff6b00; }
    .da-submit { background: #c0392b; color: #fff; padding: 14px 24px; border: 0; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; width: 100%; }
    .da-submit:hover { background: #a93226; }
    .da-error { background: #fde8e8; color: #c0392b; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
    .da-info { background: #eef6ff; color: #1565c0; padding: 14px 16px; border-radius: 10px; margin-bottom: 18px; font-size: 13px; }
    .da-link { color: #ff6b00; text-decoration: none; }
    .da-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="delete-account-hero">
    <h1>Verify It's You</h1>
    <p>We sent a verification request to <strong>{{ $masked_hint }}</strong>. Complete the next step to continue.</p>
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

        @if ($identifier === 'phone' && empty($sent))
            <div class="da-info">
                We could not deliver the verification code. Please double-check the number or <a href="{{ route('delete-account.show') }}" class="da-link">try again</a> with a different method.
            </div>
        @endif

        <form method="POST" action="{{ route('delete-account.verify') }}">
            @csrf
            <input type="hidden" name="identifier" value="{{ $identifier }}">

            @if ($identifier === 'email')
                <div class="da-field">
                    <label for="email">Email address</label>
                    <input type="email" name="email" id="email" required value="{{ old('email') }}" autocomplete="email">
                </div>
                <div class="da-field">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" required minlength="1" autocomplete="current-password">
                </div>
            @else
                <div class="da-field">
                    <label for="phone">Phone number</label>
                    <input type="tel" name="phone" id="phone" required value="{{ old('phone', request('phone')) }}" autocomplete="tel">
                </div>
                <div class="da-field">
                    <label for="otp">Verification code</label>
                    <input type="text" name="otp" id="otp" required inputmode="numeric" pattern="[0-9]*" minlength="4" maxlength="10" autocomplete="one-time-code">
                </div>
                <div class="da-info">
                    Didn't receive a code? It may take up to a minute. <a href="{{ route('delete-account.show') }}" class="da-link">Restart the process</a>.
                </div>
            @endif

            <button type="submit" class="da-submit">Verify</button>
        </form>

        <div style="margin-top: 24px; text-align: center; font-size: 13px; color: #888;">
            <a href="{{ route('delete-account.show') }}" class="da-link">Use a different method</a>
        </div>
    </div>
</div>
@endsection
