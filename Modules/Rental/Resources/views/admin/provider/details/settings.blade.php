@extends('layouts.admin.app')

@section('title',$store->name."'s ".translate('messages.settings'))

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">

@endpush

@section('content')
<div class="content container-fluid">
    @include('rental::admin.provider.details.partials._header',['store'=>$store])
    <!-- Page Heading -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="vendor">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        <span class="card-header-icon">
                            <img class="w--22" src="{{asset('public/assets/admin/img/store.png')}}" alt="">
                        </span>
                        <span class="p-md-1"> {{translate('messages.vendor_settings')}}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if ($store->store_business_model == 'commission')
                            <div class="col-sm-6 col-lg-4">
                                <div class="form-group mb-0">
                                    <label class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control" for="reviews_section">
                                    <span class="pr-2">{{translate('messages.Show_Reviews_In_vendor_Panel')}}<span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('When_enabled,_vendor_owners_can_see_customer_feedback_in_the_vendor_panel_&_vendor_app.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.show_hide_food_menu')}}"></span> </span>
                                        <input type="checkbox" class="toggle-switch-input redirect-url" data-url="{{route('admin.store.toggle-settings',[$store->id,$store->reviews_section?0:1, 'reviews_section'])}}"  name="reviews_section" id="reviews_section" {{$store->reviews_section?'checked':''}}>
                                        <span class="toggle-switch-label text">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="col-sm-6 col-lg-4">
                            <div class="form-group mb-0">
                                <label class="toggle-switch toggle-switch-sm d-flex justify-content-between border border-secondary rounded px-4 form-control" for="schedule_order">
                                <span class="pr-2">{{translate('messages.scheduled_trip')}}<span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('When_enabled,_vendor_owner_can_take_scheduled_trips_from_customers.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('messages.scheduled_trip_hint')}}"></span></span>
                                    <input type="checkbox" class="toggle-switch-input redirect-url" data-url="{{route('admin.store.toggle-settings',[$store->id,$store->schedule_order?0:1, 'schedule_order'])}}"  id="schedule_order" {{$store->schedule_order?'checked':''}}>
                                    <span class="toggle-switch-label">
                                        <span class="toggle-switch-indicator"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-3">
                        <form action="{{route('admin.rental.provider.update_settings',[$store['id']])}}" method="post"
                            enctype="multipart/form-data" class="col-12">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-6 col-lg-4">
                                    <label class="input-label text-capitalize" for="maximum_delivery_time">{{translate('messages.approx_pickup_time')}}<span class="input-label-secondary" data-toggle="tooltip" data-placement="right" data-original-title="{{translate('Set_the_total_time_to_deliver_products.')}}"><img src="{{asset('/public/assets/admin/img/info-circle.svg')}}" alt="{{translate('Set_the_total_time_to_deliver_products.')}}"></span></label>
                                    <div class="input-group">
                                        <input type="number" name="minimum_pickup_time" class="form-control" placeholder="Min: 10" value="{{explode('-',$store->delivery_time)[0]}}" data-toggle="tooltip" data-placement="top" data-original-title="{{translate('messages.minimum_delivery_time')}}">
                                        <input type="number" name="maximum_pickup_time" class="form-control" placeholder="Max: 20" value="{{explode(' ',explode('-',$store->delivery_time)[1])[0]}}" data-toggle="tooltip" data-placement="top" data-original-title="{{translate('messages.maximum_delivery_time')}}">
                                        <select name="pickup_time_type" class="form-control text-capitalize" id="" required>
                                            <option value="min" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='min'?'selected':''}}>{{translate('messages.minutes')}}</option>
                                            <option value="hours" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='hours'?'selected':''}}>{{translate('messages.hours')}}</option>
                                            <option value="days" {{explode(' ',explode('-',$store->delivery_time)[1])[1]=='days'?'selected':''}}>{{translate('messages.days')}}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group col-sm-6 col-lg-4">
                                    <div class="form-group">
                                        <label class="d-flex justify-content-between switch toggle-switch-sm text-dark" for="tax">
                                            <span>{{translate('messages.vat/tax')}}(%)</span>
                                        </label>
                                        <input type="number" id="tax" min="0" max="100" step="0.01" name="tax" class="form-control" required value="{{$store->tax??'0'}}" {{isset($store->tax)?'':'readonly'}}>
                                    </div>
                                </div>


                                <div class="col-12">
                                    <div class="justify-content-end btn--container">
                                        <button type="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                                        <button type="submit" class="btn btn--primary">{{translate('save_changes')}}</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @if (!config('module.'.$store->module_type)['always_open'])
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">
                            <span class="card-header-icon"><i class="tio-clock"></i></span>
                            <span class="p-md-1">{{translate('messages.Daily time schedule')}}</span>
                        </h5>
                    </div>
                    <div class="card-body" id="schedule">
                        @include('rental::admin.provider.details.partials._schedule', $store)
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create schedule modal -->

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" data-message="{{translate('messages.Create Schedule For ') }} ">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{translate('messages.Create Schedule')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="javascript:" method="post" id="add-schedule" data-route="{{route('admin.store.add-schedule')}}">
                    @csrf
                    <input type="hidden" name="day" id="day_id_input">
                    <input type="hidden" name="store_id" value="{{$store->id}}">
                    <div class="form-group">
                        <label for="recipient-name" class="col-form-label">{{translate('messages.Start time')}}:</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="message-text" class="col-form-label">{{translate('messages.End time')}}:</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                    <button type="submit" class="btn btn-primary">{{translate('messages.Submit')}}</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="title" data-title="{{ translate('Want_to_delete_this_schedule?') }}"></div>
<div id="subTitle" data-sub-title="{{ translate('If_you_select_Yes,_the_time_schedule_will_be_deleted') }}"></div>
<div id="buttonNo" data-no="{{ translate('no') }}"></div>
<div id="buttonYes" data-yes="{{ translate('yes') }}"></div>
<div id="removed" data-removed="{{ translate('messages.Schedule removed successfully') }}"></div>
<div id="added" data-added="{{ translate('messages.Schedule added successfully') }}"></div>
<div id="notFound" data-not-found="{{ translate('Schedule not found') }}"></div>

@endsection

@push('script_2')
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-setting.js')}}"></script>
@endpush
