<?php


use Hexbatch\Things\Models\ThingSetting;

return [
    'auth_middleware_alias' => env('HBC_THING_MIDDLEWARE_ALIAS'), //

    'default_thing_settings' => [
        'pagination_size' => env('HBC_THING_SETTING_DATA_BYTE_ROWS_LIMIT',ThingSetting::DEFAULT_DATA_BYTE_ROWS_LIMIT),
        'ancestor_limit' => env('HBC_THING_SETTING_ANCESTOR_LIMIT',ThingSetting::DEFAULT_ANCESTOR_LIMIT),
        'backoff_data_policy' => env('HBC_THING_SETTING_DATA_BACKOFF',ThingSetting::DEFAULT_BACKOFF_DATA_POLICY),
        'tree_limit' => env('HBC_THING_SETTING_DATA_BACKOFF',ThingSetting::DEFAULT_TREE_LIMIT),
    ],

];

//config('hbc-things.auth_middleware_alias') //example for accessing


