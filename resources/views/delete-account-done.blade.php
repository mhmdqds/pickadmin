@extends('layouts.landing.app')

@section('title', 'Account Deletion Submitted')

@push('css_or_js')
<meta name="robots" content="noindex, nofollow">
<style>
    .delete-account-hero { padding: 80px 0 24px; text-align: center; }
    .delete-account-hero h1 { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 32px; color: #1f1f1f; }
    .delete-account-hero p { color: #555; max-width: 560px; margin: 12px auto 0; font-size: 16px; line-height: 1.5; }
    .da-card { max-width: 540px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); border: 1px solid #eee; text-align: center; }
    .da-icon { font-size: 56px; color: #c0392b; margin-bottom: 8px; }
    .da-link { color: #ff6b00; text-decoration: none; }
    .da-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="delete-account-hero">
    <div class="da-icon">✓</div>
    <h1>Account deletion submitted</h1>
    <p>We've received your deletion request and queued it for processing. You will be logged out automatically. Any data tied to your account will be permanently removed in the background.</p>
</div>

<div class="container" style="max-width: 720px; margin: 0 auto 80px;">
    <div class="da-card">
        <p>If you have any outstanding orders or a refund request, please contact support. Some financial records may be retained in anonymised form to comply with legal and accounting obligations — see our <a href="{{ url('privacy-policy') }}" class="da-link">Privacy Policy</a> for details.</p>

        <a href="{{ url('/') }}" class="da-link" style="display: inline-block; margin-top: 16px;">Return to homepage</a>
    </div>
</div>
@endsection
