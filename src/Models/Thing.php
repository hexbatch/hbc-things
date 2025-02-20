<?php

namespace Hexbatch\Things\Models;




use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Helpers\IThingAction;
use Hexbatch\Things\Jobs\RunThing;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int parent_thing_id
 * @property int root_thing_id
 * @property int thing_error_id
 * @property int action_priority
 * @property string action_type
 * @property int action_type_id
 *
 *
 *
 * @property int debugging_breakpoint
 *
 * @property string thing_start_at
 * @property string thing_invalid_at
 * @property string thing_started_at
 * @property string ref_uuid
 * @property TypeOfThingStatus thing_status
 *
 * @property string created_at
 * @property int created_at_ts
 * @property string updated_at
 *
 * @property ThingResult thing_result
 * @property ThingError thing_error
 * @property Thing thing_parent
 * @property Thing thing_root
 * @property Thing[]|\Illuminate\Database\Eloquent\Collection thing_children
 */
class Thing extends Model
{

    protected $table = 'things';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'parent_thing_id',
        'thing_error_id',
        'parent_thing_id',
        'action_priority',
        'action_type',
        'action_type_id',
        'debugging_breakpoint',
        'thing_start_at',
        'thing_started_at',
        'thing_invalid_at',
        'thing_status',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    /* @var array<string, string> */
    protected $casts = [
        'thing_status' => TypeOfThingStatus::class
    ];



    public function thing_children() : HasMany {
        return $this->hasMany(Thing::class,'parent_thing_id','id');
    }

    public function thing_parent() : BelongsTo {
        return $this->belongsTo(Thing::class,'parent_thing_id','id');
    }

    public function thing_root() : BelongsTo {
        return $this->belongsTo(Thing::class,'root_thing_id','id');
    }

    public function thing_result() : HasOne {
        return $this->hasOne(ThingResult::class,'owner_thing_id','id')
            /** @uses ThingResult::result_callbacks() */
            ->with('result_callbacks');
    }

    public function thing_error() : BelongsTo {
        return $this->belongsTo(ThingError::class,'thing_error_id','id');
    }

    /** @var array<string,string|IThingAction> $action_type_lookup  */
    protected static array $action_type_lookup = [];


    public function getAction() : IThingAction {
        return static::resolveAction(action_type: $this->action_type,action_id: $this->action_type_id);
    }

    protected static function resolveAction(string $action_type, int $action_id) : ?IThingAction {
        $resolver = static::$action_type_lookup[$action_type]??null;
        if (!$resolver) {return null;}
        return $resolver::resolveAction(action_id: $action_id);
    }

    public static function registerActionType(IThingAction|string $action_class) :void {
        $interfaces = class_implements($action_class);
        if (!isset($interfaces['Hexbatch\Things\Helpers\IThingAction'])) {
            throw new HbcThingException("$action_class is not an IThingAction");
        }
        $action_type = $action_class::getActionType();
        static::$action_type_lookup[$action_type] = $action_class;
    }



    public function isComplete() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS || $this->thing_status === TypeOfThingStatus::THING_ERROR) {
            return true;
        }
        return false;
    }




    /**
     * @return Thing[]
     */
    public function getLeaves() {
        return [];
        //todo get the leaves via sql
    }


    public function setStartedAt() {
        $this->update([
            'thing_started_at'=>DB::raw("NOW()")
        ]);
    }

    public function setException(Exception $e) {
        $hex = ThingError::createFromException($e);
        $this->thing_error_id = $hex->id;
        $this->save();
    }



    /**
     * @throws Exception
     */
    public function runThing() :void {
        if ($this->thing_status !== TypeOfThingStatus::THING_PENDING) {
            if ($this->isComplete()) {
                return;
            }
            //requeue, something happened in the pauses between steps
            RunThing::dispatch($this);
            return;
        }
        /** @var IThingAction|null $action */

        try {
            DB::beginTransaction();
            $action = static::resolveAction(action_type: $this->action_type,action_id: $this->action_type_id);
            $action->runAction();

            $this->thing_status = match ($action->getActionStatus()) {
                TypeOfThingStatus::THING_PAUSED,
                TypeOfThingStatus::THING_HOOKED,
                TypeOfThingStatus::THING_PENDING,
                TypeOfThingStatus::THING_WAITING,
                TypeOfThingStatus::THING_BUILDING => TypeOfThingStatus::THING_PENDING,
                TypeOfThingStatus::THING_SUCCESS => TypeOfThingStatus::THING_SUCCESS,
                TypeOfThingStatus::THING_ERROR => TypeOfThingStatus::THING_ERROR
            };

            $this->save();

            if (in_array($action->getActionStatus(),[TypeOfThingStatus::THING_SUCCESS,TypeOfThingStatus::THING_ERROR]) ) {
                //it is done, for better or worse

                if ($this->parent_thing_id) {
                    //notify parent action of result
                    $this->thing_parent->getAction()->setChildActionResult($action->getActionResult());
                } else {
                    $this->thing_result->result_response = $action->getActionResult();
                    $this->thing_result->result_http_status = $action->getActionHttpCode();
                    $this->thing_result->save();
                    $this->thing_result->dispatchResult();
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->thing_status = TypeOfThingStatus::THING_ERROR;
            $this->save();
            throw $e;
        }


    }

    public function maybeQueueParent() : bool {
        foreach ($this->thing_children as $thang) {
            if (!$thang->isComplete()) {return false;}
        }
        if ($this->thing_parent) {
            RunThing::dispatch($this->thing_parent);
        }
        return true;
    }



    public function pushLeavesToJobs() {
        if ($this->thing_status !== TypeOfThingStatus::THING_BUILDING) {
            throw new LogicException("Cannot push what is already built");
        }
        foreach ($this->getLeaves() as $leaf) {
            RunThing::dispatch($leaf);
        }

    }


    public static function makeThingTree(
                                           IThingAction $action,
                                            string|int|Carbon $start_at = null,
                                            string|int|Carbon $invalid_at = null
    )
    : Thing {


        $root = new Thing();
        if ($start_at) {
            $root->thing_start_at  = Carbon::parse($start_at)->timezone('UTC')->toIso8601String();
        }
        if ($invalid_at) {
            $root->thing_invalid_at  = Carbon::parse($invalid_at)->timezone('UTC')->toIso8601String();
        }

        $root->action_type = $action::getActionType();
        $root->action_type_id = $action->getActionId();
        $root->action_priority = $action->getActionPriority();
        $root->save();

        //make result
        $result = new ThingResult();
        $result->owner_thing_id = $root->id;
        $result->save();

        $tree = $action->getChildrenTree();
        $roots = $tree->getRootNodes();
        foreach ( $roots as $a_node) {
            static::makeTreeNodes(parent_thing:$root,node: $a_node);
        }
        $thing =  static::getThing(id:$root->id);
        $thing->pushLeavesToJobs();
        return $thing;
    }




    protected static function makeTreeNodes(Thing $parent_thing, \BlueM\Tree\Node $node) : void {

        /** @var IThingAction $root_action */
        /** @noinspection PhpUndefinedFieldInspection accessed via magic method*/
        $the_action = $node->action;

        $tree_node = new Thing();
        $tree_node->thing_start_at = $parent_thing->thing_start_at;
        $tree_node->thing_invalid_at = $parent_thing->thing_invalid_at;
        $tree_node->parent_thing_id = $parent_thing->id;
        $tree_node->action_type = $the_action::getActionType();
        $tree_node->action_type_id = $the_action->getActionId();
        $tree_node->action_priority = $the_action->getActionPriority();
        $tree_node->save();

        $children = $node->getChildren();

        foreach ( $children as $child) {
            static::makeTreeNodes(parent_thing: $tree_node,node: $child);
        }
    }


    public static function getThing(
        ?int $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
    )
    : Thing
    {
        $ret = static::buildThing(id:$id, action_type_id: $action_type_id,action_type: $action_type)->first();

        if (!$ret) {
            $arg_types = [];
            $arg_vals = [];
            if ($id) { $arg_types[] = 'id'; $arg_vals[] = $id;}
            if ($action_type) { $arg_types[] = 'Action type'; $arg_vals[] = $action_type;}
            if ($action_type_id) { $arg_types[] = 'Action id'; $arg_vals[] = $action_type_id;}
            $arg_val = implode('|',$arg_vals);
            $arg_type = implode('|',$arg_types);
            throw new LogicException("Could not find thing via $arg_type : $arg_val");
        }
        return $ret;
    }

    public static function buildThing(
        ?int    $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  Thing::select('things.*')
            ->selectRaw(" extract(epoch from  things.created_at) as created_at_ts,  extract(epoch from  things.updated_at) as updated_at_ts")
        ;

        if ($id) {
            $build->where('things.id',$id);
        }

        if ($action_type) {
            $build->where('things.action_type_id',$action_type);
        }

        if ($action_type_id) {
            $build->where('things.action_type',$action_type_id);
        }





        /**
         * @uses Thing::thing_collection()
         */
        $build->with('thing_collection');

        /**
         * @uses Thing::thing_result(),Thing::thing_parent(),Thing::thing_children(),Thing::thing_error(),Thing::thing_root()
         */
        $build->with('thing_result','thing_parent','thing_children','thing_error','thing_root');

        return $build;
    }

}
