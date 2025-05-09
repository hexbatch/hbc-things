<?php
namespace Hexbatch\Things\Models\Traits;

use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingOwner;

trait ThingOwnerHandler
{
    /** @var array<string,string|IThingOwner> $owner_type_lookup  */
    protected static array $owner_type_lookup = [];


    public function getOwner() : ?IThingOwner {
        return static::resolveOwner(owner_type: $this->owner_type, owner_id: $this->owner_type_id);
    }

    protected static function resolveOwner(?string $owner_type, ?int $owner_id) : ?IThingOwner {
        if (!$owner_type) {return null;}
        if (is_null($owner_id)) {return null;}
        $resolver = static::$owner_type_lookup[$owner_type]??null;
        if (!$resolver) {return null;}
        return $resolver::resolveOwner(owner_id: $owner_id);
    }


    protected static function isRegisteredOwnerType(string $owner_type) : bool {
        return !empty(static::$owner_type_lookup[$owner_type]);
    }

    protected static function isRegisteredOwner(string $owner_type, int $owner_id) : bool {
        return !!static::resolveOwner(owner_type: $owner_type,owner_id: $owner_id);
    }

    public static function registerOwnerType(IThingOwner|string $owner_class) :void {
        $interfaces = class_implements($owner_class);
        if (!isset($interfaces['Hexbatch\Things\Interfaces\IThingOwner'])) {
            throw new HbcThingException("$owner_class is not an IThingOwner");
        }
        $owner_type = $owner_class::getOwnerTypeStatic();
        static::$owner_type_lookup[$owner_type] = $owner_class;
    }
}
