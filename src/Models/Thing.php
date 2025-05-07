<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Exceptions\HbcThingStackException;
use Hexbatch\Things\Exceptions\HbcThingTreeLimitException;
use Hexbatch\Things\Helpers\CalculatedSettings;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
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
use Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries;

/**
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin BuildsExpressionQueries
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
     * @param TypeOfHookMode $mode
     * @return ThingHooker[]
     */
    public function dispatchHooksOfMode(TypeOfHookMode $mode) : array {
        $blocking = [];
        foreach ($this->hasHooksOfMode(mode:$mode) as $hooker) {
            if ($hooker->parent_hook->isBlocking()) {
                $blocking[] = $hooker;
            }
            $hooker->dispatchHooker(); //todo when is hooker made? at tree creation or when node fires? many nodes may not fire. Should be at runtime
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
     * @param TypeOfHookMode $mode
     * @return ThingHooker[]
     */
    public function hasHooksOfMode(TypeOfHookMode $mode) : array {
        $ret = [];
        foreach ($this->applied_hooks as $hooker) {
            if ($hooker->parent_hook->hook_mode === $mode) {
                $ret[] = $hooker;
            }
        }
        return $ret;
    }



    public function isComplete() : bool {
        return in_array($this->thing_status,TypeOfThingStatus::STATUSES_OF_COMPLETION);
    }

    public function isIntrupted() : bool {
        return in_array($this->thing_status,TypeOfThingStatus::STATUSES_OF_INTERRUPTION);
    }

    public function isBlocked() : bool {
        return $this->thing_status === TypeOfThingStatus::THING_HOOKED_BEFORE_RUN;
    }


    /** @return \Illuminate\Database\Eloquent\Collection|Thing[] */

    public function getLeaves() {

        $query_descendants = DB::table("things as desc_a")
            ->selectRaw('desc_a.id, 0 as level, desc_a.thing_priority as max_priority')->where('desc_a.id', $this->id)
            ->unionAll(
                DB::table('things as desc_b')
                    ->selectRaw('desc_b.id, level + 1 as level, max(desc_b.thing_priority) OVER (PARTITION BY desc_b.parent_thing_id) as max_priority')
                    ->join('thing_descendants', 'thing_descendants.id', '=', 'desc_b.parent_thing_id')
            );

        $query_nodes = DB::table("things as node_a")
            ->selectRaw("node_a.id, thing_descendants.level, thing_descendants.max_priority")
            ->where('node_a.id', $this->id)
            ->where('node_a.thing_status', TypeOfThingStatus::THING_BUILDING)
            ->whereRaw("(node_a.thing_start_at IS NULL OR node_a.thing_start_at <= NOW() )")
            ->whereRaw("(node_a.thing_invalid_at IS NULL OR node_a.thing_invalid_at < NOW())")

            ->join('thing_descendants', 'thing_descendants.id', '=', 'node_a.id')
            ->unionAll(
                DB::table('things as node_b')
                    ->selectRaw('node_b.id,thing_descendants.level as level,thing_descendants.max_priority')

                    ->join('thing_nodes', 'thing_nodes.id', '=', 'node_b.parent_thing_id')
                    ->join('thing_descendants', 'thing_descendants.id', '=', 'node_b.id')
                    ->whereRaw("node_b.thing_priority = thing_descendants.max_priority")
                    ->where('node_b.thing_status', TypeOfThingStatus::THING_BUILDING)
                    ->whereRaw("(node_b.thing_start_at IS NULL OR node_b.thing_start_at <= NOW() )")
                    ->whereRaw("(node_b.thing_invalid_at IS NULL OR node_b.thing_invalid_at < NOW())")

            )->withRecursiveExpression('thing_descendants',$query_descendants);

        $query_term = DB::table("things as term")->selectRaw("term.id, term.thing_priority, max(term.thing_priority) OVER () as max_thinger")
            ->join('thing_nodes', 'thing_nodes.id', '=', 'term.id')
            ->leftJoin('things as y', 'y.parent_thing_id', '=', 'term.id')
            ->whereNull('y.id')
            ->withRecursiveExpression('thing_nodes',$query_nodes)
            ;

        $lar =  Thing::select('things.*')
            ->selectRaw("extract(epoch from  things.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  things.updated_at) as updated_at_ts")
            ->selectRaw("extract(epoch from  things.thing_start_at) as thing_start_at_ts")
            ->selectRaw("extract(epoch from  things.thing_invalid_at) as thing_invalid_at_ts")
            ->withExpression('terminal_list',$query_term)
            ->join('terminal_list', 'terminal_list.id', '=', 'things.id')
            ->whereRaw("things.thing_priority = terminal_list.max_thinger")
            ;

        /** @var \Illuminate\Database\Eloquent\Collection|Thing[] */
        return $lar->get();

    }

    protected function markIncompleteDescendantsAs(TypeOfThingStatus $status) {
        static::buildThing(me_id: $this->id,include_my_descendants: true)
            ->where('id','<>',$this->id) //do not mark oneself
            ->whereNotIn('thing_status',TypeOfThingStatus::STATUSES_OF_COMPLETION)
            ->update(['thing_status'=>$status]);
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
        if (!$this->thing_stat->stat_back_offs_done) {
            $this->thing_stat->stat_back_offs_done = 1;
        } else {
            $this->thing_stat->stat_back_offs_done++;
        }
        $this->thing_stat->save();
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
            // something happened in the pauses between steps, so just stop running this
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
                    $this->pushLeavesToJobs();
                } else if ($this->thing_stat->checkForDataOverflow()) {
                    $this->doBackoff();
                    $this->save();
                    $this->dispatchHooksOfMode(mode: TypeOfHookMode::NODE_RESOURCES_NOTICE);
                } else {
                    $action->setLimitDataByteRows($this->thing_stat->stat_limit_data_byte_rows);
                    $blocking = $this->dispatchHooksOfMode(mode: TypeOfHookMode::NODE_BEFORE_RUNNING_HOOK);

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
                            thing_id: $this->id, mode: TypeOfHookMode::NODE_BEFORE_RUNNING_HOOK, b_out_of_time: $send_back_to_pending,
                            b_still_pending: $not_done, data_for_this: $data_for_this, data_for_parent: $data_for_parent);
                        if (!$send_back_to_pending && !$not_done) {
                            $all_data_to_action = array_merge($data_for_this,$constant_data);
                            $action->runAction($all_data_to_action); //set hook data
                            if (!empty($data_for_parent)) {
                                $this->thing_parent->getAction()?->addDataBeforeRun($data_for_parent);
                            }


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
                                $this->thing_parent?->dispatchHooksOfMode(mode: TypeOfHookMode::NODE_RESOURCES_NOTICE);
                            }


                            if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS) {
                                $this->dispatchHooksOfMode(mode: TypeOfHookMode::NODE_SUCCESS_NOTICE);
                            } elseif ($this->thing_status === TypeOfThingStatus::THING_ERROR || $this->thing_status === TypeOfThingStatus::THING_FAIL) {
                                $this->dispatchHooksOfMode(mode: TypeOfHookMode::NODE_FAILURE_NOTICE);
                            }
                            if ($action->isActionComplete()) {

                                //it is done, for better or worse


                                if ($this->parent_thing_id) {
                                    $new_children = [];
                                    //notify parent action of result
                                    $this->thing_parent->getAction()?->setChildActionResult(child: $action);
                                    if ($this->thing_parent->getAction()?->isActionComplete()) {
                                        if ($this->thing_parent->getAction()?->isActionSuccess()) {
                                            $this->thing_parent->thing_status = TypeOfThingStatus::THING_SUCCESS;
                                        } else if ($this->thing_parent->getAction()?->isActionFail()) {
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
                                            // place the leaves of these
                                            $this->pushLeavesToJobs();
                                        }
                                    }
                                    if (count($new_children) === 0) {
                                        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS) {
                                            $this->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_SUCCESS_NOTICE);
                                        } elseif ($this->thing_status === TypeOfThingStatus::THING_ERROR || $this->thing_status === TypeOfThingStatus::THING_FAIL) {
                                            $this->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_FAILURE_NOTICE);
                                        }
                                    }
                                } else {
                                    $this->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_FINISHED_NOTICE);
                                    $this->dispatchHooksOfMode(mode: TypeOfHookMode::SYSTEM_TREE_RESULTS);
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
            $this->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_RESOURCES_NOTICE);
            $this->save();
        }catch (Exception $e) {
            DB::rollBack();
            $this->thing_status = TypeOfThingStatus::THING_ERROR;
            $this->setException($e);
            $this->save();
            throw $e;
        }
        //see if all children ran, if so, put the parent on the processing
        $this->maybeQueueMore();

    }

    /**
     * If have children, if any non-pending have not finished, return
     * if only remaining children are pending, then push leaves
     * else push leaves of grandparent (starts )
     */
    protected function maybeQueueMore() : bool {

        $count_waiting_children = 0;
        foreach ($this->thing_children as $thang) {
            if ($thang->thing_status === TypeOfThingStatus::THING_PENDING) { $count_waiting_children++; }
        }

        foreach ($this->thing_children as $thang) {
            if (!$thang->isComplete()) {
                if (!$count_waiting_children) {  return false; }
            }
        }
        if ($count_waiting_children) { $this->pushLeavesToJobs();}

        //the parent is ready to run, but perhaps its priority is low and there are others to run first, so ask the grandparent to push leaves
        if ($this->thing_parent?->thing_parent) {
            $this->thing_parent?->thing_parent?->pushLeavesToJobs();
        } else {
            //if not grandparent, this means the parent is root, so run that
            $this->thing_parent->dispatchThing();
        }

        return true;
    }


    /**
     * leaf must run async if any of its ancestors are async, found in stats for it
     */
    protected function pushLeavesToJobs() :void {
        foreach ($this->getLeaves() as $leaf) {
            $leaf->dispatchThing();
        }
    }

    protected function dispatchThing() {
        $this->thing_status = TypeOfThingStatus::THING_PENDING;
        $this->save();
        if ($this->is_async) {
            RunThing::dispatch($this);
        } else {
            RunThing::dispatchSync($this);
        }
    }


    /**
     * @throws Exception
     */
    public static function buildFromAction(IThingAction $action, IThingOwner $owner,
                                           bool $b_run_now = true,
                                           array $extra_tags = []
    ): ?ThingHooker
    {

        try {
            DB::beginTransaction();
            $root = static::makeThingTree(action: $action, extra_tags: $extra_tags,owner: $owner);
            $blocking = $root->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_CREATION_HOOK);
            if (empty($blocking) && $b_run_now) {
                $blocking = $root->dispatchHooksOfMode(mode: TypeOfHookMode::TREE_STARTING_HOOK);
                if (empty($blocking)) {
                    $root->pushLeavesToJobs();
                }

            }
            DB::commit();
            return ThingHooker::buildHooker(thing_id: $root->id, mode: TypeOfHookMode::SYSTEM_TREE_RESULTS)->first();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

    }


    /**
     * @throws Exception
     */
    protected static function makeThingTree(
        IThingAction $action,
        ?string $hint = null,
        array $extra_tags = [],
        IThingOwner $owner = null
    )
    : Thing {
        $owner = $owner? : $action->getActionOwner();
        try {
            DB::beginTransaction();
            $limit = 0;
            $backoff_policy = 0;
            if (ThingSetting::isTreeOverflow(action: $action, owner: $owner,limit: $limit,backoff_policy: $backoff_policy)
            ) {
                throw new HbcThingTreeLimitException(sprintf("New trees for action %s %s, owned by %s %s, are limited to %s",
                    $action->getActionType(), $action->getActionId(),
                    $action->getActionOwner()->getOwnerType(),
                    $action->getActionOwner()?->getOwnerId(), $limit
                ));
            }

            $descendant_limit = ThingSetting::getTreeNodeLimit(action: $action,owner: $action->getActionOwner());
            $data_limit = ThingSetting::getDataLimit(action: $action,owner: $action->getActionOwner(),backoff_policy:$backoff_policy );
            $calcs = new CalculatedSettings(descendant_limit: $descendant_limit,data_limit: $data_limit,backoff_data_policy: $backoff_policy);
            $root = static::makeThingFromAction(parent_thing: null, action: $action,extra_tags: $extra_tags,owner: $owner,settings: $calcs);

            $tree = $action->getChildrenTree(key: $hint);
            $roots = $tree->getRootNodes();
            foreach ($roots as $a_node) {
                static::makeTreeNodes(parent_thing: $root, node: $a_node);
            }

            ThingHook::makeHooksForThing(thing: $root);
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
    protected function buildMore(?IThingAction $action) :array {
        if (!$action) {return [];}
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
    protected static function makeThingFromAction(?Thing $parent_thing,IThingAction $action,array $extra_tags = [],
                                                  IThingOwner $owner = null,?CalculatedSettings $settings = null)
    : Thing
    {
        if (!$owner) {
            $owner = $parent_thing?->getOwner();
            if (!$owner) {
                $owner = $action->getActionOwner();
            }
        }

        $root_tags = ($parent_thing?->thing_root?:$parent_thing)?->thing_tags?->getArrayCopy()??[];

        $calculated_priority = max($parent_thing?->thing_priority??0, $action->getActionPriority());

        if ($parent_thing?->is_async) {
            $async = true;
        } else {
            $async = $action->isAsync();
        }
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
            $tree_node->action_type = $action->getActionType();
            $tree_node->action_type_id = $action->getActionId();
            $tree_node->owner_type = $owner?->getOwnerType();
            $tree_node->owner_type_id = $owner?->getOwnerId();
            $tree_node->thing_priority = $calculated_priority;
            $tree_node->is_async = $async;
            $tree_node->thing_tags = array_merge($action->getActionTags()??[],$root_tags,$extra_tags);
            $tree_node->thing_constant_data = $action->getInitialConstantData(); //mulched up by the stats
            $tree_node->save();
            ThingSetting::makeStatFromSettings(thing: $tree_node,settings: $settings);
            DB::commit();
            return static::getThing(id: $tree_node->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * @throws Exception
     */
    protected static function makeTreeNodes(Thing $parent_thing, \BlueM\Tree\Node $node) : Thing {

        /** @var IThingAction $the_action */
        /** @noinspection PhpUndefinedFieldInspection accessed via magic method*/
        $the_action = $node->action;

        $children = $node->getChildren();
        $desc_limit = 0;
        $backoff_policy = 0;
        if (ThingSetting::isNodeOverflow(thing: $parent_thing->thing_root?:$parent_thing,limit: $desc_limit,backoff_policy: $backoff_policy)
        ) {
            throw new HbcThingTreeLimitException(sprintf("Tree nodes for action %s %s, owned by %s %s, are limited to %s",
                $parent_thing->getAction()?->getActionType()??'nothing',
                $parent_thing->getAction()?->getActionId(),
                $parent_thing->getOwner()?->getOwnerType()??'nobody',
                $parent_thing->getOwner()?->getOwnerId(), $desc_limit
            ));
        }

        $data_limit = ThingSetting::getDataLimit(action: $the_action,owner: $the_action->getActionOwner(),thing: $parent_thing,backoff_policy:$backoff_policy );
        $calcs = new CalculatedSettings(descendant_limit: $desc_limit,data_limit: $data_limit,backoff_data_policy: $backoff_policy);

        $tree_node = static::makeThingFromAction(parent_thing: $parent_thing,action: $the_action,settings: $calcs);

        foreach ( $children as $child) {
            static::makeTreeNodes(parent_thing: $tree_node,node: $child);
        }
        ThingHook::makeHooksForThing(thing: $tree_node);
        return $tree_node;
    }


    public static function getThing(
        ?int $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
    )
    : Thing
    {
        $ret = static::buildThing(me_id:$id, action_type_id: $action_type_id,action_type: $action_type)->first();

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
        ?int    $me_id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
        ?int    $owner_type_id = null,
        ?string $owner_type = null,
        ?bool   $is_root = null,
        bool    $include_my_descendants = false,
        bool    $eager_load = false
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  Thing::select('things.*')
            ->selectRaw(" extract(epoch from  things.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  things.updated_at) as updated_at_ts")
            ->selectRaw("extract(epoch from  things.thing_start_at) as thing_start_at_ts")
            ->selectRaw("extract(epoch from  things.thing_invalid_at) as thing_invalid_at_ts")
        ;

        if ($me_id) {
            $build->where('things.id',$me_id);
        }

        if ($action_type) {
            $build->where('things.action_type',$action_type);
        }

        if ($action_type_id) {
            $build->where('things.action_type_id',$action_type_id);
        }

        if ($owner_type) {
            $build->where('things.owner_type',$owner_type);
        }

        if ($owner_type_id) {
            $build->where('things.owner_type_id',$owner_type_id);
        }

        if ($is_root !== null) {
            if($is_root) {
                $build->whereNull('things.parent_thing_id');
            } else {
                $build->whereNotNull('things.parent_thing_id');
            }
        }

        if ($include_my_descendants && $me_id) {
            $build->withRecursiveExpression('my_thing_descendants',
                /** @param \Illuminate\Database\Query\Builder $query */
                function ($query) use($me_id)
                {
                    $query->from('things as s')->select('s.id')->where('s.id',$me_id)
                        ->unionAll(
                            DB::table('things as ant')->select('ant.id')
                                ->join('my_thing_descendants', 'my_thing_descendants.id', '=', 'ant.parent_thing_id')
                        );
                }
            )
                ->join('my_thing_descendants', 'my_thing_descendants.id', '=', 'things.id');
        }



        if ($eager_load) {
            /**
             * @uses Thing::thing_parent(),Thing::thing_children(),Thing::thing_error(),
             * @uses Thing::thing_root(),Thing::thing_stat(),Thing::applied_hooks(),static::thing_setting()
             */
            $build->with('thing_parent', 'thing_children', 'thing_error',
                'thing_root', 'thing_stat', 'applied_hooks', 'thing_setting');
        }

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



}
