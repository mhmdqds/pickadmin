<?php

use Illuminate\Support\Facades\Route;
use Modules\Rental\Http\Controllers\Web\Admin\BrandController;
use Modules\Rental\Http\Controllers\Web\Admin\Promotions\BannerController;
use Modules\Rental\Http\Controllers\Web\Admin\Promotions\CouponController;
use Modules\Rental\Http\Controllers\Web\Admin\Promotions\CashBackController;
use Modules\Rental\Http\Controllers\Web\Admin\Promotions\NotificationController;
use Modules\Rental\Http\Controllers\Web\Admin\CategoryController;
use Modules\Rental\Http\Controllers\Web\Admin\DriverController;
use Modules\Rental\Http\Controllers\Web\Admin\ProviderController;
use Modules\Rental\Http\Controllers\Web\Admin\DashboardController;
use Modules\Rental\Http\Controllers\Web\Admin\ReportController;
use Modules\Rental\Http\Controllers\Web\Admin\TripController;
use Modules\Rental\Http\Controllers\Web\Admin\VehicleController;
use Modules\Rental\Http\Controllers\Web\Admin\SettingsController;

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

Route::group(['middleware' => ['admin', 'current-module']], function () {
    Route::group(['prefix' => 'rental', 'as' => 'rental.','middleware' => 'admin-rental-module'], function () {
        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::group(['prefix' => 'dashboard-stats', 'as' => 'dashboard-stats.'], function () {
            Route::get('/commission-overview', [DashboardController::class, 'commissionOverview'])->name('commission_overview');
            Route::get('/trip-by-trip-type', [DashboardController::class, 'byTripType'])->name('trip_by_trip_type');
        });

        Route::group(['prefix' => 'category', 'as' => 'category.', 'middleware' => ['module:vehicle']], function () {
            Route::get('list', [CategoryController::class, 'list'])->name('list');
            Route::post('list', [CategoryController::class, 'store']);
            Route::get('edit/{id}', [CategoryController::class, 'edit'])->name('edit');
            Route::post('edit/{id}', [CategoryController::class, 'update']);
            Route::delete('delete/{id}', [CategoryController::class, 'destroy'])->name('delete');
            Route::get('status/{id}', [CategoryController::class, 'status'])->name('status');
            Route::get('export-categories', [CategoryController::class, 'export'])->name('export-categories');
            Route::get('get-categories', [CategoryController::class, 'getCategories'])->name('get-categories');
        });

        Route::group(['prefix' => 'brand', 'as' => 'brand.', 'middleware' => ['module:vehicle']], function () {
            Route::get('list', [BrandController::class, 'list'])->name('list');
            Route::post('list', [BrandController::class, 'store']);
            Route::get('edit/{id}', [BrandController::class, 'edit'])->name('edit');
            Route::post('edit/{id}', [BrandController::class, 'update']);
            Route::delete('delete/{id}', [BrandController::class, 'destroy'])->name('delete');
            Route::get('status/{id}', [BrandController::class, 'status'])->name('status');
            Route::get('export', [BrandController::class, 'export'])->name('export-brands');

        });

        Route::group(['prefix' => 'provider', 'as' => 'provider.'], function () {
            Route::group(['middleware' => ['module:provider']], function () {
                Route::get('list', [ProviderController::class, 'list'])->name('list');
                Route::get('create', [ProviderController::class, 'create'])->name('create');
                Route::post('create', [ProviderController::class, 'store']);
                Route::get('edit-basic-setup/{id}', [ProviderController::class, 'editBasicSetup'])->name('edit-basic-setup');
                Route::post('edit-basic-setup/{id}', [ProviderController::class, 'updateBasicSetup']);
                Route::get('edit-business-setup/{id}', [ProviderController::class, 'editBusinessSetup'])->name('edit-business-setup');
                Route::post('edit-business-setup/{id}', [ProviderController::class, 'updateBusinessSetup']);
                Route::delete('delete/{id}', [ProviderController::class, 'destroy'])->name('delete');
                Route::get('status/{id}', [ProviderController::class, 'status'])->name('status');
                Route::get('details/{id}/{tab?}/{sub_tab?}', [ProviderController::class, 'details'])->name('details');
                Route::post('update-settings/{id}', [ProviderController::class, 'updateSettings'])->name('update_settings');
                Route::get('export-review', [ProviderController::class, 'exportReview'])->name('export-review');
                Route::get('export-categories', [ProviderController::class, 'export'])->name('export-brands');
                Route::get('new-requests', [ProviderController::class, 'newRequests'])->name('new-requests');
                Route::get('new-requests-details/{id}', [ProviderController::class, 'newRequestsDetails'])->name('new-requests-details');
                Route::get('approve-or-deny/{id}', [ProviderController::class, 'approveOrDeny'])->name('approve-or-deny');
                Route::get('status/{store_id}', [ProviderController::class, 'status'])->name('status');

                Route::get('bulk-import', [ProviderController::class, 'bulkImportIndex'])->name('bulk_import');
                Route::post('bulk-import', [ProviderController::class, 'bulkImportData']);
                Route::get('bulk-export', [ProviderController::class, 'bulkExportIndex'])->name('bulk_export_index');
                Route::post('bulk-export', [ProviderController::class, 'bulkExportData']);
            });

            Route::group(['prefix' => 'driver', 'as' => 'driver.', 'middleware' => ['module:driver']], function () {
                Route::get('create/{provider_id}', [DriverController::class, 'create'])->name('create');
                Route::post('create/{provider_id}', [DriverController::class, 'store']);
                Route::get('update/{id}', [DriverController::class, 'edit'])->name('edit');
                Route::post('update/{id}', [DriverController::class, 'update']);
                Route::get('details/{id}', [DriverController::class, 'details'])->name('details');
                Route::get('status/{id}', [DriverController::class, 'status'])->name('status');
                Route::delete('delete/{id}', [DriverController::class, 'destroy'])->name('delete');
                Route::get('export', [DriverController::class, 'export'])->name('export');
                Route::get('trip-export', [DriverController::class, 'tripExport'])->name('trip.export');
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
                Route::get('review-list', [VehicleController::class, 'reviews'])->name('reviews');

                Route::get('bulk-import', [VehicleController::class, 'bulkImportIndex'])->name('bulk_import');
                Route::POST('bulk-import', [VehicleController::class, 'bulkImportData']);
                Route::get('bulk-export', [VehicleController::class, 'bulkExportIndex'])->name('bulk-export-index');
                Route::POST('bulk-export', [VehicleController::class, 'bulkExportData']);
            });
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

        Route::group(['prefix' => 'banner', 'as' => 'banner.', 'middleware' => ['module:promotion']], function () {
            Route::get('/', [BannerController::class,'list'])->name('add-new');
            Route::post('store', [BannerController::class,'store'])->name('store');
            Route::get('edit/{banner}', [BannerController::class,'edit'])->name('edit');
            Route::post('edit/{banner}', [BannerController::class,'update'])->name('update');
            Route::delete('delete/{banner}', [BannerController::class,'destroy'])->name('delete');
            Route::get('status/{banner}/{status}', [BannerController::class,'status'])->name('status');
            Route::get('featured/{banner}/{status}', [BannerController::class,'updateFeatured'])->name('featured');
            Route::get('export', [BannerController::class, 'export'])->name('export');
        });

        Route::group(['prefix' => 'coupon', 'as' => 'coupon.', 'middleware' => ['module:promotion']], function () {
            Route::get('/', [CouponController::class,'list'])->name('add-new');
            Route::post('store', [CouponController::class,'store'])->name('store');
            Route::get('edit/{coupon}', [CouponController::class,'edit'])->name('edit');
            Route::post('edit/{coupon}', [CouponController::class,'update'])->name('update');
            Route::delete('delete/{coupon}', [CouponController::class,'destroy'])->name('delete');
            Route::get('status/{coupon}', [CouponController::class,'status'])->name('status');
            Route::get('export', [CouponController::class, 'export'])->name('export');
        });

        Route::group(['prefix' => 'cashback', 'as' => 'cashback.', 'middleware' => ['module:promotion']], function () {
            Route::get('/', [CashBackController::class,'list'])->name('list');
            Route::post('/', [CashBackController::class,'store']);
            Route::get('edit/{id}', [CashBackController::class,'edit'])->name('edit');
            Route::post('edit/{id}', [CashBackController::class,'update']);
            Route::delete('delete/{id}', [CashBackController::class,'destroy'])->name('delete');
            Route::get('status/{id}', [CashBackController::class,'status'])->name('status');
        });

        Route::group(['prefix' => 'notification', 'as' => 'notification.', 'middleware' => ['module:promotion']], function () {
            Route::get('/', [NotificationController::class,'list'])->name('list');
            Route::post('store', [NotificationController::class,'store'])->name('store');
            Route::get('edit/{notification}', [NotificationController::class,'edit'])->name('edit');
            Route::post('update/{notification}', [NotificationController::class,'update'])->name('update');
            Route::get('status/{notification}', [NotificationController::class,'status'])->name('status');
            Route::delete('delete/{notification}', [NotificationController::class,'destroy'])->name('delete');
            Route::get('export', [NotificationController::class,'export'])->name('export');
        });
        Route::group(['prefix' => 'settings', 'as' => 'settings.', 'middleware' => ['module:download_app']], function () {
            Route::get('/', [SettingsController::class,'homePageDownApp'])->name('down_app');
            Route::post('/down_app_update', [SettingsController::class,'homePageDownAppUpdate'])->name('down_app_update');
            Route::get('vendors-registration/', [SettingsController::class,'vendorsRegistration'])->name('vendors_registration');
            Route::post('/vendors-registration-update', [SettingsController::class,'vendorsRegistrationUpdate'])->name('vendors_registration_update');
        });


    });
    Route::group(['prefix' => 'business-settings', 'as' => 'business-settings.', 'middleware' => ['module:settings', 'actch']], function () {
        Route::get('rental-email-setup/{type}/{tab?}', [SettingsController::class,'email_index'])->name('rental-email-setup');
        Route::POST('rental-email-setup/{type}/{tab?}', [SettingsController::class,'update_email_index'])->name('rental-email-setup');
        Route::get('rental-email-status/{type}/{tab}/{status}', [SettingsController::class,'update_email_status'])->name('rental-email-status');
    });
    Route::group(['prefix' => 'transactions', 'as' => 'transactions.', 'middleware' => ['module:rental_report']], function () {
        Route::group(['prefix' => 'rental', 'as' => 'rental.'], function () {
            Route::get('trip/details/{id}', [TripController::class,'details'])->name('trip.details');
            Route::get('trip/generate-invoice/{id}', [TripController::class, 'generateInvoice'])->name('trip.generate-invoice');
            Route::group(['prefix' => 'report', 'as' => 'report.', 'middleware' => ['module:report']], function () {
                Route::get('transaction-report', [ReportController::class, 'transactionReport'])->name('transaction-report');
                Route::get('transaction-report-export', [ReportController::class, 'transactionExport'])->name('transaction-report-export');
                Route::get('generate-statement/{id}', [ReportController::class, 'generateStatement'])->name('generate-statement');
                Route::get('trip-report', [ReportController::class, 'tripReport'])->name('trip-report');
                Route::get('trip-report-export', [ReportController::class, 'tripReportExport'])->name('trip-report-export');
                Route::get('vehicle-report', [ReportController::class, 'vehicleReport'])->name('vehicle-wise-report');
                Route::get('vehicle-report-export', [ReportController::class, 'vehicleReportExport'])->name('vehicle-wise-export');
                Route::get('provider-wise-report', [ReportController::class, 'providerSummaryReport'])->name('provider-summary-report');
                Route::get('provider-summary-report-export', [ReportController::class, 'providerSummaryExport'])->name('provider-summary-report-export');
                Route::get('provider-wise-sales-report', [ReportController::class, 'providerSalesReport'])->name('provider-sales-report');
                Route::get('provider-wise-sales-report-export', [ReportController::class, 'providerSalesExport'])->name('provider-sales-report-export');
                Route::get('provider-wise-trip-report', [ReportController::class, 'providerTripReport'])->name('provider-trip-report');
                Route::get('provider-wise-trip-report-export', [ReportController::class, 'providerTripExport'])->name('provider-trip-report-export');
                Route::post('set-date', [ReportController::class, 'set_date'])->name('set-date');
            });
        });
    });
});
Route::get('trip-invoice/{id}', [ReportController::class, 'tripInvoice'])->name('trip_invoice');

