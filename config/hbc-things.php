<?php


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


];

//config('hbc-things.auth_middleware_alias') //example for accessing


