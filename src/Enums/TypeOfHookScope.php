<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_hook_scope
 */
enum TypeOfHookScope : string {
    case CURRENT = 'current';
    case ANCESTOR_CHAIN = 'ancestor_chain';
    case ALL_TREE = 'all_tree';
    case GLOBAL = 'global';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfHookScope {
        $maybe  = TypeOfHookScope::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfHookScope::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


