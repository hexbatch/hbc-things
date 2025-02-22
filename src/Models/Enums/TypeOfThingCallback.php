<?php
namespace Hexbatch\Things\Models\Enums;
/**
 * postgres enum type_of_thing_callback
 */
enum TypeOfThingCallback : string {

    case DISABLED = 'disabled';
    case MANUAL = 'manual';
    case HTTP_GET = 'http_get';
    case HTTP_POST = 'http_post';

    case HTTP_POST_FORM = 'http_post_form';
    case HTTP_PUT = 'http_put';
    case HTTP_PUT_FORM = 'http_put_form';
    case HTTP_PATCH = 'http_patch';
    case HTTP_PATCH_FORM = 'http_patch_form';
    case HTTP_DELETE = 'http_delete';
    case CODE = 'code';
    case EVENT_CALL = 'event_call';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingCallback {
        $maybe  = TypeOfThingCallback::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingCallback::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


