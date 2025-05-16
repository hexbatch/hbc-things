<?php
namespace Hexbatch\Things\Enums;

/**
 * Used to help parent code figure out how to set the group sql
 */
enum TypeOfOwnerGroup : string {
    case THING_LIST = 'thing_list';
    case CALLBACK_LIST = 'callback_list';
    case HOOK_LIST = 'hook_list';
    case HOOK_CALLBACK_CREATION = 'hook_callback_creation';

}


