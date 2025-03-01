<?php
namespace Hexbatch\Things\Enums;
/**
 * postgres enum type_of_thing_status
 */
enum TypeOfThingStatus : string {

  case THING_BUILDING = 'thing_building';
  case THING_PENDING = 'thing_pending'; //waiting to run
  case THING_HOOKED_BEFORE_RUN = 'thing_hooked_before_run'; //when waiting for remote hook to complete, which will resume it
  case THING_HOOKED_AFTER_RUN = 'thing_hooked_after_run'; //when waiting for remote hook to complete, which will resume it
  case THING_SHORT_CIRCUITED = 'thing_short_circuited'; //-- these will not run, and if queued, will be finished without processing
  case THING_RESOURCES = 'thing_resources'; //not enough resources to finish building the tree
  case THING_SUCCESS = 'thing_success';
  case THING_ERROR = 'thing_error';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingStatus {
        $maybe  = TypeOfThingStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }

}


