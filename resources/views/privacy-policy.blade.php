@extends('layouts.landing.app')

@section('title',translate('messages.privacy_policy'))

@section('content')
    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.privacy_policy') }}</h1>
            <div class="breadcrumb">
                <a href="{{route('home')}}">{{ translate('messages.home') }}</a> / {{ translate('messages.privacy_policy') }}
            </div>
        </div>
    </section>

    <!-- Page Content -->
    <section class="page-content">
        <div class="container">
            <div class="content-card">
                {!! $data !!}
            </div>
        </div>
    </section>
@endsection
