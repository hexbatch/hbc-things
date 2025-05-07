<?php

namespace Hexbatch\Things\Helpers;


use Hexbatch\Things\Interfaces\IThingOwner;

class OwnerHelper {
    /**
     * @param IThingOwner $owner
     * @param IThingOwner[] $list_of_owners
     * @return array
     */
    public static function addToOwnerArray(IThingOwner $owner,array $list_of_owners) : array {
        foreach ($list_of_owners as $what) {
            if ($what->getOwnerType() === $owner->getOwnerType() && $what->getOwnerId() === $what->getOwnerId()) {return $list_of_owners;}
        }
        $list_of_owners[]= $owner;
        return $list_of_owners;
    }

}
