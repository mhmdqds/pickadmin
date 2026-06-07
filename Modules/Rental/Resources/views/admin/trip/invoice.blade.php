@extends('layouts.admin.app')


@section($trip?->trip_status)
active
@endsection

@section('title','')


@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('Modules/Rental/public/assets/css/admin/trip-invoice.css') }}" media="print">
@endpush


@section('content')

@include('rental::admin.trip.partials._invoice')

@endsection
