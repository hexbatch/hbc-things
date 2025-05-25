<?php

namespace Hexbatch\Things\Interfaces;


use Hexbatch\Things\Enums\TypeOfOwnerGroup;

interface IThingOwner
{

    public function getOwnerId() : int;
    public function getOwnerUuid() : string;
    public function getName() : string;

    /**
     * adds a join with conditions in the join,
     * if @uses \Hexbatch\Things\Enums\TypeOfOwnerGroup::HOOK_CALLBACK_CREATION then left join
     * else inner join
     * @param \Illuminate\Contracts\Database\Query\Builder $builder
     */
    public function setReadGroupBuilding($builder, string $connecting_table_name,
                                         string $connecting_owner_type_column, string $connecting_owner_id_column,
                                         TypeOfOwnerGroup $hint,?string $alias = null
    ) :void;

    /** @return string[] */
    public function getTags() : array;

    public function getOwnerType() : string;
    public static function getOwnerTypeStatic() : string;
    public static function resolveOwner(int $owner_id) : IThingOwner;

    public static function resolveOwnerFromUiid(string $uuid) : IThingOwner;
    public static function registerOwner() : void;

}
