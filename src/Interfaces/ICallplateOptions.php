<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfCallback;

interface ICallplateOptions
{
    public function getCallbackType():  TypeOfCallback;
    public function getDataTemplate():  array;
    public function getHeaderTemplate():  array;
    public function getTags():  array;

    public function  getUrl() :?string;
    public function  getEventFilter() :?string;
    public function  getClass() :?string;

}
