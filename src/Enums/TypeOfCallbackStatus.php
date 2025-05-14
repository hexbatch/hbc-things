<?php
namespace Hexbatch\Things\Enums;
use OpenApi\Attributes as OA;
/**
 * postgres enum type_of_thing_callback_status
 */
#[OA\Schema()]
enum TypeOfCallbackStatus : string {

    case BUILDING = 'building';
    case WAITING = 'waiting';
    case CALLBACK_SUCCESSFUL = 'callback_successful';
    case CALLBACK_ERROR = 'callback_error';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfCallbackStatus {
        $maybe  = TypeOfCallbackStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfCallbackStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


