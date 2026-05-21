<?php

return [
    'name' => 'ReelsModule',
    'project' => '6ammart',
    'version' => '1.0.0',
    'pagination' => 25,

    /*
    |--------------------------------------------------------------------------
    | Multi-module flag
    |--------------------------------------------------------------------------
    | When false, the reels feature treats the project as single-module:
    | module_type / module_id checks are bypassed across admin, vendor,
    | and customer APIs, and stored reels fall back to the defaults below.
    */
    'is_multi_module' => env('REELS_IS_MULTI_MODULE', true),

    /*
    | Module types that are permitted to use the reels feature when the
    | project is running in multi-module mode. Ignored when
    | is_multi_module is false.
    */
    'allowed_module_types' => ['grocery', 'food', 'ecommerce', 'pharmacy', 'rental'],

    /*
    | Default module attributes used when is_multi_module is false, so
    | persisted reels still have consistent values on the database.
    */
    'default_module_id' => (int) env('REELS_DEFAULT_MODULE_ID', 0),
    'default_module_type' => env('REELS_DEFAULT_MODULE_TYPE', 'default'),
];
