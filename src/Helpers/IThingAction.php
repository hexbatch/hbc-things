<?php

namespace Hexbatch\Things\Helpers;

use BlueM\Tree;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;

interface IThingAction
{
    public function getActionStatus() : TypeOfThingStatus;

    public function getActionId() : int;
    public function getActionPriority() : int;
    public static function getActionType() : string;
    public function getChildrenTree() : Tree;

    public function runAction(): void;
    public function getActionResult() : ?array ;

    public static function resolveAction(int $action_id) : IThingAction;

}
