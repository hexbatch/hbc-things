<?php

namespace Hexbatch\Things\Interfaces;

interface IHookCode
{
    public static function runHook(array $header,array $body,int &$return_int) : array;
}
