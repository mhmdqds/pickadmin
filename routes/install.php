<?php

use Illuminate\Support\Facades\Route;


Route::get('/', 'InstallController@step0')->name('step0');
Route::get('/step1', 'InstallController@step1')->name('step1');
Route::get('/step2', 'InstallController@step2')->name('step2');
Route::get('/step3/{error?}', 'InstallController@step3')->name('step3')->middleware('installation-check');
Route::get('/step4', 'InstallController@step4')->name('step4')->middleware('installation-check');
Route::get('/step5', 'InstallController@step5')->name('step5')->middleware('installation-check');

Route::post('/database_installation', 'InstallController@database_installation')->name('install.db')->middleware('installation-check');
Route::get('import_sql', 'InstallController@import_sql')->name('import_sql')->middleware('installation-check');
Route::get('force-import-sql', 'InstallController@force_import_sql')->name('force-import-sql')->middleware('installation-check');
// H-? fix (F-03): system_settings and purchase_code previously had NO middleware,
// meaning they were reachable after installation. Both endpoints now require the
// installation-check middleware, which (since H-? fix) refuses ALL installer
// routes once APP_INSTALL=true is set in .env.
Route::post('system_settings', 'InstallController@system_settings')->name('system_settings')->middleware('installation-check');
Route::post('purchase_code', 'InstallController@purchase_code')->name('purchase.code')->middleware('installation-check');

Route::fallback(function () {
    return redirect('/');
});
