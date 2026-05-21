<?php

use Illuminate\Support\Facades\Route;
use Modules\ReelsModule\Http\Controllers\Admin\ReelController;
use Modules\ReelsModule\Http\Controllers\Vendor\ReelController as VendorReelController;

Route::group([
    'prefix' => 'admin/reels',
    'as' => 'admin.reels.',
    'middleware' => ['admin', 'module:reels', 'current-module', 'actch:admin_panel'],
], function () {
    Route::get('/', [ReelController::class, 'index'])->name('index');
    Route::get('/create', [ReelController::class, 'create'])->name('create');
    Route::post('/store', [ReelController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ReelController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [ReelController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [ReelController::class, 'destroy'])->name('destroy');
    Route::get('/status/{id}/{status}', [ReelController::class, 'status'])->name('status');
});

Route::group([
    'prefix' => 'vendor-panel/reels',
    'as' => 'vendor.reels.',
    'middleware' => ['vendor', 'module:reels', 'actch:admin_panel'],
], function () {
    Route::get('/', [VendorReelController::class, 'index'])->name('index');
    Route::get('/create', [VendorReelController::class, 'create'])->name('create');
    Route::post('/store', [VendorReelController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [VendorReelController::class, 'edit'])->name('edit');
    Route::put('/update/{id}', [VendorReelController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [VendorReelController::class, 'destroy'])->name('destroy');
    Route::get('/status/{id}/{status}', [VendorReelController::class, 'status'])->name('status');
});
