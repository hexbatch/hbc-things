<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_callback_sharing
 */
enum TypeOfCallbackSharing : string {

    case NO_SHARING = 'no_sharing';
    case PER_PARENT = 'per_parent';
    case PER_TREE = 'per_tree';
    case GLOBAL = 'global';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfCallbackSharing {
        $maybe  = TypeOfCallbackSharing::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfCallbackSharing::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


