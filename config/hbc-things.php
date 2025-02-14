<?php


use Hexbatch\Things\Models\ThingSetting;

return [
    'auth_middleware_alias' => env('HBC_THING_MIDDLEWARE_ALIAS'), //
    'pagination_size' => env('HBC_THING_RULE_PAGINATION_SIZE',ThingSetting::DEFAULT_PAGINATION_SIZE),
    'pagination_limit' => env('HBC_THING_RULE_PAGINATION_LIMIT',ThingSetting::DEFAULT_PAGINATION_LIMIT),
    'depth_limit' => env('HBC_THING_RULE_DEPTH_LIMIT',ThingSetting::DEFAULT_DEPTH_LIMIT),
    'backoff_page_policy' => env('HBC_THING_RULE_BACKOFF_PAGE_POLICY',ThingSetting::DEFAULT_BACKOFF_PAGE_POLICY),
    'backoff_rate_policy' => env('HBC_THING_RULE_BACKOFF_RATE_POLICY',ThingSetting::DEFAULT_BACKOFF_RATE_POLICY),
    'rate_limit' => env('HBC_THING_RULE_RATE_LIMIT',ThingSetting::DEFAULT_RATE_LIMIT),
    'json_size_limit' => env('HBC_THING_RULE_JSON_SIZE_LIMIT',ThingSetting::DEFAULT_JSON_SIZE_LIMIT),
];

//config('hbc-things.auth_middleware_alias') //example for accessing


