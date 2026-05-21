@extends('layouts.landing.app')

@section('title',translate('messages.Shipping Policy'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>{{ translate('messages.Shipping Policy') }}</h1>
            <div class="breadcrumb">
                <a href="{{route('home')}}">{{ translate('messages.home') }}</a> / {{ translate('messages.Shipping Policy') }}
            </div>
        </div>
    </section>

    <section class="page-content">
        <div class="container">
            <div class="content-card">
                {!! $data !!}
            </div>
        </div>
    </section>
@endsection
