<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_of_callback_method
 */
enum TypeOfThingCallbackMethod : string {
    case GET = 'get';
    case POST = 'post';
    case PUT = 'put';
    case PATCH = 'patch';
    case DELETE = 'delete';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingCallbackMethod {
        $maybe  = TypeOfThingCallbackMethod::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingCallbackMethod::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


