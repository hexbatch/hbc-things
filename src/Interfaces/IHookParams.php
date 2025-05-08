<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfHookScope;

interface IHookParams
{
    public function getHookOwner() : ?IThingOwner;
    public function setHookOwner(?IThingOwner $owner) :IHookParams  ;
    public function getHookAction() : ?IThingAction;
    public function getConstantData():  array;
    public function getHookTags():  array;
    public function isHookOn():  bool;
    public function isBlocking():  bool;
    public function isWriting():  bool;
    public function getHookMode():  ?TypeOfHookMode;
    public function getHookScope():  TypeOfHookScope;
    public function  getHookName() :?string;
    public function  getHookNotes() :?string;

}
