<?php

namespace Hexbatch\Things\Helpers;

use Hexbatch\Things\Models\Enums\TypeOfThingCallback;

interface IThingCallback
{
    public function getCallbackOwner() : IThingOwner;
    public function getCallbackType():  TypeOfThingCallback;
    public function getConstantData():  array;
    public function getHeader():  string;

    public function  getCallbackUrl() :?string;
    public function  getCallbackClass() :?string;
    public function  getCallbackFunction() :?string;
    public function  getCallbackEvent() :?string;

}
