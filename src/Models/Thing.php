<?php

namespace Hexbatch\Things\Models;




use App\Helpers\Utilities;
use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfOwnerGroup;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Jobs\RunThing;
use Hexbatch\Things\Jobs\SendCallback;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Hexbatch\Things\OpenApi\Things\ThingSearchParams;
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
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property bool is_async
 *
 *
 * @property string thing_start_after
 * @property string thing_invalid_after
 * @property string thing_started_at
 * @property string thing_ran_at
 * @property string thing_wait_until_at
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
        'action_type',
        'action_type_id',
        'thing_start_after',
        'thing_started_at',
        'thing_invalid_after',
        'thing_ran_at',
        'thing_wait_until_at',
        'thing_status',
    ];

    /** @var array<int, string> */
    protected $hidden = [];

    /* @var array<string, string> */
    protected $casts = [
        'thing_status' => TypeOfThingStatus::class,
        'thing_tags' => AsArrayObject::class,
        'is_async' => 'boolean',
    ];

    const int MINIMUM_WAIT_TIME = 5*60; //five minutes


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


    /** @noinspection PhpUnused */
    public function isSuccess() : bool {
        return $this->thing_status === TypeOfThingStatus::THING_SUCCESS;
    }

    /** @noinspection PhpUnused */
    public function isFailedOrError() : bool {
        return in_array($this->thing_status,[TypeOfThingStatus::THING_ERROR,TypeOfThingStatus::THING_FAIL]);
    }

    public function isWaiting() : bool {
        return $this->thing_status === TypeOfThingStatus::THING_WAITING;
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
            ->selectRaw("node_a.id, thing_self_descendants.level, shared.id as callback_id")
            ->join('thing_self_descendants', 'thing_self_descendants.id', '=', 'node_a.id')
            ->join('thing_callbacks as shared',
                /** @param JoinClause $join */
                function ($join) use($hook) {
                    $join
                        ->on('shared.source_thing_id','=','node_a.id')
                        ->where('shared.owning_hook_id',$hook->id)
                        ->whereIn('shared.thing_callback_status',[TypeOfCallbackStatus::CALLBACK_ERROR,TypeOfCallbackStatus::CALLBACK_SUCCESSFUL])
                        ->whereRaw('(shared.callback_run_at + make_interval(secs => ?) ) >= NOW()',[$hook->ttl_shared])
                    ;
                }
            ) ->orderBy('level','desc')->limit(1)
            ->withRecursiveExpression('thing_self_descendants',$query_self_descandants);

        /** @noinspection PhpUndefinedMethodInspection */
        $laravel =  ThingCallback::select('thing_callbacks.*')->whereRaw('thing_callbacks.id = shared_callbacks.callback_id')
            ->join('shared_callbacks','shared_callbacks.callback_id','thing_callbacks.id')
            ->withExpression('shared_callbacks',$query_shared_callback)
            ;
        /** @type ThingCallback|null */
        return $laravel->first();
    }

    /** @return \Illuminate\Database\Eloquent\Collection|Thing[] */

    public function getLeaves() {

        $query_descendants = DB::table("things as desc_a")
            ->selectRaw('desc_a.id, 0 as level')->where('desc_a.id', $this->id)
            ->unionAll(
                DB::table('things as desc_b')
                    ->selectRaw('desc_b.id, level + 1 as level')
                    ->join('thing_descendants', 'thing_descendants.id', '=', 'desc_b.parent_thing_id')
            );


        $incomplete_children = DB::table("things as par")
            ->distinct()
            ->selectRaw('par.id as maybe_parent_id, count(waiting_child_things.id) OVER (PARTITION BY waiting_child_things.parent_thing_id) as number_incomplete_children')
            ->leftJoin("things as waiting_child_things",
                /**
                 * @param JoinClause $join
                 */
                function (JoinClause $join)  {
                    $join
                        ->on("waiting_child_things.parent_thing_id",'=',"par.id")
                        ->whereIn('waiting_child_things.thing_status',TypeOfThingStatus::INCOMPLETE_STATUSES);
                }
            )
           ;


        $lar =  Thing::select('things.*')

            ->selectRaw("extract(epoch from  things.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  things.updated_at) as updated_at_ts")
            ->selectRaw("extract(epoch from  things.thing_start_after) as thing_start_at_ts")
            ->selectRaw("extract(epoch from  things.thing_invalid_after) as thing_invalid_at_ts")
            ->withRecursiveExpression('thing_descendants',$query_descendants)
            ->withExpression('incomplete_children',$incomplete_children)
            ->join('thing_descendants', 'thing_descendants.id', '=', 'things.id')
            ->join('incomplete_children', 'incomplete_children.maybe_parent_id', '=', 'things.id')
            ->whereRaw("incomplete_children.number_incomplete_children = 0")
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


    protected function setStartData() {
        $this->update([
            'thing_status' => TypeOfThingStatus::THING_PENDING,
            'thing_started_at'=>DB::raw("NOW()")
        ]);
    }

    public function setRunData(TypeOfThingStatus $status,?int $wait_seconds = null) {

        $what = [
            'thing_status' => $status,
            'thing_ran_at'=>DB::raw("NOW()"),
        ];

        if (( $status === TypeOfThingStatus::THING_WAITING) &&  ( (null !== $wait_seconds) && $wait_seconds >=0 ) ) {
            $what['thing_wait_until_at'] = DB::raw("NOW() + interval '$wait_seconds seconds'");
        }
        $this->update($what);
    }

    public function setException(Exception $e,TypeOfThingStatus $status = TypeOfThingStatus::THING_ERROR) {
        $hex = ThingError::createFromException(exception: $e,related_tags: $this->thing_tags->getArrayCopy());
        $this->update([
            'thing_status' => $status ,
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

            if($this->thing_invalid_after && Carbon::parse($this->thing_start_after)->isAfter($this->thing_invalid_after)) {
                $status  = TypeOfThingStatus::THING_INVALID;
            } else {

                $action = $this->getAction();
                $action->runAction();
                if ($this->thing_parent) {

                    $more_actions = $action->getMoreSiblingActions();
                    foreach ($more_actions as $extra_action) {
                        Thing::makeThingTree(action: $extra_action,parent: $this->thing_parent);
                    }
                }
                if ($action->isActionComplete()) {
                    if ($action->isActionSuccess()) {
                        $status = TypeOfThingStatus::THING_SUCCESS;
                    } else if ($action->isActionError()) {
                        $status = TypeOfThingStatus::THING_ERROR;
                    } else if ($action->isActionFail()) {
                        $status = TypeOfThingStatus::THING_FAIL;
                    } else {
                        $status = TypeOfThingStatus::THING_ERROR;
                    }
                } else {
                    $status = TypeOfThingStatus::THING_WAITING;
                }
            }
        } catch (Exception $e) {
            $status = TypeOfThingStatus::THING_ERROR;
            if ($action?->isActionFail()) {
                $status = TypeOfThingStatus::THING_FAIL;
            }
            $this->setException(e: $e,status: $status);
            throw $e;
        }

        $this->setRunData(status: $status,wait_seconds: $action->getWaitTimeout());
        if (in_array($status,TypeOfThingStatus::STATUSES_OF_COMPLETION)) {
            $this->signal_parent();
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

        }
    }


    /**
     * leaf must run async if any of its ancestors are async, found in stats for it
     * @throws Exception
     */
    public function pushLeavesToJobs() :void {
        $root = $this->thing_root;
        if (!$root) { $root = $this;}
        foreach ($root->getLeaves() as $leaf) {
            $leaf->dispatchThing();
        }
    }

    /**
     * @return SendCallback[]
     */
    public function getCallbacksOfType(TypeOfHookMode $which,?bool $blocking = null,?bool $after = null,&$unresolved_manual = []) : array {

        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook( mode:$which,
            action_type: $this->getAction()?->getActionType(), maybe_action_id: $this->getAction()?->getActionId(),
            hook_owner_group: $this->getOwner(),
            hook_group_hint: TypeOfOwnerGroup::HOOK_CALLBACK_CREATION,
            tags: $this->thing_tags->getArrayCopy(),is_on: true,is_after: $after,is_blocking: $blocking)->get();

        $callbacks = [];
        foreach ($hooks as $hook) {
            if ($hook->is_manual  ) { //we only do manual for pre blocking

                if ($hook->is_sharing) {continue;}
                if (!$blocking) {continue;}
                if ($after) {continue;}
                if ($which !== TypeOfHookMode::NODE) {continue;}
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
                        if (!$maybe->manual_alert_callback_id) { continue;}
                        if ($maybe->isCompleted()) {
                            $callbacks[] = $maybe;
                        } else {
                            $unresolved_manual[] = $maybe;
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
     * @throws Exception
     */
    protected function dispatchThing() {

        if($this->thing_status !== TypeOfThingStatus::THING_BUILDING) {return;} //its already handled

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
        $blocking_post = $this->getCallbacksOfType(which: TypeOfHookMode::NODE,blocking: true,after: true);


        $this->setStartData();

        if (!$this->is_async) {
            $this->refresh();
        }

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
                try {
                    $thing = Thing::getThing(ref_uuid: $batch->name);
                    $success = $thing->getCallbacksOfType(which: TypeOfHookMode::NODE_SUCCESS);
                    if (count($success)) {
                        Log::debug(sprintf("success handler found %s callbacks to run", count($success)));
                        Bus::batch($success)->onConnection($thing->is_async ? config('hbc-things.queues.default_connection') : 'sync')->dispatch();
                    }
                } catch (Exception|\Error $e) {
                    Log::error("then had issue: \n". $e);
                }

            })->catch(function (Batch $batch, \Throwable $e){


                try {
                    $thing = Thing::getThing(ref_uuid: $batch->name);
                    if ($thing->isWaiting()) {
                        Log::debug(sprintf("waiting thing for %s |  %s \n",$batch->id,$batch->name).$e);
                    } else {
                        Log::warning(sprintf("failed job for %s |  %s \n",$batch->id,$batch->name).$e);
                        $fails = $thing->getCallbacksOfType(which: TypeOfHookMode::NODE_FAILURE);

                        if (count($fails)) {
                            Log::debug(sprintf("failed handler found %s callbacks to run", count($fails)));
                            Bus::batch($fails)->onConnection($thing->is_async ? config('hbc-things.queues.default_connection') : 'sync')->dispatch();
                        }
                    }

                } catch (Exception|\Error $e) {
                    Log::error("batch catch had issue: \n". $e);
                }

            })->finally(function (Batch $batch)  {
                Log::debug(sprintf("Finally handler for %s |  %s",$batch->id,$batch->name));
                try {
                    $thing = Thing::getThing(ref_uuid: $batch->name);


                    $always = $thing->getCallbacksOfType(which: TypeOfHookMode::NODE_FINALLY);
                    if (count($always)) {
                        Log::debug(sprintf("finally handler found %s callbacks to run", count($always)));
                        Bus::batch($always)->onConnection($thing->is_async ? config('hbc-things.queues.default_connection') : 'sync')->dispatch();
                    } else {
                        Log::debug(("finally handler found no callbacks for thing id ".$thing->id));
                    }

                    if ($thing->is_async && in_array($thing->thing_status,TypeOfThingStatus::STATUSES_OF_COMPLETION)) {
                        $thing->pushLeavesToJobs();
                    }

                } catch (Exception|\Error $e) {
                    Log::error("Finally had issue: \n". $e);
                }
            })->onConnection($this->is_async?config('hbc-things.queues.default_connection'):'sync')
            ->name($this->ref_uuid);

        foreach ($non_blocking_pre as $pre_what) {
            $chaining[] = $pre_what;
        }

        Bus::chain($chaining)
            ->onConnection($this->is_async?config('hbc-things.queues.default_connection'):'sync')
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
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }

        $root->pushLeavesToJobs();
        if (!$root->is_async) {
            $counter = 0;
            while(!$root->isComplete() && $counter < 12) {
                $root->refresh();
                $counter++;
                $root->pushLeavesToJobs();
            }
        }
        return $root;
    }


    /**
     * @throws Exception
     */
    protected static function makeThingTree(
        IThingAction $action,
        array $extra_tags = [],
        IThingOwner $owner = null,
        ?Thing $parent = null
    )
    : Thing {
        $owner = $owner? : $action->getActionOwner();
        try {

            $root = static::makeThingFromAction(parent_thing: $parent, action: $action,extra_tags: $extra_tags,owner: $owner);
            if (!$root->isWaiting()) {
                $tree = $action->getChildrenTree();
                $roots = $tree?->getRootNodes()??[];
                foreach ($roots as $a_node) {
                    static::makeTreeNodes(parent_thing: $root, node: $a_node);
                }
            }


            return static::getThing(id: $root->id);
        } catch (Exception $e) {
            Utilities::ignoreVar($e);
            throw $e;
        }
    }


    /**
     * @throws Exception
     */
    protected static function makeThingFromAction(?Thing $parent_thing,IThingAction $action,array $extra_tags = [],
                                                  TypeOfThingStatus $initial_status = TypeOfThingStatus::THING_BUILDING,
                                                  IThingOwner $owner = null)
    : Thing
    {
        if (!$owner) {
            $owner = $parent_thing?->getOwner();
            if (!$owner) {
                $owner = $action->getActionOwner();
            }
        }

        $root_action_tags = [];
        if ($parent_thing) {
            if ($parent_thing->thing_root) {
                $root_action_tags = $parent_thing->thing_root->getAction()?->getActionTags()??[];
            } else {
                $root_action_tags = $parent_thing->getAction()?->getActionTags()??[];
            }
        }


        $root_tags = array_values( ($parent_thing?->thing_root?:$parent_thing)?->thing_tags?->getArrayCopy()??[]);
        $root_tags_without_action_stuff = array_diff($root_tags,$root_action_tags);
        $thing_tags = array_values(array_unique(array_merge($action->getActionTags()??[],$root_tags_without_action_stuff,array_values($extra_tags))));
        if (empty($thing_tags) ) {
            $thing_tags = [];
        }


        if ($parent_thing?->is_async) {
            $async = true;
        } else {
            $async = $action->isAsync();
        }



        try {
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
            $tree_node->thing_status = $initial_status;
            $tree_node->action_type = $action->getActionType();
            $tree_node->action_type_id = $action->getActionId();
            $tree_node->owner_type = $owner?->getOwnerType();
            $tree_node->owner_type_id = $owner?->getOwnerId();
            $tree_node->is_async = $async;
            $tree_node->thing_tags = $thing_tags;
            $tree_node->save();
            return static::getThing(id: $tree_node->id);
        } catch (Exception $e) {
            Utilities::ignoreVar($e);
            throw $e;
        }
    }


    /**
     * @throws Exception
     */
    protected static function makeTreeNodes(Thing $parent_thing, \BlueM\Tree\Node $node) : Thing {

        /** @var IThingAction $the_action */
        $the_action = $node->action;


        $initial_status = TypeOfThingStatus::THING_BUILDING;
        if (isset($node->is_waiting) && $node->is_waiting) {
            $initial_status = TypeOfThingStatus::THING_WAITING;
        }

        $extra_tags = [];
        if (isset($node->extra_tags)) {
            if (is_array($node->extra_tags)) { $extra_tags = $node->extra_tags;}
            else if (is_object($node->extra_tags) || is_bool($node->extra_tags) || is_int($node->extra_tags) || is_float($node->extra_tags)) {
                $extra_tags = [(string)$node->extra_tags];}
            else { $extra_tags = [$node->extra_tags]; }
        }
        if (isset($node->title)) {
            $extra_tags[] = (string) $node->title;
        }

        $children = [];
        if ($initial_status !== TypeOfThingStatus::THING_WAITING) {
            $first_children = $node->getChildren();
            $also_this_tree = $the_action->getChildrenTree()?->getRootNodes() ?? [];
            $children = array_merge($first_children, $also_this_tree);
            //can be duplicates
            array_filter($children, function (\BlueM\Tree\Node $node_a) {
                $a = $node_a->action ?? null;
                if (!$a) {
                    return false;
                }
                static $mems = [];
                if (array_key_exists($a->getActionUuid(), $mems)) {
                    return false;
                }
                $mems[$a->getActionUuid()] = $a;
                return true;
            });
        }
        $tree_node = static::makeThingFromAction(parent_thing: $parent_thing, action: $the_action, extra_tags: $extra_tags, initial_status: $initial_status);

        foreach ( $children as $child) {
            static::makeTreeNodes(parent_thing: $tree_node,node: $child);
        }

        return $tree_node;
    }


    public static function getThing(
        ?int $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
        ?string $ref_uuid = null,
    )
    : Thing
    {
        $ret = static::buildThing(me_id: $id, ref_uuid: $ref_uuid, action_type_id: $action_type_id, action_type: $action_type)->first();

        if (!$ret) {
            $arg_types = [];
            $arg_vals = [];
            if ($id) { $arg_types[] = 'id'; $arg_vals[] = $id;}
            if ($action_type) { $arg_types[] = 'Action type'; $arg_vals[] = $action_type;}
            if ($action_type_id) { $arg_types[] = 'Action id'; $arg_vals[] = $action_type_id;}
            if ($ref_uuid) { $arg_types[] = 'Ref uuid'; $arg_vals[] = $ref_uuid;}
            $arg_val = implode('|',$arg_vals);
            $arg_type = implode('|',$arg_types);
            throw new \InvalidArgumentException("Could not find thing via $arg_type : $arg_val");
        }
        return $ret;
    }

    public static function buildThing(
        ?int               $me_id = null,
        ?string            $ref_uuid = null,
        ?int               $action_type_id = null,
        ?string            $action_type = null,
        ?int               $owner_type_id = null,
        ?string            $owner_type = null,
        ?bool              $is_root = null,
        bool               $include_my_descendants = false,
        bool               $eager_load = false,
        ?IThingOwner       $owner_group = null,
        ?TypeOfOwnerGroup  $group_hint = null,
        ?array             $tags = null,
        ?ThingSearchParams $params = null
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

        if ($ref_uuid || $params?->getUuid()) {
            $build->where('things.ref_uuid',$ref_uuid?: $params->getUuid());
        }

        if ($action_type || $params?->getActionType() ) {
            $build->where('things.action_type',$action_type?: $params->getActionType());
        }

        if ($action_type_id || $params?->getActionId()) {
            $build->where('things.action_type_id',$action_type_id?: $params->getActionId());
        }

        if ($owner_type || $params?->getOwnerType() ) {
            $build->where('things.owner_type',$owner_type?: $params->getOwnerType());
        }

        if ($owner_type_id || $params?->getOwnerId()) {
            $build->where('things.owner_type_id',$owner_type_id?: $params->getOwnerId());
        }

        if ($is_root !== null || ($params?->getIsRoot() !== null)) {
            if($is_root || $params?->getIsRoot()) {
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

        if($group_hint) {
            $owner_group?->setReadGroupBuilding(builder: $build, connecting_table_name: 'things',
                connecting_owner_type_column: 'owner_type', connecting_owner_id_column: 'owner_type_id', hint: $group_hint);
        }

        if ($tags !== null || $params?->getTags()) {
            $use_tags = $tags?: $params->getTags();
            if (count($use_tags) ) {
                $tags_json = json_encode(array_values($use_tags));
                $build->whereRaw("array(select jsonb_array_elements(things.thing_tags) ) && array(select jsonb_array_elements(?) )", $tags_json);
            } else {
                $build->whereRaw("(jsonb_array_length(things.thing_tags) is null OR jsonb_array_length(things.thing_tags) = 0)");
            }
        }

        if ($params) {
            if($params->getAsync() !== null) {
                $build->where('things.is_async',$params->getAsync());
            }

            if($params->getStatus() ) {
                $build->where('things.thing_status',$params->getStatus());
            }

            if ($params->getWaitUntil() ) {
                $build->where('things.thing_status',TypeOfThingStatus::THING_WAITING);
                $build->where('things.thing_wait_until_at','<=',$params->getWaitUntil());
            }

            if ($params->getRanAtMin() ) {
                $build->where('things.thing_ran_at','>=',$params->getRanAtMin());
            }

            if ($params->getRanAtMax() ) {
                $build->where('things.thing_ran_at','<=',$params->getRanAtMax());
            }

            if ($params->getCreatedAtMin() ) {
                $build->where('things.created_at','>=',$params->getCreatedAtMin());
            }

            if ($params->getCreatedAtMax() ) {
                $build->where('things.created_at','<=',$params->getCreatedAtMax());
            }

            if ($params->getStartedAtMin() ) {
                $build->where('things.thing_started_at','>=',$params->getCreatedAtMin());
            }

            if ($params->getStartedAtMax() ) {
                $build->where('things.thing_started_at','<=',$params->getCreatedAtMax());
            }

            if ($params->getErrorUuid() ) {
                $build->join('thing_errors as param_error','param_error.id','=','things.thing_error_id');
                $build->where('param_error.ref_uuid',$params->getErrorUuid());
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
    public function continueThing() :bool
    {
        if ($this->thing_status !== TypeOfThingStatus::THING_WAITING) {return false;}
        if ($this->thing_wait_until_at) {
            if (Carbon::parse($this->thing_wait_until_at,'UTC')->isBefore(Carbon::now('UTC'))) {return false;}
        }
        $this->thing_status = TypeOfThingStatus::THING_BUILDING;
        $this->thing_wait_until_at = null;
        $this->save();
        $count_incomplete_children = 0;
        foreach ($this->thing_children as $that) {
            if (!$that->isComplete()) { $count_incomplete_children++;}
        }
        if (!$count_incomplete_children) {
            $this->dispatchThing();
        }

        return true;
    }

    /**
     * @return int[]
     */
    public function getTreeErrorIds() : array {

        $query_descendants = DB::table("things as desc_a")
            ->selectRaw('desc_a.id, 0 as level')->where('desc_a.id', $this->id)
            ->unionAll(
                DB::table('things as desc_b')
                    ->selectRaw('desc_b.id, level + 1 as level')
                    ->join('thing_descendants', 'thing_descendants.id', '=', 'desc_b.parent_thing_id')
            );

        $lar =  Thing::select('things.id', 'things.thing_error_id')
            ->withRecursiveExpression('thing_descendants',$query_descendants)
            ->join('thing_descendants', 'thing_descendants.id', '=', 'things.id')
            ->whereNotNull('things.thing_error_id');

        $lar->orderBy('thing_error_id','desc');

        return $lar->pluck('thing_error_id')->toArray();
    }



}
