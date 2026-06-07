<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\Web\Provider\DriverController;
use Modules\Rental\Http\Controllers\Web\Provider\Promotions\BannerController;
use Modules\Rental\Http\Controllers\Web\Provider\Promotions\CouponController;
use Modules\Rental\Http\Controllers\Web\Provider\ProviderController;
use Modules\Rental\Http\Controllers\Web\Provider\ReportController;
use Modules\Rental\Http\Controllers\Web\Provider\VehicleController;
use Modules\Rental\Http\Controllers\Web\Provider\TripController;
use Modules\Rental\Http\Controllers\Web\Provider\ProviderDashBoardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([ 'middleware' => ['vendor', 'provider-rental-module']], function () {
    Route::group(['prefix' => 'provider-dashboard'], function () {
        Route::get('/', [ProviderDashBoardController::class, 'providerDashboard'])->name('providerDashboard');
        Route::get('delivery-statistics', [ProviderDashBoardController::class, 'deliveryStatistics'])->name('deliveryStatistics');
        Route::get('commission-overview', [ProviderDashBoardController::class, 'commissionOverview'])->name('commissionOverview');
    });

    Route::group(['prefix' => 'vehicle', 'as' => 'vehicle.', 'middleware' => ['module:vehicle']], function () {
        Route::get('list', [VehicleController::class, 'index'])->name('list');
        Route::get('create', [VehicleController::class, 'create'])->name('create');
        Route::post('create', [VehicleController::class, 'store']);
        Route::get('update/{id}', [VehicleController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [VehicleController::class, 'update']);
        Route::get('details/{id}', [VehicleController::class, 'details'])->name('details');
        Route::get('status/{id}', [VehicleController::class, 'status'])->name('status');
        Route::get('new-tag/{id}', [VehicleController::class, 'newTag'])->name('new-tag');
        Route::delete('delete/{id}', [VehicleController::class, 'destroy'])->name('delete');
        Route::get('export', [VehicleController::class, 'export'])->name('export');
        Route::get('review-status/{id}', [VehicleController::class, 'reviewStatus'])->name('review.status');
        Route::get('review-export', [VehicleController::class, 'reviewExport'])->name('review.export');

        Route::get('bulk-import', [VehicleController::class, 'bulkImportIndex'])->name('bulk_import');
        Route::POST('bulk-import', [VehicleController::class, 'bulkImportData']);
        Route::get('bulk-export', [VehicleController::class, 'bulkExportIndex'])->name('bulk-export-index');
        Route::POST('bulk-export', [VehicleController::class, 'bulkExportData']);
    });

    Route::group(['prefix' => 'vehicle-category', 'as' => 'vehicle_category.', 'middleware' => ['module:vehicle']], function () {
        Route::get('list', [ProviderController::class, 'categoryList'])->name('list');
        Route::get('export', [ProviderController::class, 'categoryExport'])->name('export');
    });


    Route::group(['prefix' => 'trip', 'as' => 'trip.', 'middleware' => ['module:trip']], function () {
        Route::get('/', [TripController::class,'list'])->name('list');
        Route::get('details/{id}', [TripController::class,'details'])->name('details');
        Route::get('status/{id}/{status}', [TripController::class,'status'])->name('status');
        Route::get('payment/status/{id}/{status}', [TripController::class,'paymentStatus'])->name('payment.status');
        Route::post('assign/vehicle', [TripController::class,'assignVehicle'])->name('assign.vehicle');
        Route::post('assign/driver', [TripController::class,'assignDriver'])->name('assign.driver');
        Route::get('export', [TripController::class, 'export'])->name('export');
        Route::post('get-calculation', [TripController::class, 'update'])->name('get-calculation');
        Route::get('generate-invoice/{id}', [TripController::class, 'generateInvoice'])->name('generate-invoice');
        Route::get('print-invoice/{id}', [TripController::class, 'printInvoice'])->name('print-invoice');
    });

    Route::group(['prefix' => 'vehicle-brand', 'as' => 'vehicle_brand.', 'middleware' => ['module:vehicle']], function () {
        Route::get('list', [ProviderController::class, 'brandList'])->name('list');
        Route::get('export', [ProviderController::class, 'brandExport'])->name('export');
    });

    Route::group(['prefix' => 'rental-banner', 'as' => 'rental_banner.', 'middleware' => ['module:marketing']], function () {
        Route::get('/', [BannerController::class,'list'])->name('list');
        Route::post('/', [BannerController::class,'store']);
        Route::get('edit/{banner}', [BannerController::class,'edit'])->name('edit');
        Route::post('edit/{banner}', [BannerController::class,'update'])->name('update');
        Route::delete('delete/{banner}', [BannerController::class,'destroy'])->name('delete');
        Route::get('status/{banner}/{status}', [BannerController::class,'status'])->name('status');
        Route::get('featured/{banner}/{status}', [BannerController::class,'updateFeatured'])->name('featured');
        Route::get('export', [BannerController::class, 'export'])->name('export');
    });

    Route::group(['prefix' => 'rental-coupon', 'as' => 'rental_coupon.', 'middleware' => ['module:marketing']], function () {
        Route::get('/', [CouponController::class,'list'])->name('list');
        Route::post('/', [CouponController::class,'store']);
        Route::get('edit/{id}', [CouponController::class,'edit'])->name('edit');
        Route::post('edit/{id}', [CouponController::class,'update']);
        Route::delete('delete/{coupon}', [CouponController::class,'destroy'])->name('delete');
        Route::get('status/{coupon}', [CouponController::class,'status'])->name('status');
        Route::get('export', [CouponController::class, 'export'])->name('export');
    });

    Route::group(['prefix' => 'driver', 'as' => 'driver.', 'middleware' => ['module:driver']], function () {
        Route::get('list', [DriverController::class, 'list'])->name('list');
        Route::get('/create', [DriverController::class, 'create'])->name('create');
        Route::post('/create', [DriverController::class, 'store']);
        Route::get('update/{id}', [DriverController::class, 'edit'])->name('edit');
        Route::post('update/{id}', [DriverController::class, 'update']);
        Route::get('details/{id}', [DriverController::class, 'details'])->name('details');
        Route::get('status/{id}', [DriverController::class, 'status'])->name('status');
        Route::delete('delete/{id}', [DriverController::class, 'destroy'])->name('delete');
        Route::get('export', [DriverController::class, 'export'])->name('export');
        Route::get('trip-export', [DriverController::class, 'tripExport'])->name('trip.export');

    });

    Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
        Route::get('trip-report', [ReportController::class, 'tripReport'])->name('trip-report');
        Route::get('trip-report-export', [ReportController::class, 'tripReportExport'])->name('trip-report-export');
    });

    Route::group(['prefix' => 'custom-role', 'as' => 'custom-role.', 'middleware' => ['module:employee']], function () {
        Route::get('list', [ProviderController::class, 'role'])->name('list');
        Route::get('update/{id}', [ProviderController::class, 'update'])->name('update');
    });


    Route::get('rental-reviews', [ProviderController::class, 'reviews'])->name('rental.reviews')->middleware('module:reviews','subscription:reviews');
    Route::post('rental-review/{id}', [ProviderController::class, 'reviewReply'])->name('rental.review.reply')->middleware('module:reviews','subscription:reviews');
});


