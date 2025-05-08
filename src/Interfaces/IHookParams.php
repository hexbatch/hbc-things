<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;

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
    public function isSharing():  bool;
    public function getHookMode():  ?TypeOfHookMode;
    public function  getHookName() :?string;
    public function  getHookNotes() :?string;

    public function getCallbackType():  ?TypeOfCallback;
    public function getDataTemplate():  array;
    public function getHeaderTemplate():  array;

    public function  getAddress() :string;
    public function  getSharedTtl() :?int;

}
