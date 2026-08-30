@extends('layouts.landing.app')

@section('title', 'Confirm Account Deletion')

@push('css_or_js')
<meta name="robots" content="noindex, nofollow">
<style>
    .delete-account-hero { padding: 64px 0 24px; text-align: center; }
    .delete-account-hero h1 { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 30px; color: #1f1f1f; }
    .delete-account-hero p { color: #555; max-width: 540px; margin: 8px auto 0; font-size: 15px; }
    .da-card { max-width: 540px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 8px 30px rgba(0,0,0,0.06); border: 1px solid #eee; }
    .da-warning { background: #fff8e1; color: #8a6d00; padding: 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; line-height: 1.5; }
    .da-danger { background: #fde8e8; color: #c0392b; padding: 16px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; line-height: 1.5; }
    .da-submit { background: #c0392b; color: #fff; padding: 14px 24px; border: 0; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; width: 100%; }
    .da-submit:hover { background: #a93226; }
    .da-cancel { background: #fff; color: #555; padding: 14px 24px; border: 1px solid #ccc; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; width: 100%; margin-top: 12px; text-align: center; display: block; text-decoration: none; }
    .da-cancel:hover { background: #fafafa; }
    .da-error { background: #fde8e8; color: #c0392b; padding: 12px 14px; border-radius: 8px; margin-bottom: 18px; font-size: 14px; }
    .da-field { margin-bottom: 18px; }
    .da-field label { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; color: #333; }
    .da-link { color: #ff6b00; text-decoration: none; }
    .da-link:hover { text-decoration: underline; }
</style>
@endpush

@section('content')
<div class="delete-account-hero">
    <h1>Are you sure?</h1>
    <p>This will permanently delete your account. You will not be able to recover it.</p>
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

        <div class="da-danger">
            <strong>This action is irreversible.</strong> Once the deletion is processed:
            <ul style="margin: 8px 0 0 16px; padding: 0;">
                <li>Your personal information will be permanently deleted.</li>
                <li>Your saved addresses, wishlists and carts will be removed.</li>
                <li>You will no longer be able to log in.</li>
                <li>Pending orders (if any) may continue — please contact support if you need help.</li>
                <li>Some financial records may be retained in anonymised form for accounting.</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('delete-account.confirm') }}">
            @csrf
            <input type="hidden" name="request_uuid" value="{{ $request_uuid }}">

            <div class="da-field">
                <label>
                    <input type="checkbox" name="confirm" value="1" required>
                    I understand and want to permanently delete my account.
                </label>
            </div>

            <button type="submit" class="da-submit">Delete my account</button>
            <a href="{{ url('/') }}" class="da-cancel">Cancel</a>
        </form>
    </div>
</div>
@endsection
