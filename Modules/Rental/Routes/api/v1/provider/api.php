<?php

use Illuminate\Support\Facades\Route;

use Modules\Rental\Http\Controllers\Api\Provider\BannerController;
use Modules\Rental\Http\Controllers\Api\Provider\BusinessSettingsController;
use Modules\Rental\Http\Controllers\Api\Provider\ConversationController;
use Modules\Rental\Http\Controllers\Api\Provider\CouponController;
use Modules\Rental\Http\Controllers\Api\Provider\DriverController;
use Modules\Rental\Http\Controllers\Api\Provider\ProviderController;
use Modules\Rental\Http\Controllers\Api\Provider\VehicleController;
use Modules\Rental\Http\Controllers\Api\Provider\ProviderTripController;
use Modules\Rental\Http\Controllers\Api\Public\CouponController as Coupon;
use Modules\Rental\Http\Controllers\Api\Public\BannerController as Banner;
use Modules\Rental\Http\Controllers\Api\Public\VehicleController as Vehicle;
use Modules\Rental\Http\Controllers\Api\Public\VehicleCategoryController as VehicleCategory;
use Modules\Rental\Http\Controllers\Api\Public\VehicleBrandController as VehicleBrand;
use Modules\Rental\Http\Controllers\Api\Public\ProviderController as Provider;
use Modules\Rental\Http\Controllers\Api\User\CartController;
use Modules\Rental\Http\Controllers\Api\User\TripController;
use Modules\Rental\Http\Controllers\Api\User\RentalWishlistController;
use Modules\Rental\Http\Controllers\Api\User\VehicleReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'rental', 'as' => 'rental.',  'middleware' =>  ['localization']], function () {

    Route::group(['prefix' => 'vendor', 'namespace' => 'Provider', 'middleware' => ['vendor.api']], function () {
        Route::group(['prefix' => 'driver', 'as' => 'driver.'], function () {
            Route::get('list', [DriverController::class, 'list']);
            Route::post('create', [DriverController::class, 'store']);
            Route::post('update/{id}', [DriverController::class, 'update']);
            Route::get('details/{id}', [DriverController::class, 'details']);
            Route::get('status/{id}', [DriverController::class, 'status']);
            Route::delete('delete/{id}', [DriverController::class, 'destroy']);
        });

        Route::group(['prefix' => 'vehicle', 'as' => 'vehicle.'], function () {
            Route::get('list', [VehicleController::class, 'list']);
            Route::get('edit/{id}', [VehicleController::class, 'edit']);
            Route::post('create', [VehicleController::class, 'store']);
            Route::post('update/{id}', [VehicleController::class, 'update']);
            Route::get('details/{id}', [VehicleController::class, 'details']);
            Route::get('status/{id}', [VehicleController::class, 'status']);
            Route::get('new-tag/{id}', [VehicleController::class, 'newTag']);
            Route::delete('delete/{id}', [VehicleController::class, 'destroy']);
            Route::get('reviews', [VehicleController::class, 'reviews']);
            Route::put('reply-update', [VehicleController::class, 'updateReply']);
        });

        Route::group(['prefix' => 'banner', 'as' => 'vehicle.'], function () {
            Route::get('list', [BannerController::class, 'list']);
            Route::get('edit/{id}', [BannerController::class, 'edit']);
            Route::post('create', [BannerController::class, 'store']);
            Route::post('update/{id}', [BannerController::class, 'update']);
            Route::get('status/{id}', [BannerController::class, 'status']);
            Route::get('featured/{id}', [BannerController::class, 'featured']);
            Route::delete('delete/{id}', [BannerController::class, 'destroy']);
        });

        Route::group(['prefix' => 'coupon', 'as' => 'coupon.'], function () {
            Route::get('list', [CouponController::class, 'list']);
            Route::post('create', [CouponController::class, 'store']);
            Route::get('edit/{id}', [CouponController::class, 'edit']);
            Route::post('update/{id}', [CouponController::class, 'update']);
            Route::get('status/{id}', [CouponController::class, 'status']);
            Route::delete('delete/{id}', [CouponController::class, 'destroy']);
        });

        Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
            Route::get('/', [ProviderController::class, 'profile']);
            Route::post('update', [ProviderController::class, 'profileUpdate']);
        });

        Route::group(['prefix' => 'schedule', 'as' => 'schedule.'], function () {
            Route::post('/create', [ProviderController::class, 'scheduleStore']);
            Route::delete('delete/{id}', [ProviderController::class, 'scheduleDelete']);
        });

        Route::group(['prefix' => 'message', 'as' => 'message.'], function () {
            Route::get('list', [ConversationController::class, 'conversations']);
            Route::get('search-list', [ConversationController::class, 'search']);
            Route::get('details', [ConversationController::class, 'messages']);
            Route::post('send', [ConversationController::class, 'messagesStore']);
        });

        Route::group(['prefix' => 'trip', 'as' => 'trip.'], function () {
            Route::get('list/{all}', [ProviderTripController::class, 'tripList']);
            Route::get('details', [ProviderTripController::class, 'getTripDetails']);
            Route::get('status', [ProviderTripController::class, 'updateTripStatus']);
            Route::get('payment', [ProviderTripController::class, 'updateTripPaymentStatus']);
            Route::put('assign-vehicle', [ProviderTripController::class, 'assignVehicle']);
            Route::put('assign-driver', [ProviderTripController::class, 'assignDriver']);
            Route::put('edit-trip', [ProviderTripController::class, 'editTrip']);
        });

        Route::get('category/list', [ProviderController::class, 'categoryList']);
        Route::get('brand/list', [ProviderController::class, 'brandList']);
        Route::POST('update-business-setup', [BusinessSettingsController::class, 'updateStoreSetup']);
    });

    Route::group(['middleware' => 'module-check'], function () {
        Route::get('coupon/list/all', [Coupon::class, 'list']);
        Route::group(['prefix' => 'coupon', 'middleware' => 'auth:api'], function () {
            Route::get('list', [Coupon::class, 'list']);
            Route::get('apply', [Coupon::class, 'apply']);
        });
        Route::group(['prefix' => 'banners'], function () {
            Route::get('/', [Banner::class, 'list']);
            Route::get('/{store_id}', [Banner::class, 'getStoreBanners']);
        });
        Route::group(['prefix' => 'vehicle'], function () {
            Route::get('top-rated/', [Vehicle::class, 'topRatedVehicleList']);
            Route::get('search/', [Vehicle::class, 'getSearchedVehicles']);
            Route::get('search/suggestion', [Vehicle::class, 'getSearchedVehiclesSuggestion']);
            Route::get('get-provider-vehicles', [Vehicle::class, 'getProviderWiseVehicles']);
            Route::get('get-vehicle-details/{vehicle}', [Vehicle::class, 'getVehicleDetails']);
            Route::get('category-list/', [VehicleCategory::class, 'vehicleCategoryList']);
            Route::get('brand-list/', [VehicleBrand::class, 'vehicleBrandList']);
            Route::get('popular-suggestion/', [Vehicle::class, 'getPopularSearchlist']);
            Route::get('reviews/{id}', [Vehicle::class, 'getVehicleReviews']);
        });

        Route::group(['prefix' => 'provider'], function () {
            Route::get('get-provider-details/{provider}', [Provider::class, 'getProvidereDetails']);
            Route::get('get-provider-reviews/{provider}', [Provider::class, 'getProvidereReviews']);
            Route::get('popular', [Provider::class, 'getPopularProvider']);
            Route::get('latest', [Provider::class, 'getLatestProvider']);
        });

        Route::group(['prefix' => 'user', 'middleware' => 'apiGuestCheck'], function () {
            Route::group(['prefix' => 'cart'], function () {
                Route::get('get-cart', [CartController::class, 'getCartList']);
                Route::Post('add-to-cart', [CartController::class, 'addToCart']);
                Route::Put('update-cart', [CartController::class, 'updateCart']);
                Route::Put('update-user-data/{user_data}', [CartController::class, 'updateUserData']);
                Route::delete('remove-vehicle/{cart_id}', [CartController::class, 'removeVehicle']);
                Route::delete('remove-cart', [CartController::class, 'removeCart']);
                Route::delete('remove-multiple-cart', [CartController::class, 'removeMultipleVehicles']);
            });
            Route::group(['prefix' => 'trip'], function () {
                Route::Post('trip-booking', [TripController::class, 'tripBooking']);
                Route::get('get-trip-list/{all}', [TripController::class, 'getTripList']);
                Route::get('get-trip-details', [TripController::class, 'getTripDetails']);
                Route::post('payment', [TripController::class, 'makePayment']);
                Route::put('cancel-trip', [TripController::class, 'cancelTrip']);
            });
            Route::group(['middleware' => 'auth:api'], function () {
                Route::group(['prefix' => 'wish-list'], function () {
                    Route::get('/', [RentalWishlistController::class, 'wishlist']);
                    Route::post('add',  [RentalWishlistController::class, 'addToWishlist']);
                    Route::delete('remove',  [RentalWishlistController::class, 'removeFromWishlist']);
                });
                Route::group(['prefix' => 'review'], function () {
                    Route::post('add', [VehicleReviewController::class, 'submitVehicleReview']);
                });
            });
        });
    });

    Route::get('push-notification-test/{id}', [BusinessSettingsController::class, 'testNotification']);
});
