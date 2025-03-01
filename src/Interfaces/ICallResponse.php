<?php

namespace Hexbatch\Things\Interfaces;

interface ICallResponse
{
    public function getCode() : int;
    public function isSuccessful() : bool;
    public function getData() : ?array;
}
