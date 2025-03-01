<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfThingHookBlocking;
use Hexbatch\Things\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Enums\TypeOfThingHookPosition;
use Hexbatch\Things\Enums\TypeOfThingHookScope;

interface IThingHook
{
    public function getHookOwner() : ?IThingOwner;
    public function getHookAction() : ?IThingAction;
    public function getConstantData():  array;
    public function getHookTags():  array;
    public function getHookCallbackTimeToLive():  ?int;
    public function isHookOn():  bool;
    public function getHookMode():  TypeOfThingHookMode;
    public function getHookBlocking():  TypeOfThingHookBlocking;
    public function getHookScope():  TypeOfThingHookScope;
    public function getHookPosition():  TypeOfThingHookPosition;
    public function  getHookName() :string;
    public function  getHookNotes() :?string;

}
