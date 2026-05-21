@extends('layouts.landing.app')

@section('title',translate('messages.about_us'))

@section('content')
    <!-- Page Hero -->
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.about_us') }}</h1>
            <div class="breadcrumb">
                <a href="{{route('home')}}">{{ translate('messages.home') }}</a> / {{ translate('messages.about_us') }}
            </div>
        </div>
    </section>

    <!-- Page Content -->
    <section class="page-content">
        <div class="container">
            <div class="content-card">
                <h2>{{ $data_title }}</h2>
                <div>{!! $data !!}</div>
            </div>
        </div>
    </section>
@endsection
