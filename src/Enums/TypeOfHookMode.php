<?php
namespace Hexbatch\Things\Enums;
use OpenApi\Attributes as OA;
/**
 * postgres enum type_of_thing_hook_mode
 */
#[OA\Schema]
enum TypeOfHookMode : string {
    case NONE = 'none';
    case NODE = 'node';

    case NODE_FAILURE = 'node_failure';
    case NODE_SUCCESS = 'node_success';
    case NODE_FINALLY = 'node_finally';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfHookMode {
        $maybe  = TypeOfHookMode::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfHookMode::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


