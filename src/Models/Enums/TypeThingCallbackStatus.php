<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_thing_callback_status
 */
enum TypeThingCallbackStatus : string {
    case NO_FOLLOWUP = 'no_followup';
    case DIRECT_FOLLOWUP = 'direct_followup';
    case POLLED_FOLLOWUP = 'polled_followup';
    case FOLLOWUP_CALLBACK_SUCCESSFUL = 'followup_callback_successful';
    case FOLLOWUP_CALLBACK_ERROR = 'followup_callback_error';


    public static function tryFromInput(string|int|bool|null $test ) : TypeThingCallbackStatus {
        $maybe  = TypeThingCallbackStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeThingCallbackStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


