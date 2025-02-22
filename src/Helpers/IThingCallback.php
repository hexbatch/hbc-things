<?php

namespace Hexbatch\Things\Helpers;

use BlueM\Tree;
use Hexbatch\Things\Models\Enums\TypeOfThingCallback;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackEncoding;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackMethod;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;

interface IThingCallback
{
    public function getCallbackOwner() : IThingOwner;
    public function getCallbackType():  TypeOfThingCallback;

    public function  getCallbackMethod() :TypeOfThingCallbackMethod;
    public function getCallbackEncoding() : TypeOfThingCallbackEncoding ;
    public function  getCallbackUrl() :?string;

}
