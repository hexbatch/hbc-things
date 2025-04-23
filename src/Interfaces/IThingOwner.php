<?php

namespace Hexbatch\Things\Interfaces;

interface IThingOwner
{

    public function getOwnerId() : int;
    public function getName() : string;

    /** @return string[] */
    public function getTags() : array;

    public static function getOwnerType() : string;
    public static function resolveOwner(int $owner_id) : IThingOwner;
    public static function registerOwner() : void;

}
