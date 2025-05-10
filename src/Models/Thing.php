<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Exception;
use Hexbatch\Things\Enums\TypeOfThingStatus;
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
use Illuminate\Support\Facades\DB;
use LogicException;

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
 * @property string thing_start_at
 * @property string thing_invalid_at
 * @property string thing_started_at
 *
 * @property string batch_string_id
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
        'thing_tags' => AsArrayObject::class,
        'is_signalling_when_done' => 'boolean',
        'is_async' => 'boolean',
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


    public function thing_error() : BelongsTo {
        return $this->belongsTo(ThingError::class,'thing_error_id','id');
    }



    public function applied_callbacks() : HasMany {
        return $this->hasMany(ThingCallback::class,'source_thing_id','id');
    }






    public function isComplete() : bool {
        return in_array($this->thing_status,TypeOfThingStatus::STATUSES_OF_COMPLETION);
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

        /** @noinspection PhpUndefinedMethodInspection */
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


    protected function setStartData() {
        $this->update([
            'thing_status' => TypeOfThingStatus::THING_PENDING,
            'thing_started_at'=>DB::raw("NOW()")
        ]);
    }

    protected function setException(Exception $e) {
        $hex = ThingError::createFromException($e);
        $this->thing_error_id = $hex->id;
        $this->save();
    }


    /**
     * @throws Exception
     */
    public function runThing() :void {
        if ($this->thing_status !== TypeOfThingStatus::THING_PENDING) {
            if ($this->isComplete() ) {
                return;
            }
            // something happened in the pauses between steps, so just stop running this
            return;
        }
        /** @var IThingAction|null $action */
        /*
        todo move part of this to the dispatch, where make sure the thing is pending, and we set the start, and make sure it can run (pending)

        todo have hooks optionally add in extra thing nodes, so do not check for that here (and remove that from the IAction)
        */
        try {
            DB::beginTransaction();
            $b_ok = true;
            if($this->thing_invalid_at) {
                if(Carbon::parse($this->thing_start_at)->isAfter($this->thing_invalid_at) ) {
                    //it fails
                    $this->thing_status = TypeOfThingStatus::THING_INVALID;
                    $b_ok = false;
                }
            } //end invalid check

            if ($b_ok) {
                $action->runAction();

                if ($action->isActionComplete()) {
                    if ($action->isActionSuccess()) {
                        $this->thing_status = TypeOfThingStatus::THING_SUCCESS;
                    } else if ($action->isActionFail()) {
                        $this->thing_status = TypeOfThingStatus::THING_FAIL;
                    } else {
                        $this->thing_status = TypeOfThingStatus::THING_ERROR;
                    }
                    if ($this->is_signalling_when_done) {
                        //it is done, for better or worse
                        $this->signal_parent();
                    }
                } else {
                    $this->thing_status = TypeOfThingStatus::THING_PENDING;
                }
            }//end if is ok

            $this->save();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->thing_status = TypeOfThingStatus::THING_ERROR;
            $this->setException($e);
            $this->save();
            throw $e;
        }

    }

    /**
     * @throws Exception
     */
    public function signal_parent() {
        if ($this->parent_thing_id) {
            try {
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
            } catch (Exception $e) {
                DB::rollBack();
                $this->thing_status = TypeOfThingStatus::THING_ERROR;
                $this->setException($e);
                $this->save();
                throw $e;
            } finally {
                //see if all children ran, if so, put the parent on the processing
                $this->maybeQueueMore();
            }

        }
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
        /*
         * todo here we find the hooks for the thing, and arrange them in the bus
         *  the callbacks will be made here, and organized, if a manual is found, add callbacks until that (pre),
         *  including it if has address
         *  or run thing and callbacks until the manual
         *
         * todo find the last blocking callback/thing , and set is_signalling_when_done  to tell the thing its done ,
         *
         * * todo when figuring out the hook, and if  shared, then see if hook will be used later, up the ancestry chain of things: if so,
         *    pick the highest ancestor, then create the result using that. When referencing this again, see if ttl + callback_run_at is less than now,
         *    if so, discard this (delete) and make new result with same thing as before
         *
         * todo put the final, fail and success callbacks in the handlers
         *
         */

        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook( action: $this->getAction(), owner: $this->getOwner(),
            tags: $this->thing_tags->getArrayCopy(),is_on: true)->get()->toArray();

        $callbacks = [];
        foreach ($hooks as $hook) {
            $callbacks[] = ThingCallback::createFromHook(hook: $hook,thing: $this);
        }

        $this->setStartData(); //combine with status change here in the update
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
    public static function buildFromAction(IThingAction $action, IThingOwner $owner, array $extra_tags = [])
    : \Illuminate\Database\Eloquent\Collection
    {

        try {
            DB::beginTransaction();
            $root = static::makeThingTree(action: $action, extra_tags: $extra_tags,owner: $owner);

            $root->pushLeavesToJobs();
            DB::commit();
            return ThingCallback::buildCallback(thing_id: $root->id)->get();
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


    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $ret = null;
        try {
            if ($field) {
                $ret = $this->where($field, $value)->first();
            } else {
                if (ctype_digit($value)) {
                    $ret = $this->where('id', $value)->first();
                } else {
                    $ret = $this->where('ref_uuid', $value)->first();
                }
            }
            if ($ret) {
                $ret = static::buildThing(me_id:$ret->id)->first();
            }
        } finally {
            if (empty($ret)) {
                throw new \RuntimeException(
                    "Did not find thing with $field $value"
                );
            }
        }
        return $ret;
    }

    /*
     *
     */



}
