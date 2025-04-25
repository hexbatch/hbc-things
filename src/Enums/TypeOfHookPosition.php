<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_hook_position
 */
enum TypeOfHookPosition : string {
    case ANY_POSITION = 'any_position';
    case ROOT = 'root';
    case ANY_CHILD = 'any_child';
    case SUB_ROOT = 'sub_root';
    case LEAF = 'leaf';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfHookPosition {
        $maybe  = TypeOfHookPosition::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfHookPosition::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


