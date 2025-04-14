<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Exceptions\HbcThingStackException;
use Hexbatch\Things\Exceptions\HbcThingTreeLimitException;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingCallback;
use Hexbatch\Things\Jobs\RunThing;
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
 * @property ArrayObject thing_tags
 *
 * @property string created_at
 * @property int created_at_ts
 * @property string updated_at
 *
 * @property ThingStat thing_stat
 * @property ThingError thing_error
 * @property ThingSetting thing_setting
 * @property Thing thing_parent
 * @property Thing thing_root
 * @property Thing[]|\Illuminate\Database\Eloquent\Collection thing_children
 * @property ThingHooker[]|\Illuminate\Database\Eloquent\Collection applied_hooks
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
        'thing_tags' => AsArrayObject::class,
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

    public function thing_setting() : HasOne {
        return $this->hasOne(ThingSetting::class,'setting_about_thing_id','id');
    }


    public function thing_error() : BelongsTo {
        return $this->belongsTo(ThingError::class,'thing_error_id','id');
    }


    public function thing_stat() : HasOne {
        return $this->hasOne(ThingStat::class,'stat_thing_id','id');
    }

    public function applied_hooks() : HasMany {
        return $this->hasMany(ThingHooker::class,'hooked_thing_id','id')
            /** @uses ThingHooker::parent_hook(),ThingHooker::hooker_thing(),ThingHooker::hooker_callbacks() */
            ->with('parent_hook','hooker_thing','hooker_callbacks');
    }

    /**
     * @param TypeOfThingHookMode $mode
     * @return ThingHooker[]
     */
    public function dispatchHooksOfMode(TypeOfThingHookMode $mode) : array {
        $blocking = [];
        foreach ($this->hasHooksOfMode(mode:$mode) as $hooker) {
            if ($hooker->parent_hook->isBlocking()) {
                $blocking[] = $hooker;
            }
            $hooker->dispatchHooker();
        }
        return $blocking;
    }

    public function resumeBlockedThing() {
        if ($this->isBlocked()) {
            $this->thing_status = TypeOfThingStatus::THING_PENDING;
            $this->save();
            $this->pushLeavesToJobs();
        }
    }

    /**
     * @param TypeOfThingHookMode $mode
     * @return ThingHooker[]
     */
    public function hasHooksOfMode(TypeOfThingHookMode $mode) : array {
        $ret = [];
        foreach ($this->applied_hooks as $hooker) {
            if ($hooker->parent_hook->hook_mode === $mode) {
                $ret[] = $hooker;
            }
        }
        return $ret;
    }



    public function isComplete() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS ||
            $this->thing_status === TypeOfThingStatus::THING_ERROR   ||
            $this->thing_status === TypeOfThingStatus::THING_SHORT_CIRCUITED   ||
            $this->thing_status === TypeOfThingStatus::THING_INVALID   ||
            $this->thing_status === TypeOfThingStatus::THING_FAIL

        ) {
            return true;
        }
        return false;
    }


    public function isIntrupted() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_RESOURCES ||
            $this->thing_status === TypeOfThingStatus::THING_HOOKED_BEFORE_RUN

        ) {
            return true;
        }
        return false;
    }

    public function isBlocked() : bool {
        if (
            $this->thing_status === TypeOfThingStatus::THING_HOOKED_BEFORE_RUN

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
            if($this->thing_invalid_at) {
                if(Carbon::parse($this->thing_start_at)->isAfter($this->thing_invalid_at) ) {
                    //it fails
                    $this->thing_status = TypeOfThingStatus::THING_INVALID;
                    $this->save();
                }
            }

            if (!$this->isComplete())
            {
                //see if need to build more
                $action = static::resolveAction(action_type: $this->action_type, action_id: $this->action_type_id);
                $new_children = $this->buildMore(action: $action);
                if (count($new_children)) {
                    // place the leaves of these and then return
                    foreach ($new_children as $new_child) {
                        $new_child->pushLeavesToJobs();
                    }
                } else if ($this->thing_stat->checkForDataOverflow()) {
                    $this->doBackoff();
                    $this->save();
                    $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::NODE_RESOURCES_NOTICE);
                } else {
                    $action->setLimitDataByteRows($this->thing_stat->getDataLimit());
                    $blocking = $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::NODE_BEFORE_RUNNING_HOOK);

                    if (count($blocking)) {
                        $this->thing_status = TypeOfThingStatus::THING_HOOKED_BEFORE_RUN;
                        $this->save(); //after hooks finished and resume, the node goes through this function again
                    } else {
                        $send_back_to_pending = false;
                        $not_done = false;
                        $data_for_this = [];
                        $constant_data = $this->thing_stat->stat_constant_data->getArrayCopy();
                        $data_for_parent = [];
                        ThingHooker::getHookerData(
                            thing_id: $this->id, mode: TypeOfThingHookMode::NODE_BEFORE_RUNNING_HOOK, b_out_of_time: $send_back_to_pending,
                            b_still_pending: $not_done, data_for_this: $data_for_this, data_for_parent: $data_for_parent);
                        if (!$send_back_to_pending && !$not_done) {
                            $all_data_to_action = array_merge($data_for_this,$constant_data);
                            $action->runAction($all_data_to_action); //set hook data
                            if (!empty($data_for_parent)) {
                                $this->thing_parent->getAction()->addDataBeforeRun($data_for_parent);
                            }
                            $this->thing_stat->updateDataStats(action: $action);

                            if ($action->isActionComplete()) {
                                if ($action->isActionSuccess()) {
                                    $this->thing_status = TypeOfThingStatus::THING_SUCCESS;
                                } else if ($action->isActionFail()) {
                                    $this->thing_status = TypeOfThingStatus::THING_FAIL;
                                } else {
                                    $this->thing_status = TypeOfThingStatus::THING_ERROR;
                                }
                            } else {
                                $this->thing_status = TypeOfThingStatus::THING_PENDING;
                            }

                            $this->save();


                            if ($this->thing_stat->checkForDataOverflow()) {
                                //do backoff of its parent, if no parent then no backoff
                                $this->thing_parent?->doBackoff();
                                $this->thing_parent?->dispatchHooksOfMode(mode: TypeOfThingHookMode::NODE_RESOURCES_NOTICE);
                            }


                            if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS) {
                                $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::NODE_SUCCESS_NOTICE);
                            } elseif ($this->thing_status === TypeOfThingStatus::THING_ERROR || $this->thing_status === TypeOfThingStatus::THING_FAIL) {
                                $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::NODE_FAILURE_NOTICE);
                            }
                            if ($action->isActionComplete()) {

                                //it is done, for better or worse


                                if ($this->parent_thing_id) {
                                    $new_children = [];
                                    //notify parent action of result
                                    $this->thing_parent->getAction()->setChildActionResult(child: $action);
                                    if ($this->thing_parent->getAction()->isActionComplete()) {
                                        if ($this->thing_parent->getAction()->isActionSuccess()) {
                                            $this->thing_parent->thing_status = TypeOfThingStatus::THING_SUCCESS;
                                        } else if ($this->thing_parent->getAction()->isActionFail()) {
                                            $this->thing_parent->thing_status = TypeOfThingStatus::THING_FAIL;
                                        } else {
                                            $this->thing_parent->thing_status = TypeOfThingStatus::THING_ERROR;
                                        }
                                        $this->thing_parent->save();
                                        $this->thing_parent->markIncompleteDescendantsAs(TypeOfThingStatus::THING_SHORT_CIRCUITED);
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
                                    if (count($new_children) === 0) {
                                        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS) {
                                            $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_SUCCESS_NOTICE);
                                        } elseif ($this->thing_status === TypeOfThingStatus::THING_ERROR || $this->thing_status === TypeOfThingStatus::THING_FAIL) {
                                            $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_FAILURE_NOTICE);
                                        }
                                    }
                                } else {
                                    $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_FINISHED_NOTICE);
                                    $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::SYSTEM_TREE_RESULTS);
                                } //else no parent
                            } //if action is complete (it will run again next time this is called for the thing)
                        } //if not sent back to pending (ttl)
                    } //else not blocked
                } //else not overflowed
            } //if not complete
            DB::commit();
        } catch (HbcThingStackException) {
            DB::commit();
            $this->thing_status = TypeOfThingStatus::THING_RESOURCES;
            $this->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_RESOURCES_NOTICE);
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
        //todo recurse through the tree and see if any descendants are async, if they are, then entire tree is async
    }

    /**
     * @param array<string,IThingCallback[]> $callbacks
     * @throws Exception
     */
    public static function buildFromAction(IThingAction $action, array $callbacks = [], bool $b_run_now = true, array $extra_tags = []) : ?ThingHooker {

        try {
            DB::beginTransaction();
            $root = static::makeThingTree(action: $action, callbacks: $callbacks,extra_tags: $extra_tags);
            $blocking = $root->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_CREATION_HOOK);
            if (empty($blocking) && $b_run_now) {
                $blocking = $root->dispatchHooksOfMode(mode: TypeOfThingHookMode::TREE_STARTING_HOOK);
                if (empty($blocking)) {
                    $root->pushLeavesToJobs();
                }

            }
            DB::commit();
            return ThingHooker::buildHooker(thing_id: $root->id, mode: TypeOfThingHookMode::SYSTEM_TREE_RESULTS)->first();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }


    /**
     * @param  array<string,IThingCallback[]> $callbacks
     * @throws Exception
     */
    protected static function makeThingTree(
        IThingAction $action,
        array $callbacks = [],
        ?string $hint = null,
        array $extra_tags = []
    )
    : Thing {

        try {
            DB::beginTransaction();
            $limit = 0;
            if (ThingSetting::isTreeOverflow(action_type: $action::getActionType(), action_type_id: $action->getActionId(),
                owner_type: $action->getActionOwner()?$action->getActionOwner()::getOwnerType():null,
                owner_type_id: $action->getActionOwner()?->getOwnerId(),limit: $limit)
            ) {
                throw new HbcThingTreeLimitException(sprintf("New trees for action %s %s owned by %s %s are limited to %s",
                    $action::getActionType(), $action->getActionId(),
                    $action->getActionOwner()?$action->getActionOwner()::getOwnerType():null,
                    $action->getActionOwner()?->getOwnerId(), $limit
                ));
            }

            $root = static::makeThingFromAction(parent_thing: null, action: $action,extra_tags: $extra_tags);

            $tree = $action->getChildrenTree(key: $hint);
            $roots = $tree->getRootNodes();
            foreach ($roots as $a_node) {
                static::makeTreeNodes(parent_thing: $root, node: $a_node,  callbacks: $callbacks);
            }
            ThingStat::updateStatsAfterBuilding(thing:$root);

            ThingHook::makeHooksForThing(thing: $root,  callbacks: $callbacks);
            DB::commit();
            return static::getThing(id: $root->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @return Thing[]
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    protected static function makeThingFromAction(?Thing $parent_thing,IThingAction $action,array $extra_tags = []) : Thing {
        try {
            DB::beginTransaction();
            $tree_node = new Thing();
            if ($start_at = $action->getStartAt()) {
                $tree_node->thing_start_at = Carbon::parse($start_at)->timezone('UTC')->toDateTimeString();
            } else {
                $tree_node->thing_start_at = null; //children can start earlier if not defined
            }
            if ($invalid_at = $action->getInvalidAt()) {
                $tree_node->thing_invalid_at = Carbon::parse($invalid_at)->timezone('UTC')->toDateTimeString();
            } else {
                $tree_node->thing_invalid_at = $parent_thing?->thing_invalid_at ?? null;
            }


            $tree_node->parent_thing_id = $parent_thing?->id ?? null;
            $tree_node->root_thing_id = $parent_thing?->root_thing_id ?? null;
            if (!$tree_node->root_thing_id && $tree_node->parent_thing_id) {
                $tree_node->root_thing_id = $tree_node->parent_thing_id;
            }
            $tree_node->action_type = $action::getActionType();
            $tree_node->action_type_id = $action->getActionId();
            $tree_node->owner_type = $action->getActionOwner()?$action->getActionOwner()::getOwnerType():null;
            $tree_node->owner_type_id = $action->getActionOwner()?->getOwnerId();
            $tree_node->thing_priority = $action->getActionPriority();
            $tree_node->is_async = $action->isAsync();
            $tree_node->thing_tags = array_merge($action->getActionTags()??[],$extra_tags);
            $tree_node->thing_constant_data = $action->getInitialConstantData(); //mulched up by the stats
            $tree_node->save();
            ThingSetting::makeStatFromSettings(thing: $tree_node);
            DB::commit();
            return static::getThing(id: $tree_node->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * @param  array<string,IThingCallback[]> $callbacks
     * @throws Exception
     */
    protected static function makeTreeNodes(Thing $parent_thing, \BlueM\Tree\Node $node,array $callbacks = []) : Thing {
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
        ThingHook::makeHooksForThing(thing: $tree_node,  callbacks: $callbacks);
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
         * @uses Thing::thing_parent(),Thing::thing_children(),Thing::thing_error(),
         * @uses Thing::thing_root(),Thing::thing_stat(),Thing::applied_hooks(),static::thing_setting()
         */
        $build->with('thing_parent','thing_children','thing_error',
            'thing_root','thing_stat','applied_hooks','thing_setting');

        return $build;
    }

    /**
     * gets ancestor chain with the root in element 0
     * @return array<Thing>
     */
    public function getAncestorChain() : array {
        $ret = [];
        $it = $this;
        $ret[] = $it;
        while($it->thing_parent) {
            $it = $it->thing_parent;
            $ret[] = $it;
        }
        return array_reverse($ret);
    }

    public function countDescendants() : int {
        //todo use sql to count the descendants this has
        return 0;
    }


}
