<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Jobs\RunThing;
use Hexbatch\Things\Jobs\SendCallback;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Ramsey\Uuid\Uuid;

/**
 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Staudenmeir\LaravelCte\Query\Traits\BuildsExpressionQueries
 * @property int id
 * @property int parent_thing_id
 * @property int root_thing_id
 * @property int thing_error_id
 *
 * @property int thing_priority
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property bool is_async
 * @property bool is_signalling_when_done
 *
 *
 * @property string thing_start_after
 * @property string thing_invalid_after
 * @property string thing_started_at
 * @property string thing_ran_at
 *
 *
 * @property string ref_uuid
 * @property TypeOfThingStatus thing_status
 * @property ArrayObject thing_tags
 *
 * @property string created_at
 * @property int created_at_ts
 * @property string updated_at
 *
 * @property ThingError thing_error
 * @property Thing thing_parent
 * @property Thing thing_root
 * @property Thing[]|\Illuminate\Database\Eloquent\Collection thing_children
 * @property ThingCallback[]|\Illuminate\Database\Eloquent\Collection applied_callbacks
 * @property ThingHook[]|\Illuminate\Database\Eloquent\Collection attached_hooks
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
        'thing_start_after',
        'thing_started_at',
        'thing_invalid_after',
        'thing_ran_at',
        'thing_status',
        'is_signalling_when_done',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    /* @var array<string, string> */
    protected $casts = [
        'thing_status' => TypeOfThingStatus::class,
        'thing_tags' => AsArrayObject::class,
        'is_signalling_when_done' => 'boolean',
        'is_async' => 'boolean',
    ];


    public function attached_hooks() : HasManyThrough
    {

        return $this->hasManyThrough(
            ThingHook::class, //what is returned
            ThingCallback::class, //the connecting class
            'source_thing_id', // Foreign key on the connecting table...
            'id', // Foreign key on the returned table...
            'id', // Local key on this class table...
            'owning_hook_id' // Local key on the connecting table...
        );
    }
    public function thing_children() : HasMany {
        return $this->hasMany(Thing::class,'parent_thing_id','id');
    }

    public function thing_parent() : BelongsTo {
        return $this->belongsTo(Thing::class,'parent_thing_id','id');
    }

    public function thing_root() : BelongsTo {
        return $this->belongsTo(Thing::class,'root_thing_id','id');
    }


    public function thing_error() : BelongsTo {
        return $this->belongsTo(ThingError::class,'thing_error_id','id');
    }



    public function applied_callbacks() : HasMany {
        return $this->hasMany(ThingCallback::class,'source_thing_id','id');
    }






    public function isComplete() : bool {
        return in_array($this->thing_status,TypeOfThingStatus::STATUSES_OF_COMPLETION);
    }

    public function getCurrentSharedCallbackFromDescendant(ThingHook $hook)
    : ?ThingCallback
    {
        $query_self_descandants = DB::table("things as desc_a")
            ->selectRaw('desc_a.id, 0 as level')->where('desc_a.id', $this->id)
            ->unionAll(
                DB::table('things as desc_b')
                    ->selectRaw('desc_b.id, level + 1 as level')
                    ->join('thing_self_descendants', 'thing_self_descendants.id', '=', 'desc_b.parent_thing_id')
            );

        /** @noinspection PhpUndefinedMethodInspection */
        $query_shared_callback = DB::table("things as node_a")
            ->selectRaw("node_a.id, thing_self_descendants.level, thing_self_descendants.max_priority,thing_callbacks.id as callback_id")
            ->where('node_a.id', $this->id)


            ->join('thing_callbacks', 'thing_callbacks.owning_hook_id', '=', 'node_a.id')
            ->join('thing_hooks', 'thing_descendants.id', '=', 'node_a.id')

            ->join('thing_self_descendants', 'thing_self_descendants.id', '=', 'node_a.id')
            ->join('thing_callbacks as shared',
                /** @param JoinClause $join */
                function ($join) use($hook) {
                    $join
                        ->on('shared.source_thing_id','=','node_a.id')
                        ->where('shared.owning_hook_id',$hook->id)
                        ->whereIn('shared.thing_callback_status',[TypeOfCallbackStatus::CALLBACK_ERROR,TypeOfCallbackStatus::CALLBACK_SUCCESSFUL])
                        ->whereRaw('(shared.callback_run_at + make_interval(secs => ?) ) <= NOW()',[$hook->ttl_shared])
                    ;
                }
            ) ->orderBy('level','desc')->limit(1)
            ->withRecursiveExpression('thing_self_descendants',$query_self_descandants);

        /** @noinspection PhpUndefinedMethodInspection */
        return ThingCallback::where('thing_callbacks.id','callback_id')
            ->join('shared_callbacks','shared_callbacks.callback_id')
            ->withExpression('shared_callbacks',$query_shared_callback)
            ->first();
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

        /** @noinspection PhpUndefinedMethodInspection */
        $query_nodes = DB::table("things as node_a")
            ->selectRaw("node_a.id, thing_descendants.level, thing_descendants.max_priority")
            ->where('node_a.id', $this->id)
            ->where('node_a.thing_status', TypeOfThingStatus::THING_BUILDING)
            ->whereRaw("(node_a.thing_start_after IS NULL OR node_a.thing_start_after <= NOW() )")
            ->whereRaw("(node_a.thing_invalid_after IS NULL OR node_a.thing_invalid_after < NOW())")

            ->join('thing_descendants', 'thing_descendants.id', '=', 'node_a.id')
            ->unionAll(
                DB::table('things as node_b')
                    ->selectRaw('node_b.id,thing_descendants.level as level,thing_descendants.max_priority')

                    ->join('thing_nodes', 'thing_nodes.id', '=', 'node_b.parent_thing_id')
                    ->join('thing_descendants', 'thing_descendants.id', '=', 'node_b.id')
                    ->whereRaw("node_b.thing_priority = thing_descendants.max_priority")
                    ->where('node_b.thing_status', TypeOfThingStatus::THING_BUILDING)
                    ->whereRaw("(node_b.thing_start_after IS NULL OR node_b.thing_start_after <= NOW() )")
                    ->whereRaw("(node_b.thing_invalid_after IS NULL OR node_b.thing_invalid_after < NOW())")

            )->withRecursiveExpression('thing_descendants',$query_descendants);

        /** @noinspection PhpUndefinedMethodInspection */
        $query_term = DB::table("things as term")
            ->distinct()
            ->selectRaw("term.id, term.thing_priority, max(term.thing_priority) OVER () as max_thinger")
            ->join('thing_nodes', 'thing_nodes.id', '=', 'term.id')
            ->leftJoin('things as y', 'y.parent_thing_id', '=', 'term.id')
                /** @param Builder $q  */
            ->where(function ( $q){
                $q  ->whereNotIn('y.thing_status',[
                    //children must be completed or be leaves
                    TypeOfThingStatus::THING_BUILDING,TypeOfThingStatus::THING_PENDING,TypeOfThingStatus::THING_WAITING,TypeOfThingStatus::THING_RUNNING])
                    ->orWhereNull('y.id');
            })

            ->withRecursiveExpression('thing_nodes',$query_nodes)
            ;

        $lar =  Thing::select('things.*')
            ->selectRaw("extract(epoch from  things.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  things.updated_at) as updated_at_ts")
            ->selectRaw("extract(epoch from  things.thing_start_after) as thing_start_at_ts")
            ->selectRaw("extract(epoch from  things.thing_invalid_after) as thing_invalid_at_ts")
            ->withExpression('terminal_list',$query_term)
            ->join('terminal_list', 'terminal_list.id', '=', 'things.id')
            ->whereRaw("things.thing_priority = terminal_list.max_thinger")
            ->where('things.thing_status',TypeOfThingStatus::THING_BUILDING)
            ;

        /** @var \Illuminate\Database\Eloquent\Collection|Thing[] */
        return $lar->get();

    }

    public function markIncompleteDescendantsAs(TypeOfThingStatus $status) {
        static::buildThing(me_id: $this->id,include_my_descendants: true)
            ->where('things.id','<>',$this->id) //do not mark oneself
            ->whereNotIn('thing_status',TypeOfThingStatus::STATUSES_OF_COMPLETION)
            ->update(['thing_status'=>$status]);
    }


    protected function setStartData(bool $signal_when_done) {
        $this->update([
            'thing_status' => TypeOfThingStatus::THING_PENDING,
            'is_signalling_when_done'=>$signal_when_done,
            'thing_started_at'=>DB::raw("NOW()")
        ]);
    }

    protected function setRunData(TypeOfThingStatus $status) {
        $this->update([
            'thing_status' => $status,
            'thing_ran_at'=>DB::raw("NOW()"),
        ]);
    }

    public function setException(Exception $e) {
        $hex = ThingError::createFromException($e);
        $this->update([
            'thing_status' => TypeOfThingStatus::THING_ERROR,
            'thing_ran_at'=>DB::raw("NOW()"),
            'thing_error_id'=>$hex?->id??null,
        ]);
    }


    /**
     * @throws Exception
     */
    public function runThing() :void {
        if ($this->thing_status !== TypeOfThingStatus::THING_PENDING) {
           throw new HbcThingException("Non building thing has started a run : #". $this->id);
        }
        $this->thing_status = TypeOfThingStatus::THING_RUNNING;
        $this->save();
        /** @var IThingAction|null $action */

        try {
            DB::beginTransaction();


            $status = null;
            $action = $this->getAction();
            $action->runAction();
            if($this->thing_invalid_after && Carbon::parse($this->thing_start_after)->isAfter($this->thing_invalid_after)) {
                $status  = TypeOfThingStatus::THING_INVALID;
            }
            else if ($action->isActionComplete())
            {
                if ($action->isActionSuccess()) {
                    $status  = TypeOfThingStatus::THING_SUCCESS;
                } else if ($action->isActionFail()) {
                    $status  = TypeOfThingStatus::THING_FAIL;
                } else {
                    $status  = TypeOfThingStatus::THING_ERROR;
                }
            } else {
                $status  = TypeOfThingStatus::THING_WAITING;
            }

            if (in_array($status,TypeOfThingStatus::STATUSES_OF_COMPLETION)) {
                $this->setRunData(status: $status);
                if ($this->is_signalling_when_done) {
                    //it is done, for better or worse
                    $this->signal_parent();
                }
            } //else the thing will have to be resumed later with continueThing by the outside

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->setException($e);
            throw $e;
        }

    }

    /**
     * @throws Exception
     */
    public function signal_parent() {
        if ($this->parent_thing_id) {
            $action = $this->getAction();
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
            }
            //see if all children ran, if so, put the parent on the processing
            $this->maybeQueueMore();
        }
    }

    /**
     * If have children, if any non-pending have not finished, return
     * if only remaining children are pending, then push leaves
     * else push leaves of grandparent (starts )
     * @throws Exception
     */
    protected function maybeQueueMore() : bool {

        $count_waiting_children = 0;
        foreach ($this->thing_children as $thang) {
            if ($thang->thing_status === TypeOfThingStatus::THING_WAITING) { $count_waiting_children++; }
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
     * @throws Exception
     */
    protected function pushLeavesToJobs() :void {
        foreach ($this->getLeaves() as $leaf) {
            $leaf->dispatchThing();
        }
    }

    /**
     * @return SendCallback[]
     */
    protected function getCallbacksOfType(TypeOfHookMode $which,?bool $blocking = null,?bool $after = null,&$unresolved_manual = []) : array {

        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook( mode:$which,action: $this->getAction(), owner: $this->getOwner(),
            tags: $this->thing_tags->getArrayCopy(),is_on: true,is_after: $after,is_blocking: $blocking)->get();

        $callbacks = [];
        foreach ($hooks as $hook) {
            if ($hook->is_manual && $blocking && !$after) { //we only do manual for pre blocking
                //see if filled in for thing already
                /**
                 * @var ThingCallback[] $maybe_existing
                 */
                $maybe_existing = ThingCallback::buildCallback(hook_id: $hook->id, thing_id: $this->id)->get();
                /*
                 * if none existing, create one or two
                 */
                if (count($maybe_existing) === 0) {
                    if ($hook->address) {
                        $jump_start = ThingCallback::createCallback(hook: $hook, thing: $this);
                        $unresolved_manual[] = $jump_start->createEmptyManual();
                        SendCallback::dispatch($jump_start)->afterCommit();
                    }
                } elseif (count($maybe_existing) === 1) {
                    $manual = $maybe_existing[0];
                    if ($hook->address && $manual->manual_alert_callback_id && !$manual->isCompleted()) {
                        //hook was edited since callback created, now has address, send notification because manual not completed yet
                        $jump_start = ThingCallback::createCallback(hook: $hook, thing: $this);
                        $unresolved_manual[] = $manual;
                        SendCallback::dispatch($jump_start)->afterCommit();
                    } else if ($manual->manual_alert_callback_id && !$manual->isCompleted()) {
                        $unresolved_manual[] = $manual;
                    } else if ($hook->address && !$manual->manual_alert_callback_id) {
                        //notice was sent out, but no manual created
                        $unresolved_manual[] = $manual->createEmptyManual();
                    } else if (!$hook->address && $manual->manual_alert_callback_id && !$manual->isCompleted()) {
                        //waiting on manual
                        $unresolved_manual[] = $manual;
                    } else {
                        $callbacks[] = $manual;
                    }
                } elseif (count($maybe_existing) >= 2) {
                    foreach ($maybe_existing as $maybe) {
                        if ($maybe->manual_alert_callback_id && !$maybe->isCompleted()) {
                            $unresolved_manual[] = $maybe;
                        }
                        if ($maybe->manual_alert_callback_id && $maybe->isCompleted()) {
                            $callbacks[] = $maybe;
                        }
                    }
                }
            }
        } //end for each hook

        if (count($unresolved_manual)) {
            //do not create others yet
            return [];
        }

        foreach ($hooks as $hook) {
            if ($hook->is_manual) { continue; } //manual already done
            $callbacks[] = ThingCallback::createCallback(hook: $hook,thing: $this);
        }



        //sort highest priority first
        usort($callbacks,function (ThingCallback $a,ThingCallback $b) {
            return -($a->owning_hook->hook_priority <=> $b->owning_hook->hook_priority) ;
        });

        $ret = [];
        foreach ($callbacks as $call) {
            $ret[] = new  SendCallback(callback:$call);
        }
        return $ret;
    }

    /**
     * @return SendCallback[]
     */
    public static function getCallbacks(string $ref,TypeOfHookMode $which,?bool $blocking = null,?bool $after = null) {
        /** @var Thing|null $thung */
        $thung = Thing::where('ref_uuid',$ref)->first();
        if (!$thung) {
            Log::warning("could not get thing by uuid of $ref");
            return [];
        }
        return $thung->getCallbacksOfType(which: $which,blocking: $blocking,after: $after);
    }

    /**
     * @throws Exception
     */
    protected function dispatchThing() {


        if ( $this->thing_invalid_after && Carbon::now('UTC')->greaterThanOrEqualTo(Carbon::parse($this->thing_invalid_after,'UTC')) ) {
            $this->setRunData(status: TypeOfThingStatus::THING_FAIL);
            $this->signal_parent();
            return;
        }

        if ( $this->thing_start_after && Carbon::now('UTC')->lessThan(Carbon::parse($this->thing_start_after,'UTC')) ) {
            $this->setRunData(status: TypeOfThingStatus::THING_INVALID);
            $this->signal_parent();
            return;
        }


        $unresolved_manual = [];
        $blocking_pre = $this->getCallbacksOfType(which: TypeOfHookMode::NODE,blocking: true,after: false,unresolved_manual: $unresolved_manual);
        if (count($unresolved_manual)) {
            $this->setRunData(status: TypeOfThingStatus::THING_WAITING);
            return; //not until they are resolved
        }
        $blocking_post = $this->getCallbacksOfType(which: TypeOfHookMode::NODE,blocking: false,after: true);
        if (count($blocking_post)) {
            $blocking_post[count($blocking_post)-1]->getCallback()->setSignalWhenDone();
        }

        $this->setStartData(signal_when_done:  !count($blocking_post)); //combine with status change here in the update
        $blocking = array_merge($blocking_pre,[new RunThing(thing: $this)],$blocking_post);
        $non_blocking_pre = $this->getCallbacksOfType(which: TypeOfHookMode::NODE,blocking: false,after: false);

        $chaining = [];
        $chaining[] = Bus::batch(
            $blocking
        )
            ->before(function (Batch $batch) {

                Log::debug(sprintf("before handler for %s |  %s",$batch->id,$batch->name));

            })->progress(function (Batch $batch) {

                Log::notice("job in batch completed , pending left ".$batch->pendingJobs);

            })->then(function (Batch $batch)  {
                Log::debug(sprintf("success handler for %s |  %s",$batch->id,$batch->name));
                $success = Thing::getCallbacks(ref: $batch->name,which: TypeOfHookMode::NODE_SUCCESS);
                if (count($success)) {
                    Bus::batch($success)->onConnection('default');
                }

            })->catch(function (Batch $batch, \Throwable $e){

                Log::warning(sprintf("failed job for %s |  %s \n",$batch->id,$batch->name).$e);
                $fail = Thing::getCallbacks(ref: $batch->name,which: TypeOfHookMode::NODE_FAILURE);

                if (count($fail)) {
                    Bus::batch($fail)->onConnection('default');
                }

            })->finally(function (Batch $batch)  {
                Log::debug(sprintf("Finally handler for %s |  %s",$batch->id,$batch->name));
                $always = Thing::getCallbacks(ref: $batch->name,which: TypeOfHookMode::NODE_FAILURE);
                if (count($always)) {
                    Bus::batch($always)->onConnection('default');
                }
                $non_blocking_post = Thing::getCallbacks(ref: $batch->name,which: TypeOfHookMode::NODE,blocking: false,after: true);
                if (count($non_blocking_post)) {
                    Bus::batch($non_blocking_post)->onConnection('default');
                }
            })->onConnection($this->is_async?'default':'sync')
            ->name($this->ref_uuid);

        if (count($non_blocking_pre)) {
            $chaining[] = $non_blocking_pre;
        }

        Bus::chain($chaining)
            ->onConnection($this->is_async?'default':'sync')
            ->dispatch();

    }


    /**
     * @throws Exception
     */
    public static function buildFromAction(IThingAction $action, IThingOwner $owner, array $extra_tags = [])
    : Thing
    {

        try {
            DB::beginTransaction();
            $root = static::makeThingTree(action: $action, extra_tags: $extra_tags,owner: $owner);

            $root->pushLeavesToJobs();
            DB::commit();
            return $root;
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
            $root = static::makeThingFromAction(parent_thing: null, action: $action,extra_tags: $extra_tags,owner: $owner);

            $tree = $action->getChildrenTree(key: $hint);
            $roots = $tree->getRootNodes();
            foreach ($roots as $a_node) {
                static::makeTreeNodes(parent_thing: $root, node: $a_node);
            }

            DB::commit();
            return static::getThing(id: $root->id);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    /**
     * @throws Exception
     */
    protected static function makeThingFromAction(?Thing $parent_thing,IThingAction $action,array $extra_tags = [],
                                                  IThingOwner $owner = null)
    : Thing
    {
        if (!$owner) {
            $owner = $parent_thing?->getOwner();
            if (!$owner) {
                $owner = $action->getActionOwner();
            }
        }

        $root_tags = ($parent_thing?->thing_root?:$parent_thing)?->thing_tags?->getArrayCopy()??[];
        $thing_tags = array_unique(array_merge($action->getActionTags()??[],$root_tags,$extra_tags));
        if (empty($thing_tags) ) {
            $thing_tags = [];
        }

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
                $tree_node->thing_start_after = Carbon::parse($start_at)->timezone('UTC')->toDateTimeString();
            } else {
                $tree_node->thing_start_after = null; //children can start earlier if not defined
            }
            if ($invalid_at = $action->getInvalidAt()) {
                $tree_node->thing_invalid_after = Carbon::parse($invalid_at)->timezone('UTC')->toDateTimeString();
            } else {
                $tree_node->thing_invalid_after = $parent_thing?->thing_invalid_after ?? null;
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
            $tree_node->thing_tags = $thing_tags;
            $tree_node->save();
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

        $tree_node = static::makeThingFromAction(parent_thing: $parent_thing,action: $the_action);

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
        ?string    $ref_uuid = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
        ?int    $owner_type_id = null,
        ?string $owner_type = null,
        ?bool   $is_root = null,
        bool    $include_my_descendants = false,
        bool    $eager_load = false,
        array   $owners = [],
        ?array   $tags = null
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  Thing::select('things.*')
            ->selectRaw(" extract(epoch from  things.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  things.updated_at) as updated_at_ts")
            ->selectRaw("extract(epoch from  things.thing_start_after) as thing_start_at_ts")
            ->selectRaw("extract(epoch from  things.thing_invalid_after) as thing_invalid_at_ts")
        ;

        if ($me_id) {
            $build->where('things.id',$me_id);
        }

        if ($ref_uuid) {
            $build->where('things.ref_uuid',$ref_uuid);
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
            /** @noinspection PhpUndefinedMethodInspection */
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

        if (count($owners)) {
            $build->where(function (Builder $q) use($owners) {
                foreach ($owners as $some_owner) {
                    $q->orWhere(function (Builder $q) use($some_owner) {
                        $q->where('things.owner_type',$some_owner->getOwnerType());
                        $q->where('things.owner_type_id',$some_owner->getOwnerId());
                    });
                }
            });
        }

        if ($tags !== null ) {
            if (count($tags) ) {
                $tags_json = json_encode(array_values($tags));
                $build->whereRaw("array(select jsonb_array_elements(things.thing_tags) ) && array(select jsonb_array_elements(?) )", $tags_json);
            } else {
                $build->whereRaw("jsonb_array_length(things.thing_tags) is null OR jsonb_array_length(things.thing_tags) = 0");
            }
        }

        if ($eager_load) {
            /**
             * @uses Thing::thing_parent(),Thing::thing_children(),Thing::thing_error(),
             * @uses Thing::thing_root(), Thing::applied_callbacks()
             */
            $build->with('thing_parent', 'thing_children', 'thing_error',
                'thing_root');
        }

        return $build;
    }



    public function resolveRouteBinding($value, $field = null)
    {
        $ret = null;
        if ( Uuid::isValid($value)) {
            $ret = static::buildThing(ref_uuid: (string)$value)->first();
        }
        if (!$ret) {
            throw new HbcThingException("could not find thing with uuid of $value");
        }
        return $ret;
    }

    /**
     * @throws Exception
     */
    public function continueThing() {
        if ($this->thing_status !== TypeOfThingStatus::THING_WAITING) {return;}
        $this->thing_status = TypeOfThingStatus::THING_PENDING;
        $this->save();
        $this->dispatchThing();
    }



}
