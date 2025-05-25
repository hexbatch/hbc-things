<?php
namespace Hexbatch\Things\Models\Traits;

use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingAction;

trait ThingActionHandler
{
    /** @var array<string,string|IThingAction> $action_type_lookup  */
    protected static array $action_type_lookup = [];


    public function getAction() : ?IThingAction {
        return static::resolveAction(action_type: $this->action_type,action_id: $this->action_type_id);
    }

    public static function resolveAction(?string $action_type, ?int $action_id = null,?string $uuid = null) : ?IThingAction {
        if (!$action_type) {return null;}
        if (! ($action_id || $uuid)) {return null;}
        $resolver = static::$action_type_lookup[$action_type]??null;
        if (!$resolver) {return null;}
        if ($action_id) {
            return $resolver::resolveAction(action_id: $action_id);
        } elseif ($uuid) {
            return $resolver::resolveActionFromUiid(uuid: $uuid);
        }

    }

    protected static function isRegisteredActionType(string $action_type) : bool {
        return !empty(static::$action_type_lookup[$action_type]);
    }

    protected static function isRegisteredAction(string $action_type, ?int $action_id = null,?string $uuid = null) : bool {
        return !!static::resolveAction(action_type: $action_type,action_id: $action_id,uuid: $uuid);
    }

    public static function registerActionType(IThingAction|string $action_class) :void {
        $interfaces = class_implements($action_class);
        if (!isset($interfaces['Hexbatch\Things\Interfaces\IThingAction'])) {
            throw new HbcThingException("$action_class is not an IThingAction");
        }
        $action_type = $action_class::getActionTypeStatic();
        static::$action_type_lookup[$action_type] = $action_class;
    }
}
