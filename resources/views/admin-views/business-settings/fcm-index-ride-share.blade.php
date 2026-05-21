@extends('layouts.admin.app')

@section('title',translate('FCM Settings'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title">
                <span class="page-header-icon">
                    <img src="{{asset('public/assets/admin/img/firebase.png')}}" class="w--26" alt="">
                </span>
                <span>{{translate('messages.firebase_push_notification_setup')}}
                </span>
            </h1>
        </div>
        <!-- End Page Header -->
        <?php
        $mod_type = 'grocery';
        if(request('module_type')){
            $mod_type = request('module_type');
        }
        ?>
        <div class="card">
            <div class="card-header card-header-shadow pb-0">
                <div class="d-flex flex-wrap justify-content-between w-100 row-gap-1">
                    <ul class="nav nav-tabs nav--tabs border-0 gap-2">
                        <li class="nav-item mr-2 mr-md-4">
                            <a href="{{ route('admin.business-settings.fcm-index') }}" class="nav-link pb-2 px-0 pb-sm-3 active" data-slide="1">
                                <img src="{{asset('/public/assets/admin/img/notify.png')}}" alt="">
                                <span>{{translate('Push Notification')}}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.business-settings.fcm-config') }}" class="nav-link pb-2 px-0 pb-sm-3" data-slide="2">
                                <img src="{{asset('/public/assets/admin/img/firebase2.png')}}" alt="">
                                <span>{{translate('Firebase Configuration')}}</span>
                            </a>
                        </li>
                    </ul>
                    <div class="py-1">
                        <div class="tab--content">
                            <div class="item show text--primary-2 d-flex flex-wrap align-items-center" type="button" data-toggle="modal" data-target="#push-notify-modal">
                                <strong class="mr-2">{{translate('Read Documentation')}}</strong>
                                <div class="blinkings">
                                    <i class="tio-info-outined"></i>
                                </div>
                            </div>
                            <div class="item text--primary-2 d-flex flex-wrap align-items-center" type="button" data-toggle="modal" data-target="#firebase-modal">
                                <strong class="mr-2">{{translate('Where to get this information')}}</strong>
                                <div class="blinkings">
                                    <i class="tio-info-outined"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="push-notify">
                        @php($language = \App\Models\BusinessSetting::where('key', 'language')->first())
                        @php($language = $language->value ?? null)
                        @php($defaultLang = 'en')
                        <div class="row justify-content-between">
                            <div class="col-sm-auto mb-5">
                                @if($language)
                                    @php($defaultLang = json_decode($language)[0])
                                    <ul class="nav nav-tabs border-0">
                                        @foreach(json_decode($language) as $lang)
                                            <li class="nav-item">
                                                <a class="nav-link lang_link {{$lang == $defaultLang? 'active':''}}" href="#" id="{{$lang}}-link">{{\App\CentralLogics\Helpers::get_language_name($lang).'('.strtoupper($lang).')'}}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="col-sm-auto mb-5">
                                <select name="module_type" class="form-control js-select2-custom set-filter"
                                    data-url="{{url()->full()}}"
                                    data-filter="module_type"
                                    title="{{translate('messages.select_modules')}}">
                                    @foreach (config('module.module_type') as $module)
                                        <option
                                            value="{{$module}}" {{$mod_type == $module?'selected':''}}>
                                            {{ucfirst(translate($module))}}
                                        </option>
                                    @endforeach
                                </select>
                                <small>{{translate('*Select Module Here')}}</small>
                            </div>
                        </div>
                        <form action="{{ route('admin.business-settings.update-fcm-messages-ride-share') }}" method="post" enctype="multipart/form-data">
                            @csrf

                            @if($language)
                                {!! $formHtml !!}
                            @endif

                            <div class="btn--container justify-content-end">
                                <button type="reset" class="btn btn--reset">{{ translate('messages.reset') }}</button>
                                <button type="submit" class="btn btn--primary">{{ translate('messages.submit') }}</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

        <!-- Firebase Modal -->
        <div class="modal fade" id="push-notify-modal">
            <div class="modal-dialog status-warning-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span aria-hidden="true" class="tio-clear"></span>
                        </button>
                    </div>
                    <div class="modal-body pb-5 pt-0">
                        <div class="single-item-slider owl-carousel">
                            <div class="item">
                                <div class="mb-20">
                                    <div class="text-center">
                                        <img src="{{asset('/public/assets/admin/img/email-templates/3.png')}}" alt="" class="mb-20">
                                        <h5 class="modal-title">{{translate('Write_a_message_in_the_Notification_Body')}}</h5>
                                    </div>
                                    <p>
                                        {{ translate('you_can_add_your_message_using_placeholders_to_include_dynamic_content._Here_are_some_examples_of_placeholders_you_can_use:') }}
                                    </p>
                                    <ul>
                                        <li>
                                            {userName}: {{ translate('the_name_of_the_user.') }}
                                        </li>
                                        <li>
                                            {storeName}: {{ translate('the_name_of_the_store.') }}
                                        </li>
                                        <li>
                                            {orderId}: {{ translate('the_order_id.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="item">
                                <div class="mb-20">
                                    <div class="text-center">
                                        <img src="{{asset('/public/assets/admin/img/firebase/slide-4.png')}}" alt="" class="mb-20">
                                        <h5 class="modal-title">{{translate('Please Visit the Docs to Set FCM on Mobile Apps')}}</h5>
                                    </div>
                                    <div class="text-center">
                                        <p>
                                            {{translate('Please check the documentation below for detailed instructions on setting up your mobile app to receive Firebase Cloud Messaging (FCM) notifications.')}}
                                        </p>
                                        <a href="https://docs.6amtech.com/docs-six-am-mart/mobile-apps/mandatory-setup" target="_blank">{{translate('Click Here')}}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center">
                            <div class="slide-counter"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

