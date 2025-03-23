<?php

namespace Hexbatch\Things\Interfaces;

use BlueM\Tree;
use Carbon\Carbon;

interface IThingAction
{
    public function isActionComplete() : bool;
    public function isActionError() : bool;
    public function isActionSuccess() : bool;
    public function isActionFail() : bool;

    public function getActionId() : int;
    public function getActionRef() : string;
    public function getActionPriority() : int;
    public static function getActionType() : string;
    public function getChildrenTree(?string $key = null) : Tree;

    public function runAction(array $data = []): void;
    public function getDataByteRowsUsed(): int;
    public function setLimitDataByteRows(int $limit): int;

    public function getActionOwner() : IThingOwner;
    public function getStartAt(): ?Carbon;
    public function getInvalidAt(): ?Carbon;

    public function isAsync() : bool;
    public function isMoreBuilding() : ?string;
    public function getActionResult() : ?array ;
    public function getActionTags() : ?array ;
    public function getInitialConstantData() : ?array ;
    public function setChildActionResult(IThingAction $child) : void ;
    public function addDataBeforeRun(array $data) : void ;

    public function getActionHttpCode() : int;

    public static function resolveAction(int $action_id) : IThingAction;

}
