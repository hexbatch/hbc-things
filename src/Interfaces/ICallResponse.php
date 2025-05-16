<?php

namespace Hexbatch\Things\Interfaces;

interface ICallResponse
{
    public function getCode() : int;
    public function getData() : ?array;
}
