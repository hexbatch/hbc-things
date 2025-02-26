<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Helpers\IThingCallback;
use Hexbatch\Things\Helpers\Utilities;
use Hexbatch\Things\Jobs\SendCallback;
use Hexbatch\Things\Models\Enums\TypeOfHookerStatus;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackStatus;
use Hexbatch\Things\Models\Enums\TypeOfThingHookMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;


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
     * @uses Thing::resumeBlockedThing()
     * @return void
     */
    public function maybeCallbacksDone() {
        //todo check status for all callbacks, when they are done, if the hook is blocking, resume the thing
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
            ->selectRaw(" extract(epoch from  thing_hookers.created_at) as created_at_ts,  extract(epoch from  thing_hookers.updated_at) as updated_at_ts")
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
            $build->join('thing_hooks as tree_hook',
                /** @param JoinClause $join */
                function ($join) use($belongs_to_tree_thing_id) {
                    $join
                        ->on('tree_hook.id','=','thing_hookers.hooked_thing_id')
                        ->where('tree_hook.root_thing_id',$belongs_to_tree_thing_id);
                }
            );
        }

        if ($belongs_to_ancestor_of_thing_id) {
            Utilities::getComposerPath(false); //rm this its placeholder to prevent warnings
            //todo do sql for finding all ancestors of this thing id
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
            $this->parent_hook->callback_constant_data?->getArrayCopy()??[]);
        $c->callback_outgoing_header = $call_me->getHeader();
        if ($owner = $call_me->getCallbackOwner()) {
            $c->owner_type_id = $owner->getOwnerId();
            $c->owner_type = $owner::getOwnerType();
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


}
