<?php
namespace Hexbatch\Things\Enums;
use OpenApi\Attributes as OA;
/**
 * postgres enum type_of_thing_status
 */
#[OA\Schema]
enum TypeOfThingStatus : string {

  case THING_BUILDING = 'thing_building';
  case THING_WAITING = 'thing_waiting'; //waiting for manual callback
  case THING_PENDING = 'thing_pending'; //it in the jobs but not running yet
  case THING_RUNNING = 'thing_running'; //in the bus

  case THING_SHORT_CIRCUITED = 'thing_short_circuited'; //-- these will not run, and if queued, will be finished without processing
  case THING_SUCCESS = 'thing_success';
  case THING_FAIL = 'thing_fail';
  case THING_INVALID = 'thing_invalid';
  case THING_ERROR = 'thing_error';

    public static function tryFromInput(string|int|bool|null $test ) : TypeOfThingStatus {
        $maybe  = TypeOfThingStatus::tryFrom($test);
        if (!$maybe ) {
            $delimited_values = implode('|',array_column(TypeOfThingStatus::cases(),'value'));
            throw new \InvalidArgumentException(__("msg.invalid_enum",['ref'=>$test,'enum_list'=>$delimited_values]));
        }
        return $maybe;
    }

    const array STATUSES_OF_COMPLETION = [
        self::THING_SUCCESS,
        self::THING_ERROR,
        self::THING_SHORT_CIRCUITED,
        self::THING_INVALID,
        self::THING_FAIL,
    ];

    const array INCOMPLETE_STATUSES = [
        self::THING_BUILDING,self::THING_PENDING,self::THING_WAITING,self::THING_RUNNING
    ];


}


