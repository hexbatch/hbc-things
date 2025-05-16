<?php


return [
    'middleware' => [
        'auth_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_AUTH'),
        'admin_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_ADMIN'),
        'owner_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_OWNER'),

        'thing_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_THING_VIEWABLE'),
        'thing_editable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_THING_EDITABLE'),

        'hook_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_HOOK_VIEWABLE'),
        'hook_editable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_HOOK_EDITABLE'),

        'callback_viewable_alias' => env('HBC_THING_MIDDLEWARE_ALIAS_CALLBACK_VIEWABLE'),

    ],

    'queues'=> [
        'default_connection' => env('HBC_THING_DEFAULT_QUEUE_CONNECTION',''),
    ]

];

//config('hbc-things.auth_middleware_alias') //example for accessing


