<?php
namespace Hexbatch\Things\Models\Traits;

use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingAction;

trait ThingActionHandler
{
    /** @var array<string,string|IThingAction> $action_type_lookup  */
    protected static array $action_type_lookup = [];


    public function getAction() : IThingAction {
        return static::resolveAction(action_type: $this->action_type,action_id: $this->action_type_id);
    }

    protected static function resolveAction(string $action_type, int $action_id) : ?IThingAction {
        $resolver = static::$action_type_lookup[$action_type]??null;
        if (!$resolver) {return null;}
        return $resolver::resolveAction(action_id: $action_id);
    }

    public static function registerActionType(IThingAction|string $action_class) :void {
        $interfaces = class_implements($action_class);
        if (!isset($interfaces['Hexbatch\Things\Interfaces\IThingAction'])) {
            throw new HbcThingException("$action_class is not an IThingAction");
        }
        $action_type = $action_class::getActionType();
        static::$action_type_lookup[$action_type] = $action_class;
    }
}
