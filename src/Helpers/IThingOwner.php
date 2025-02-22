<?php

namespace Hexbatch\Things\Helpers;

use BlueM\Tree;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;

interface IThingOwner
{

    public function getOwnerId() : int;
    public static function getOwnerType() : string;
    public static function resolveOwner(int $action_id) : IThingOwner;

}
