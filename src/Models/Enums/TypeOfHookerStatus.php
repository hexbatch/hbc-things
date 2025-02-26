<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_of_hooker_status
 */
enum TypeOfHookerStatus : string {
    case NONE = 'none';
    case WAITING_FOR_THING = 'waiting_for_thing';
    case WAITING_FOR_HOOK = 'waiting_for_callbacks';
    case HOOK_COMPLETE = 'hook_complete';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfHookerStatus {
        $maybe  = TypeOfHookerStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfHookerStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


