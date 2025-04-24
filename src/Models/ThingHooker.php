<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfHookerStatus;
use Hexbatch\Things\Enums\TypeOfThingCallbackStatus;
use Hexbatch\Things\Enums\TypeOfThingHookBlocking;
use Hexbatch\Things\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Interfaces\IThingCallback;
use Hexbatch\Things\Jobs\SendCallback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int hooked_thing_id
 * @property int owning_hook_id
 *
 * @property int hook_http_status
 *
 * @property string ref_uuid
 * @property TypeOfHookerStatus hooker_status
 * @property ArrayObject outgoing_hook_data
 *
 * @property ThingHook parent_hook
 * @property Thing hooker_thing
 * @property ThingCallback[] hooker_callbacks
 *
 *
 */
class ThingHooker extends Model
{


    protected $table = 'thing_hookers';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'outgoing_hook_data' => AsArrayObject::class,
        'hooker_status' => TypeOfHookerStatus::class,
    ];

    public function parent_hook() : BelongsTo {
        return $this->belongsTo(ThingHook::class,'owning_hook_id','id');
    }

    public function hooker_thing() : BelongsTo {
        return $this->belongsTo(Thing::class,'hooked_thing_id','id');
    }

    public function hooker_callbacks() : HasMany {
        return $this->hasMany(ThingCallback::class,'owning_hooker_id','id')
            /** @uses ThingCallback::callback_owning_hooker() */
            ->with('callback_owning_hooker');
    }

    public function dispatchHooker() {
        foreach ($this->hooker_callbacks as $callback) {
            SendCallback::dispatch($callback);
        }
    }

    /**
     * @return void
     */
    public function maybeCallbacksDone() {

        foreach ($this->hooker_callbacks as $cb) {
            if ($cb->isDone() ) { return;}
        }
        $this->hooker_status = TypeOfHookerStatus::HOOK_COMPLETE;
        $this->save();

        if (!$this->parent_hook->isBlocking()) { return; }
        $this->hooker_thing->resumeBlockedThing();

    }

    public static function getHookerData(int $thing_id,TypeOfThingHookMode $mode,bool &$b_out_of_time,bool &$b_still_pending,
        array &$data_for_this, array &$data_for_parent
    )
    : void
    {
        // if the time to live is exceeded, for any of those callbacks, need to do them again so the thing must return in the caller function in the level above
        $data_for_this = [];
        $data_for_parent = [];
        $b_out_of_time = false;
        $b_still_pending = false;
        $hookers = ThingHooker::buildHooker(belongs_to_tree_thing_id: $thing_id, mode: $mode)
            ->orderBy('id')
            ->get();


        /** @var ThingCallback[] $redo_callbacks */
        $redo_callbacks = [];
        /** @var ThingHooker $hooker */
        foreach ($hookers as $hooker) {
            if (!$hooker->isDone()) {
                $b_still_pending = true;
                continue;
            }
            foreach ($hooker->hooker_callbacks as $callback) {
                if (Carbon::parse($callback->callback_run_at,'UTC')->addSeconds($hooker->parent_hook->ttl_callbacks) >
                    Carbon::now()->timezone('UTC'))
                {
                    $b_out_of_time = true;
                    $redo_callbacks[] = $callback;
                }
                switch ($hooker->parent_hook->blocking_mode) {
                    case TypeOfThingHookBlocking::BLOCK_ADD_DATA_TO_CURRENT: {
                        $data_for_this = array_merge($callback->callback_incoming_data->getArrayCopy(),$data_for_this);
                        break;
                    }
                    case TypeOfThingHookBlocking::BLOCK_ADD_DATA_TO_PARENT: {
                        $data_for_parent = array_merge($callback->callback_incoming_data->getArrayCopy(),$data_for_parent);
                        break;
                    }
                    case TypeOfThingHookBlocking::BLOCK:
                    case TypeOfThingHookBlocking::NONE:
                    {

                        break;
                    }
                } //end switch
            } //end foreach callback
        } //end foreach hooker
        if ($b_out_of_time) {
            foreach ($redo_callbacks as $callback) {
                SendCallback::dispatch($callback);
            }
        }
    }

    public static function buildHooker(
        ?int                  $id = null,
        ?int                  $thing_id = null,
        ?int                  $hook_id = null,
        ?int                  $belongs_to_tree_thing_id = null,
        ?int                  $belongs_to_ancestor_of_thing_id = null,
        ?TypeOfHookerStatus   $status = null,
        ?TypeOfThingHookMode $mode = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  ThingHooker::select('thing_hookers.*')
            ->selectRaw(" extract(epoch from  thing_hookers.created_at) as created_at_ts, ".
                " extract(epoch from  thing_hookers.updated_at) as updated_at_ts")
        ;

        if ($id) {
            $build->where('thing_hookers.id',$id);
        }

        if ($thing_id) {
            $build->where('thing_hookers.hooked_thing_id',$thing_id);
        }

        if ($hook_id) {
            $build->where('thing_hookers.owning_hook_id',$hook_id);
        }

        if ($status) {
            $build->where('thing_hookers.hooker_status',$status);
        }

        if ($belongs_to_tree_thing_id) {
            $build->join('things as fam',
                /** @param JoinClause $join */
                function ($join) use($belongs_to_tree_thing_id) {
                    $join
                        ->on('fam.id','=','thing_hookers.hooked_thing_id')
                        ->where('fam.root_thing_id',$belongs_to_tree_thing_id);
                }
            );
        }

        if ($belongs_to_ancestor_of_thing_id) {
            $build->withRecursiveExpression('thing_ancestors',
                /** @param \Illuminate\Database\Query\Builder $query */
                function ($query) use($belongs_to_ancestor_of_thing_id)
                {
                    $query->from('things as s')->select('s.id,s.parent_thing_id')->where('s.id',$belongs_to_ancestor_of_thing_id)
                        ->unionAll(
                            DB::table('things as c')->select('c.id,c.parent_thing_id')
                                ->join('thing_ancestors', 'thing_ancestors.parent_thing_id', '=', 'c.id')
                        );
                }
            )
            ->join('thing_ancestors', 'thing_ancestors.id', '=', 'thing_hookers.hooked_thing_id')
            ->where('thing_ancestors.id','<>',$belongs_to_ancestor_of_thing_id);
        }

        if ($mode) {
            $build->join('thing_hooks as parent',
                /** @param JoinClause $join */
                function ($join) use($mode) {
                    $join
                        ->on('parent.id','=','thing_hookers.owning_hook_id')
                        ->where('parent.hook_mode',$mode);
                }
            );
        }




        /** @uses ThingHooker::parent_hook(),ThingHooker::hooker_thing(),ThingHooker::hooker_callbacks() */
        $build->with('parent_hook','hooker_thing','hooker_callbacks');


        return $build;
    }


    public function makeCallback(IThingCallback $call_me) : ThingCallback {


        $c = new ThingCallback();
        $c->callback_callplate_id = $this->id;
        $c->thing_callback_status = TypeOfThingCallbackStatus::WAITING;
        $c->thing_callback_type = $call_me->getCallbackType();
        $c->callback_outgoing_data = array_merge($call_me->getConstantData(),
            $this->parent_hook->hook_constant_data?->getArrayCopy()??[]);
        $c->callback_outgoing_header = $call_me->getHeader();
        if ($owner = $call_me->getCallbackOwner()) {
            $c->owner_type_id = $owner->getOwnerId();
            $c->owner_type = $owner::getOwnerTypeStatic();
        } else {
            $c->owner_type_id = $this->parent_hook->owner_type_id;
            $c->owner_type = $this->parent_hook->owner_type;
        }

        $c->callback_url = $call_me->getCallbackUrl();
        $c->callback_class = $call_me->getCallbackClass();
        $c->callback_function = $call_me->getCallbackFunction();
        $c->callback_event = $call_me->getCallbackEvent();
        $c->save();
        return $c;
    }

    public function isDone() : bool {
        return $this->hooker_status === TypeOfHookerStatus::HOOK_COMPLETE;
    }


}
