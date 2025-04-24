<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfThingHookBlocking;
use Hexbatch\Things\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Enums\TypeOfThingHookPosition;
use Hexbatch\Things\Enums\TypeOfThingHookScope;
use Hexbatch\Things\Interfaces\IThingCallback;
use Hexbatch\Things\Interfaces\IThingHook;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 * @property bool is_on
 * @property int ttl_callbacks
 *
 * @property string ref_uuid
 * @property string hook_name
 * @property string hook_notes
 * @property ArrayObject hook_constant_data
 * @property ArrayObject hook_tags
 * @property TypeOfThingHookMode hook_mode
 * @property TypeOfThingHookBlocking blocking_mode
 * @property TypeOfThingHookScope hook_scope
 * @property TypeOfThingHookPosition hook_position
 *
 * @property ThingCallplate[] hook_callplates
 *
 */
class ThingHook extends Model
{
    use ThingOwnerHandler,ThingActionHandler;

    protected $table = 'thing_hooks';
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
        'hook_constant_data' => AsArrayObject::class,
        'hook_tags' => AsArrayObject::class,
        'hook_mode' => TypeOfThingHookMode::class,
        'hook_scope' => TypeOfThingHookScope::class,
        'hook_position' => TypeOfThingHookPosition::class,
        'blocking_mode' => TypeOfThingHookBlocking::class,
    ];


    public function hook_callplates() : HasMany {
        return $this->hasMany(ThingCallplate::class,'callplate_for_hook_id','id')
            /** @uses ThingCallplate::callplate_owning_hook() */
            ->with('callplate_owning_hook');
    }


    /**
     * @param  array<string,IThingCallback[]> $callbacks
     * @return ThingHooker[]
     * @throws \Exception
     */
    public static function makeHooksForThing(Thing $thing,?TypeOfThingHookMode $mode = null, array $callbacks = []) : array {

        try {
            $ret = [];
            DB::beginTransaction();
            //figure out node position
            if (!$thing->parent_thing_id) {
                $position = TypeOfThingHookPosition::ROOT;
            } elseif (count($thing->thing_children()->get()) > 0) {
                $position = TypeOfThingHookPosition::SUB_ROOT;
            } else {
                $position = TypeOfThingHookPosition::LEAF;
            }
            /** @var ThingHook[] $hooks */
            $hooks = ThingHook::buildHook(mode: $mode, action_type: $thing->action_type, action_type_id: $thing->action_type_id,
                owner_type: $thing->owner_type, owner_type_id: $thing->owner_type_id,position: $position)->get();

            foreach ($hooks as $hook) {
                $hooker = new ThingHooker();
                $hooker->hooked_thing_id = $thing->id;
                $hooker->owning_hook_id = $hook->id;
                $hooker->save();
                foreach ($hook->hook_callplates as $plate) {
                    $plate->makeCallback(hooker: $hooker);
                }
                foreach ($callbacks as $hook_name => $callback_array) {
                    if ($hook->hook_name === $hook_name) {
                        foreach ($callback_array as $callback) {
                            $hooker->makeCallback(call_me: $callback);
                        }

                    }

                }
                $ret[] = ThingHooker::buildHooker(id: $hooker->id);
            }
            DB::commit();
            return $ret;
        } catch(\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function isBlocking() : bool {
        if (in_array($this->blocking_mode,[
            TypeOfThingHookBlocking::BLOCK,
            TypeOfThingHookBlocking::BLOCK_ADD_DATA_TO_CURRENT,
            TypeOfThingHookBlocking::BLOCK_ADD_DATA_TO_PARENT])
        ) {
            return true;
        }

        return false;
    }


    public static function buildHook(
        ?int    $id = null,
        ?TypeOfThingHookMode $mode = null,
        ?string $action_type = null, ?int  $action_type_id = null,
        ?string $owner_type = null, ?int  $owner_type_id = null,
        ?TypeOfThingHookPosition $position = null,
        ?array $tags = null
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  ThingHook::select('thing_hooks.*')
            ->selectRaw(" extract(epoch from  thing_hooks.created_at) as created_at_ts,  extract(epoch from  thing_hooks.updated_at) as updated_at_ts")
        ;

        if ($id) {
            $build->where('thing_hooks.id',$id);
        }

        if ($action_type && $action_type_id) {
            $build->where(function (Builder $q) use($action_type,$action_type_id) {
                $q->where(function (Builder $q) use($action_type,$action_type_id) {
                    $q->where('thing_hooks.action_type',$action_type);
                    $q->where('thing_hooks.action_type_id',$action_type_id);
                })
                ->orWhere(function (Builder $q) {
                    $q->whereNull('thing_hooks.action_type')->orWhereNull('thing_hooks.action_type_id');
                });
            });
        }



        if ($owner_type && $owner_type_id) {
            $build->where(function (Builder $q) use($owner_type,$owner_type_id) {
                $q->where(function (Builder $q) use($owner_type,$owner_type_id) {
                    $q->where('thing_hooks.owner_type',$owner_type);
                    $q->where('thing_hooks.owner_type_id',$owner_type_id);
                })
                    ->orWhere(function (Builder $q) {
                        $q->whereNull('thing_hooks.owner_type')->orWhereNull('thing_hooks.owner_type_id');
                    });
            });
        }


        if ($mode) {
            $build->where('thing_hooks.hook_mode',$mode);
        }

        if ($position) {
            $build->where(function (Builder $q) use($position) {
                $q->where('thing_hooks.hook_position',$position);
                $q->orWhere('thing_hooks.hook_position',TypeOfThingHookPosition::ANY_POSITION);
            });
        }

        if ($tags !== null ) {
            if (count($tags) ) {
                $tags_json = json_encode($tags);
                $build->whereRaw("array(select jsonb_array_elements(thing_hooks.hook_tags) ) && array(select jsonb_array_elements(?) )", $tags_json);
            } else {
                $build->whereRaw("jsonb_array_length(thing_hooks.hook_tags) is null OR jsonb_array_length(thing_hooks.hook_tags) = 0");
            }
        }


        /**
         * @uses ThingHook::hook_callplates()
         */
        $build->with('hook_callplates');


        return $build;
    }

    public static function createHook(IThingHook $it)
    : ThingHook
    {
        $hook = new ThingHook();
        $owner = $it->getHookOwner();
        $hook->owner_type_id = $owner?->getOwnerId() ;
        $hook->owner_type = $owner?->getOwnerType() ;

        $action = $it->getHookAction();
        $hook->action_type_id = $action?->getActionId() ;
        $hook->action_type = $action?->getActionType() ;
        $hook->is_on = $it->isHookOn() ;
        $hook->ttl_callbacks = $it->getHookCallbackTimeToLive() ;
        $hook->hook_constant_data = $it->getConstantData() ;
        $hook->hook_tags = $it->getHookTags() ;
        $hook->hook_notes = $it->getHookNotes() ;
        $hook->hook_mode = $it->getHookMode() ;
        $hook->blocking_mode = $it->getHookBlocking() ;
        $hook->hook_scope = $it->getHookScope() ;
        $hook->hook_position = $it->getHookPosition() ;
        $hook->hook_name = $it->getHookName() ;

        $hook->save();
        return $hook;
    }

}
