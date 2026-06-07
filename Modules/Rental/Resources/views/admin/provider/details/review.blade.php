@extends('layouts.admin.app')

@section('title',$store->name."'s ".translate('messages.reviews'))

@push('css_or_js')
    <!-- Custom styles for this page -->
    <link href="{{asset('public/assets/admin/css/croppie.css')}}" rel="stylesheet">

@endpush

@section('content')
<div class="content container-fluid">
    @include('rental::admin.provider.details.partials._header',['store'=>$store])
    <!-- Page Heading -->
    <div class="tab-content">
        <div class="tab-pane fade show active" id="product">
            <div class="resturant-review-top" id="store_details">
                <div class="resturant-review-left mb-3">
                    @php($user_rating = null)
                    @php($total_rating = 0)
                    @php($total_reviews = 0)
                    <h1 class="title">{{ number_format($avgRating, 1)}}<span class="out-of">/5</span></h1>
                    @if ($avgRating == 5)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                    </div>
                    @elseif ($avgRating < 5 && $avgRating >= 4.5)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-half"></i></span>
                    </div>
                    @elseif ($avgRating < 4.5 && $avgRating >= 4)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 4 && $avgRating >= 3.5)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-half"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 3.5 && $avgRating >= 3)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 3 && $avgRating >= 2.5)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-half"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 2.5 && $avgRating > 2)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 2 && $avgRating >= 1.5)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-half"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 1.5 && $avgRating > 1)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating < 1 && $avgRating > 0)
                    <div class="rating">
                        <span><i class="tio-star-half"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating == 1)
                    <div class="rating">
                        <span><i class="tio-star"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @elseif ($avgRating == 0)
                    <div class="rating">
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                        <span><i class="tio-star-outlined"></i></span>
                    </div>
                    @endif
                    <div class="info">
                        <span>{{$totalReviews}} {{translate('messages.reviews')}}</span>
                    </div>
                </div>
                <div class="resturant-review-right">
                    <ul class="list-unstyled list-unstyled-py-2 mb-0">
                        <!-- Review Ratings -->
                        <li class="d-flex align-items-center font-size-sm">
                            <span class="progress-name mr-3">{{translate('messages.excellent')}}</span>
                            <div class="progress flex-grow-1">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $totalReviews > 0 ? ($excellentCount / $totalReviews) * 100 : 0 }}%;"
                                     aria-valuenow="{{ $totalReviews > 0 ? ($excellentCount / $totalReviews) * 100 : 0 }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ml-3">{{$excellentCount}}</span>
                        </li>
                        <!-- End Review Ratings -->

                        <!-- Review Ratings -->
                        <li class="d-flex align-items-center font-size-sm">
                            <span class="progress-name mr-3">{{translate('messages.good')}}</span>
                            <div class="progress flex-grow-1">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $totalReviews > 0 ? ($goodCount / $totalReviews) * 100 : 0 }}%;"
                                     aria-valuenow="{{ $totalReviews > 0 ? ($goodCount / $totalReviews) * 100 : 0 }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ml-3">{{$goodCount}}</span>
                        </li>
                        <!-- End Review Ratings -->

                        <!-- Review Ratings -->
                        <li class="d-flex align-items-center font-size-sm">
                            <span class="progress-name mr-3">{{translate('messages.average')}}</span>
                            <div class="progress flex-grow-1">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $totalReviews > 0 ? ($averageCount / $totalReviews) * 100 : 0 }}%;"
                                     aria-valuenow="{{ $totalReviews > 0 ? ($averageCount / $totalReviews) * 100 : 0 }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ml-3">{{$averageCount}}</span>
                        </li>
                        <!-- End Review Ratings -->

                        <!-- Review Ratings -->
                        <li class="d-flex align-items-center font-size-sm">
                            <span class="progress-name mr-3">{{translate('messages.below_average')}}</span>
                            <div class="progress flex-grow-1">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $totalReviews > 0 ? ($belowAverageCount / $totalReviews) * 100 : 0 }}%;"
                                     aria-valuenow="{{ $totalReviews > 0 ? ($belowAverageCount / $totalReviews) * 100 : 0 }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ml-3">{{$belowAverageCount}}</span>
                        </li>
                        <!-- End Review Ratings -->

                        <!-- Review Ratings -->
                        <li class="d-flex align-items-center font-size-sm">
                            <span class="progress-name mr-3">{{translate('messages.poor')}}</span>
                            <div class="progress flex-grow-1">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ $totalReviews > 0 ? ($poorCount / $totalReviews) * 100 : 0 }}%;"
                                     aria-valuenow="{{ $totalReviews > 0 ? ($poorCount / $totalReviews) * 100 : 0 }}"
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ml-3">{{$poorCount}}</span>
                        </li>
                        <!-- End Review Ratings -->
                    </ul>
                </div>
            </div>
            <div class="card">

                    <!-- Header -->
            <div class="card-header py-2">
                <div class="search--button-wrapper">
                    <h5 class="card-title">{{translate('messages.Review_list')}}

                        <span class="badge badge-soft-dark ml-2"
                        id="itemCount">{{$tripReviews->total()}}</span>
                    </h5>
                    {{-- <form  class="search-form">
                                    <!-- Search -->
                        @csrf
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" value="{{ request()?->search ?? null }}" name="search" class="form-control"
                                    placeholder="{{translate('ex_:_Search_Store_Name')}}" aria-label="{{translate('messages.search')}}" >
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>

                        </div>
                        <!-- End Search -->
                    </form> --}}
                    <!-- Unfold -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle min-height-40" href="javascript:;"
                            data-hs-unfold-options='{
                                    "target": "#usersExportDropdown",
                                    "type": "css-animation"
                                }'>
                            <i class="tio-download-to mr-1"></i> {{ translate('messages.export') }}
                        </a>

                        <div id="usersExportDropdown"
                            class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">

                            <span class="dropdown-header">{{ translate('messages.download_options') }}</span>
                            <a id="export-excel" class="dropdown-item" href="{{ route('admin.rental.provider.export-review', ['provider_id' => request()->id, 'type' => 'excel', request()->getQueryString()]) }}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/excel.svg"
                                    alt="Image Description">
                                {{ translate('messages.excel') }}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="{{route('admin.rental.provider.export-review', ['provider_id' => request()->id, 'type'=>'csv', request()->getQueryString()])}}">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                    src="{{ asset('public/assets/admin') }}/svg/components/placeholder-csv-format.svg"
                                    alt="Image Description">
                                .{{ translate('messages.csv') }}
                            </a>

                        </div>
                    </div>
                    <!-- End Unfold -->
                </div>
            </div>


                <div class="card-body p-0 verticle-align-middle-table">
                    <div class="table-responsive datatable-custom">
                        <table id="columnSearchDatatable"
                               class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th class="border-0">{{ translate('sl') }}</th>
                                <th class="border-0">{{ translate('messages.Review_ID') }}</th>
                                <th class="w-10p">{{translate('messages.vehicle')}}</th>
                                <th class="border-0">{{ translate('messages.Customer') }}</th>
                                <th class="border-0">{{ translate('messages.Review') }}</th>
                                <th class="border-0">{{ translate('messages.Date') }}</th>
                                <th class="border-0">{{ translate('messages.Provider_Reply') }}</th>
                                <th class="text-center border-0">{{ translate('messages.Status') }}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($tripReviews as $key => $tripReview)
                                <tr>
                                    <td>{{ $key + $tripReviews->firstItem() }}</td>
                                    <td>{{$tripReview->review_id}}</td>

                                    <td class="d-flex">
                                        @if ($tripReview->vehicle)
                                            <a class="media align-items-center mb-1" href="{{route('admin.rental.provider.vehicle.details', $tripReview->vehicle_id)}}">
                                                <img class="avatar avatar-lg mr-3 onerror-image"
                                                     src="{{ $tripReview->vehicle['thumbnailFullUrl'] ?? asset('public/assets/admin/img/160x160/img2.jpg') }}"
                                                     data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}"
                                                     alt="{{ $tripReview->vehicle['name'] }} image">
                                            </a>
                                            <div class="py-2">
                                                <a class="media align-items-center mb-1" href="{{route('admin.rental.provider.vehicle.details', $tripReview->vehicle_id)}}">
                                                    <div class="media-body">
                                                        <h5 class="text-hover-primary mb-0">{{Str::limit($tripReview->vehicle['name'],20,'...')}}</h5>
                                                    </div>
                                                </a>
                                                <a class="mr-5 text-body" href="{{ route('admin.rental.trip.details', $tripReview->trip_id) }}"> {{ translate('Trip_ID') }}: {{ $tripReview->trip_id }}</a>
                                            </div>
                                        @else
                                            {{translate('messages.Trip_deleted!')}}
                                        @endif

                                    </td>

                                    <td>
                                        <div class="table-rest-info d-block">
                                            <div class="info">
                                                <div title="Car Rental Service" class="text--info">
                                                    {{ $tripReview->customer->fullName }}
                                                </div>
                                                <div>
                                                <span class="font-light">
                                                    {{ $tripReview->customer->phone }}
                                                </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text--warning">
                                            <i class="fs-13 tio-star"></i>
                                            {{ $tripReview->rating }}
                                        </div>
                                        @if($tripReview->comment)
                                            <div class="line--limit-2 max-w--220px">
                                                {{ $tripReview->comment  }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $tripReview->reviewDate }}
                                        <br>
                                        {{ $tripReview->reviewTime }}
                                    </td>
                                    <td>
                                        <div class="line--limit-2 max-w--220px">
                                            {{ $tripReview->reply ? $tripReview->reply : 'N/A' }}
                                        </div>
                                    <td>
                                        <label class="toggle-switch toggle-switch-sm" for="publishCheckbox{{ $tripReview->id }}">
                                            <input type="checkbox" data-url="{{ route('admin.rental.provider.vehicle.review.status', $tripReview->id) }}" class="toggle-switch-input redirect-url"
                                                   id="publishCheckbox{{ $tripReview->id }}" {{ $tripReview->status ? 'checked' : ''}}>
                                            <span class="toggle-switch-label mx-auto">
                                            <span class="toggle-switch-indicator"></span>
                                        </span>
                                        </label>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                    </div>
                    @if(count($tripReviews) !== 0)
                        <hr>
                    @endif
                    <div class="page-area mt-3">
                        {!! $tripReviews->appends($_GET)->links() !!}
                    </div>
                    @if(count($tripReviews) === 0)
                        <div class="empty--data">
                            <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                            <h5>
                                {{translate('no_data_found')}}
                            </h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
<div id="title" data-title="{{ translate('Are_you_sure?') }}"></div>
<div id="buttonCancel" data-no="{{ translate('no') }}"></div>
<div id="buttonApprove" data-yes="{{ translate('yes') }}"></div>
@endsection

@push('script_2')
    <script src="{{asset('Modules/Rental/public/assets/js/admin/view-pages/provider-review.js')}}"></script>
@endpush
