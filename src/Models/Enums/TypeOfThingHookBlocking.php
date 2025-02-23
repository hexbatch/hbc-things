<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_hooked_thing_status
 */
enum TypeOfThingHookBlocking : string {
    case NONE = 'none';
    case BLOCK = 'block';
    case BLOCK_ADD_DATA_TO_PARENT = 'block_add_data_to_parent';
    case BLOCK_ADD_DATA_TO_CURRENT = 'block_add_data_to_current';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingHookBlocking {
        $maybe  = TypeOfThingHookBlocking::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingHookBlocking::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


