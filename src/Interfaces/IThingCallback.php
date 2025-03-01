<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfThingCallback;

interface IThingCallback
{
    public function getCallbackOwner() : IThingOwner;
    public function getCallbackType():  TypeOfThingCallback;
    public function getConstantData():  array;
    public function getHeader():  array;

    public function  getCallbackUrl() :?string;
    public function  getCallbackClass() :?string;
    public function  getCallbackFunction() :?string;
    public function  getCallbackEvent() :?string;

}
