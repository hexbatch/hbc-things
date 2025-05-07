<?php

namespace Hexbatch\Things\Interfaces;

use ArrayIterator;


abstract class ThingOwnerGroup extends ArrayIterator {

    /** @return IThingOwner[] */
    public abstract function getOwners() : array ;
}
