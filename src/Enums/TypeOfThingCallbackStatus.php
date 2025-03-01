<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_callback_status
 */
enum TypeOfThingCallbackStatus : string {

    case BUILDING = 'building';
    case WAITING = 'waiting';
    case CALLBACK_SUCCESSFUL = 'callback_successful';
    case CALLBACK_ERROR = 'callback_error';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingCallbackStatus {
        $maybe  = TypeOfThingCallbackStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingCallbackStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


