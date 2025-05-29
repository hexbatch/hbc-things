<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;

interface IHookCode
{
    public static function runHook(ThingCallback $callback,Thing $thing,ThingHook $hook,array $header,array $body)
    : ICallResponse;
}
