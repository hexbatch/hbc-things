<?php

namespace Hexbatch\Things\Interfaces;

interface ICallResponse
{
    public function getCode() : int;
    public function getData() : ?array;
    public function getWaitTimeoutInSeconds() : ?int;
}
