<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_hooked_thing_status
 */
enum TypeOfThingHookScope : string {
    case CURRENT = 'current';
    case ALL_DESCENDANTS = 'all_descendants';
    case ALL_TREE = 'all_tree';
    case GLOBAL = 'global';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingHookScope {
        $maybe  = TypeOfThingHookScope::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingHookScope::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


