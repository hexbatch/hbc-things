<?php

namespace Hexbatch\Things\Interfaces;

interface IHookCode extends ICallResponse
{
    public static function runHook(array $header,array $body) : ICallResponse;
}
