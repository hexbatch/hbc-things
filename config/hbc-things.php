<?php


use Hexbatch\Things\Models\ThingSetting;
/*

 */
return [
    'middleware' => [
        'auth_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_AUTH'),
        'admin_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_ADMIN'),
        'owner_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_OWNER'),
        'thing_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_THING_VIEWABLE'),
        'thing_listing_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_THING_LISTING'),
        'thing_editable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_THING_EDITABLE'),
        'setting_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_SETTING_VIEWABLE'),
        'setting_listing_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_SETTING_LISTING'),
        'hook_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_HOOK_VIEWABLE'),
        'hook_listing_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_HOOK_LISTING'),
        'hook_editable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_HOOK_EDITABLE'),
    ],


    'default_thing_settings' => [
        'data_byte_row_limit' => env('HBC_THING_SETTING_DATA_BYTE_ROWS_LIMIT',ThingSetting::DEFAULT_DATA_BYTE_ROWS_LIMIT),
        'tree_limit' => env('HBC_THING_SETTING_DATA_BACKOFF',ThingSetting::DEFAULT_TREE_LIMIT),
        'tree_node_limit' => env('HBC_THING_SETTING_TREE_NODE_LIMIT',ThingSetting::DEFAULT_TREE_NODE_LIMIT),
        'backoff_data_policy' => env('HBC_THING_SETTING_DATA_BACKOFF',ThingSetting::DEFAULT_BACKOFF_DATA_POLICY),

    ],

];

//config('hbc-things.auth_middleware_alias') //example for accessing


