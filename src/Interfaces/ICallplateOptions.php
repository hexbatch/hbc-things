<?php

namespace Hexbatch\Things\Interfaces;

use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackSharing;

interface ICallplateOptions
{
    public function getCallbackType():  ?TypeOfCallback;
    public function getCallbackSharing():  ?TypeOfCallbackSharing;
    public function getDataTemplate():  array;
    public function getHeaderTemplate():  array;
    public function getTags():  array;

    public function  getAddress() :string;
    public function  getSharedTtl() :?int;


}
