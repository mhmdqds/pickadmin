<?php

use Illuminate\Support\Facades\Route;
use Modules\ReelsModule\Http\Controllers\Api\V1\ReelController;
use Modules\ReelsModule\Http\Controllers\Api\V1\Vendor\ReelController as VendorReelController;

Route::group(['middleware' => ['localization', 'module-check']], function () {
    Route::group(['prefix' => 'customer', 'as' => 'customer.'], function () {
        Route::group(['prefix' => 'reels', 'as' => 'reels.', 'middleware' => 'apiGuestCheck'], function () {
            Route::get('list', [ReelController::class, 'index'])->name('list');
            Route::get('details', [ReelController::class, 'show'])->name('show');
            Route::get('stats', [ReelController::class, 'stats'])->name('stats');
            Route::post('visit', [ReelController::class, 'visit'])->name('visit');
        });

        Route::group(['prefix' => 'reels', 'as' => 'reels.', 'middleware' => 'auth:api'], function () {
            Route::post('like', [ReelController::class, 'like'])->name('like');
        });
    });
});

Route::group(['prefix' => 'vendor', 'namespace' => 'Vendor', 'middleware' => ['vendor.api', 'actch:vendor_app']], function () {
    Route::group(['prefix' => 'reel'], function () {
        Route::get('list', [VendorReelController::class, 'index']);
        Route::post('store', [VendorReelController::class, 'store']);
        Route::get('details', [VendorReelController::class, 'show']);
        Route::put('update', [VendorReelController::class, 'update']);
        Route::delete('delete', [VendorReelController::class, 'destroy']);
        Route::put('status', [VendorReelController::class, 'status']);
    });
});
