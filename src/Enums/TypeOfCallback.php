<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_callback
 */
enum TypeOfCallback : string {

    case DISABLED = 'disabled';
    case HTTP_GET = 'http_get';
    case HTTP_POST = 'http_post';

    case HTTP_POST_FORM = 'http_post_form';
    case HTTP_POST_JSON = 'http_post_json';
    case HTTP_PUT = 'http_put';
    case HTTP_PUT_FORM = 'http_put_form';
    case HTTP_PUT_JSON = 'http_put_json';
    case HTTP_PATCH = 'http_patch';
    case HTTP_PATCH_FORM = 'http_patch_form';
    case HTTP_PATCH_JSON = 'http_patch_json';
    case HTTP_DELETE = 'http_delete';
    case HTTP_DELETE_FORM = 'http_delete_form';
    case HTTP_DELETE_JSON = 'http_delete_json';
    case CODE = 'code';
    case EVENT_CALL = 'event_call';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfCallback {
        $maybe  = TypeOfCallback::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfCallback::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }
}


