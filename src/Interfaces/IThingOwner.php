<?php

namespace Hexbatch\Things\Interfaces;

interface IThingOwner
{

    public function getOwnerId() : int;
    public static function getOwnerType() : string;
    public static function resolveOwner(int $action_id) : IThingOwner;

}
