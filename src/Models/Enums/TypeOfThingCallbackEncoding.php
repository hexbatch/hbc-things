<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_of_callback_encoding
 */
enum TypeOfThingCallbackEncoding : string {
    case REGULAR = 'regular';
    case FORM = 'form';


    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingCallbackEncoding {
        $maybe  = TypeOfThingCallbackEncoding::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingCallbackEncoding::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


