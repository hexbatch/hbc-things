<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_of_thing_callback
 */
enum TypeOfThingCallback : string {
    case MANUAL = 'manual';
    case PUSH = 'push';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingCallback {
        $maybe  = TypeOfThingCallback::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingCallback::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


