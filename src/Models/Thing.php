<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Exceptions\HbcThingStackException;
use Hexbatch\Things\Exceptions\HbcThingTreeLimitException;
use Hexbatch\Things\Helpers\IThingAction;
use Hexbatch\Things\Helpers\IThingCallback;
use Hexbatch\Things\Jobs\RunThing;
use Hexbatch\Things\Models\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 *
 * thing_start_at
 * thing_invalid_at
 * thing_started_at
 * is_async
 * ref_uuid
 *
 * thing_status
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int parent_thing_id
 * @property int root_thing_id
 * @property int thing_error_id
 * @property int thing_priority
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property bool is_async
 *
 *
 * @property string thing_start_at
 * @property string thing_invalid_at
 * @property string thing_started_at
 * @property string ref_uuid
 * @property TypeOfThingStatus thing_status
 * @property ArrayObject thing_constant_data
 *
 * @property string created_at
 * @property int created_at_ts
 * @property string updated_at
 *
 * @property ThingStat thing_stat
 * @property ThingResult thing_result
 * @property ThingError thing_error
 * @property Thing thing_parent
 * @property Thing thing_root
 * @property Thing[]|\Illuminate\Database\Eloquent\Collection thing_children
 * @property ThingHooker[]|\Illuminate\Database\Eloquent\Collection da_hooks
 */
class Thing extends Model
{
    use ThingOwnerHandler,ThingActionHandler;
    protected $table = 'things';
    public $timestamps = false;

    /** @var array<int, string> */
    protected $fillable = [
        'parent_thing_id',
        'thing_error_id',
        'parent_thing_id',
        'thing_priority',
        'action_type',
        'action_type_id',
        'thing_start_at',
        'thing_started_at',
        'thing_invalid_at',
        'thing_status',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    /* @var array<string, string> */
    protected $casts = [
        'thing_status' => TypeOfThingStatus::class,
        'thing_constant_data' => AsArrayObject::class,
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


    public function thing_stat() : BelongsTo {
        return $this->belongsTo(ThingStat::class,'stat_thing_id','id');
    }

    public function da_hooks() : HasMany {
        return $this->hasMany(ThingHooker::class,'hooked_thing_id','id')
            /** @uses ThingHooker::hooker_parent(),ThingHooker::hooker_thing() */
            ->with('hooker_parent','hooker_thing');

    }

    /**
     * @param TypeOfThingHookMode $mode
     * @return ThingHooker[]
     */
    public function dispatchHooksOfMode(TypeOfThingHookMode $mode) : array {
        $blocking = [];
        foreach ($this->hasHooksOfMode(mode:$mode) as $hooker) {
            if ($hooker->hooker_parent->isBlocking()) {
                $blocking[] = $hooker;
            }
            $hooker->dispatchHooker();
        }
        return $blocking;
    }

    public function resumeBlockedThing() {
        if (! $this->isIntrupted()) { return;}
        $this->pushLeavesToJobs();
    }

    /**
     * @param TypeOfThingHookMode $mode
     * @return ThingHooker[]
     */
    public function hasHooksOfMode(TypeOfThingHookMode $mode) : array {
        $ret = [];
        foreach ($this->da_hooks as $hooker) {
            if ($hooker->hooker_parent->hook_mode === $mode) {
                $ret[] = $hooker;
            }
        }
        return $ret;
    }


    public function isComplete() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS ||
            $this->thing_status === TypeOfThingStatus::THING_ERROR   ||
            $this->thing_status === TypeOfThingStatus::THING_SHORT_CIRCUITED

        ) {
            return true;
        }
        return false;
    }


    public function isIntrupted() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_RESOURCES ||
            $this->thing_status === TypeOfThingStatus::THING_PAUSED   ||
            $this->thing_status === TypeOfThingStatus::THING_HOOKED

        ) {
            return true;
        }
        return false;
    }




    /**
     * @return Thing[]
     */
    protected function getLeaves() {
        return [];
        //todo get the leaves via sql, get only the highest priority (all the same priority) and return them
        // this is recursive (in sql), so the highest priority here, gets the highest priority of the children, which get their children's highest, and so on
        // when there are no more leaves of that highest at this tree level, go to next highest priority
        // also only get the leaves that can start now, and must not be pending, but building only
    }

    protected function markIncompleteDescendantsAs(TypeOfThingStatus $status) {
        //todo find all the incomplete descendants, and put their status as this
    }


    protected function setStartedAt() {
        $this->update([
            'thing_started_at'=>DB::raw("NOW()")
        ]);
    }

    protected function setException(Exception $e) {
        $hex = ThingError::createFromException($e);
        $this->thing_error_id = $hex->id;
        $this->save();
    }


    protected function doBackoff() {
        $this->thing_status = TypeOfThingStatus::THING_PENDING;
        $this->thing_start_at = $this->thing_stat->getBackoffFutureTime();
        $this->save();
    }

    /**
     * @throws Exception
     */
    public function runThing() :void {
        if ($this->thing_status !== TypeOfThingStatus::THING_PENDING) {
            if ($this->isComplete() || $this->isIntrupted()) {
                return;
            }
            //requeue, something happened in the pauses between steps
            RunThing::dispatch($this);
            return;
        }
        /** @var IThingAction|null $action */

        try {
            DB::beginTransaction();
            $this->setStartedAt();
            //see if need to build more

            $action = static::resolveAction(action_type: $this->action_type,action_id: $this->action_type_id);
            $new_children = $this->buildMore(action: $action);
            if (count($new_children)) {
                // place the leaves of these and then return
                foreach ($new_children as $new_child) {
                    $new_child->pushLeavesToJobs();
                }
                return;
            }
            if ($this->thing_stat->checkForDataOverflow()) {
                $this->doBackoff();
                return;
            }
            $action->setLimitDataByteRows($this->thing_stat->getDataLimit());
            $data_from_hook = [];
            $action->runAction($data_from_hook); //set page length
            $this->thing_stat->updateDataStats(action: $action);

            $this->thing_status = match ($action->getActionStatus()) {
                TypeOfThingStatus::THING_PAUSED,
                TypeOfThingStatus::THING_HOOKED,
                TypeOfThingStatus::THING_PENDING,
                TypeOfThingStatus::THING_BUILDING => TypeOfThingStatus::THING_PENDING,
                TypeOfThingStatus::THING_SUCCESS => TypeOfThingStatus::THING_SUCCESS,
                TypeOfThingStatus::THING_ERROR => TypeOfThingStatus::THING_ERROR,
                TypeOfThingStatus::THING_RESOURCES => TypeOfThingStatus::THING_RESOURCES,
                TypeOfThingStatus::THING_SHORT_CIRCUITED => TypeOfThingStatus::THING_SHORT_CIRCUITED,
            };

            $this->save();

            if ($this->thing_stat->checkForDataOverflow()) {
                //do backoff of its parent, if no parent then no backoff
                $this->thing_parent?->doBackoff();
                return;
            }

            if (in_array($action->getActionStatus(),[TypeOfThingStatus::THING_SUCCESS,TypeOfThingStatus::THING_ERROR]) ) {
                //it is done, for better or worse

                if ($this->parent_thing_id) {
                    //notify parent action of result
                    $this->thing_parent->getAction()->setChildActionResult(child: $action);
                    if ($this->thing_parent->isComplete()) {
                        $this->markIncompleteDescendantsAs(TypeOfThingStatus::THING_SHORT_CIRCUITED);
                    } else {
                        //maybe the parent wants to add new children after getting the news
                        $new_children = $this->buildMore(action: $this->thing_parent->getAction());
                        if (count($new_children)) {
                            // place the leaves of these and then return
                            foreach ($new_children as $new_child) {
                                $new_child->pushLeavesToJobs();
                            }
                        }
                    }
                } else {
                    $this->thing_result->result_response = $action->getActionResult();
                    $this->thing_result->result_http_status = $action->getActionHttpCode();
                    $this->thing_result->save();
                    $this->thing_result->dispatchResult();
                }
            }

            DB::commit();
        } catch (HbcThingStackException) {
            DB::commit();
            $this->thing_status = TypeOfThingStatus::THING_RESOURCES;
            $this->save();
        }catch (Exception $e) {
            DB::rollBack();
            $this->thing_status = TypeOfThingStatus::THING_ERROR;
            $this->setException($e);
            $this->save();
            throw $e;
        }
        //see if all children ran, if so, put the parent on the processing
        $this->maybeQueueParent();

    }

    protected function maybeQueueParent() : bool {
        foreach ($this->thing_children as $thang) {
            if (!$thang->isComplete()) {return false;}
        }
        if ($this->thing_parent) {

            RunThing::dispatch($this->thing_parent);
        }
        return true;
    }



    protected function pushLeavesToJobs() {
        if ($this->thing_status !== TypeOfThingStatus::THING_BUILDING) {
            throw new LogicException("Cannot push what is already built");
        }
        foreach ($this->getLeaves() as $leaf) {
            $leaf->thing_status = TypeOfThingStatus::THING_PENDING;
            if ($leaf->thing_root->isTreeAsync()) {
                //todo make queues
                RunThing::dispatch($leaf);
            } else {
                RunThing::dispatchSync($leaf);
            }

        }
    }

    public function isTreeAsync() : bool {
        return $this->is_async;
        //todo recurse through the three and see if any nodes are async, if they are, then entire tree is async
    }

    /**
     * @param IThingAction $action
     * @param IThingCallback[] $callbacks
     * @return ThingResult
     */
    public static function runAction(IThingAction $action,array $callbacks = []) : ThingResult {

        $root = static::makeThingTree(action: $action);
        $root->thing_result->setCallbacks($callbacks);
        $blocking = $root->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_CREATION_HOOK);
        if (empty($blocking)) {
            $root->pushLeavesToJobs();
        }

        return $root->thing_result; //if not async, this will be completed, else pending

    }



    protected static function makeThingTree(
        IThingAction $action
    )
    : Thing {

        if ($limit = ThingSetting::checkForTreeOverflow(action_type: $action::getActionType(),action_type_id: $action->getActionId(),
            owner_type: $action->getActionOwner()::getOwnerType(),owner_type_id: $action->getActionOwner()->getOwnerId())
        ) {
            throw new HbcThingTreeLimitException(sprintf("New trees for action %s %s owned by %s %s are limited to %s",
                $action::getActionType(),$action->getActionId(),$action->getActionOwner()::getOwnerType(), $action->getActionOwner()->getOwnerId(),$limit
            ) );
        }

        $root = static::makeThingFromAction(parent_thing: null,action: $action);

        //make result
        $result = new ThingResult();
        $result->owner_thing_id = $root->id;
        $result->save();



        $tree = $action->getChildrenTree();
        $roots = $tree->getRootNodes();
        foreach ( $roots as $a_node) {
            static::makeTreeNodes(parent_thing:$root,node: $a_node);
        }

        return  static::getThing(id:$root->id);
    }

    /** @return Thing[] */
    protected function buildMore(IThingAction $action) :array {
        $ret = [];
        if ($key = $action->isMoreBuilding()) {
            $tree = $action->getChildrenTree(key: $key);
            $roots = $tree->getRootNodes();

            foreach ( $roots as $a_node) {
               $ret[] =  static::makeTreeNodes(parent_thing:$this,node: $a_node);
            }
        }
        return $ret;
    }

    protected static function makeThingFromAction(?Thing $parent_thing,IThingAction $action) : Thing {
        $tree_node = new Thing();
        if ($start_at = $action->getStartAt()) {
            $tree_node->thing_start_at  = Carbon::parse($start_at)->timezone('UTC')->toIso8601String();
        } else {
            $tree_node->thing_start_at = $parent_thing?->thing_start_at??null;
        }
        if ($invalid_at = $action->getInvalidAt()) {
            $tree_node->thing_invalid_at  = Carbon::parse($invalid_at)->timezone('UTC')->toIso8601String();
        } else {
            $tree_node->thing_invalid_at = $parent_thing?->thing_invalid_at??null;
        }


        $tree_node->parent_thing_id = $parent_thing?->id??null;
        $tree_node->action_type = $action::getActionType();
        $tree_node->action_type_id = $action->getActionId();
        $tree_node->owner_type = $action->getActionOwner()::getOwnerType();
        $tree_node->owner_type_id = $action->getActionOwner()->getOwnerId();
        $tree_node->thing_priority = $action->getActionPriority();
        $tree_node->is_async = $action->isAsync();
        $tree_node->save();
        ThingSetting::makeStatFromSettings(thing: $tree_node);
        ThingHook::makeHooksForThing(thing: $tree_node);
        return  static::getThing(id:$tree_node->id);
    }


    protected static function makeTreeNodes(Thing $parent_thing, \BlueM\Tree\Node $node) : Thing {
        if ($over_by_n = $parent_thing->thing_stat->checkForDescendantOverflow(n_extra: 1)) {
            throw new HbcThingStackException("Exceeds building by $over_by_n");
        }
        /** @var IThingAction $root_action */
        /** @noinspection PhpUndefinedFieldInspection accessed via magic method*/
        $the_action = $node->action;
        $tree_node = static::makeThingFromAction(parent_thing: $parent_thing,action: $the_action);
        $children = $node->getChildren();

        foreach ( $children as $child) {
            static::makeTreeNodes(parent_thing: $tree_node,node: $child);
        }

        return $tree_node;
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
         * @uses Thing::thing_result(),Thing::thing_parent(),Thing::thing_children(),Thing::thing_error(),Thing::thing_root(),Thing::thing_stat(),Thing::da_hooks()
         */
        $build->with('thing_result','thing_parent','thing_children','thing_error','thing_root','thing_stat','da_hooks');

        return $build;
    }

}
