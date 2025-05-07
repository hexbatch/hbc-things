<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfHookBlocking;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfHookPosition;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\IHookParams;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
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
 *
 * @property string ref_uuid
 * @property string hook_name
 * @property string hook_notes
 * @property ArrayObject hook_constant_data
 * @property ArrayObject hook_tags
 * @property TypeOfHookMode hook_mode
 * @property TypeOfHookBlocking blocking_mode
 * @property TypeOfHookScope hook_scope
 * @property TypeOfHookPosition hook_position
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
        'is_on' => 'boolean',
        'hook_constant_data' => AsArrayObject::class,
        'hook_tags' => AsArrayObject::class,
        'hook_mode' => TypeOfHookMode::class,
        'hook_scope' => TypeOfHookScope::class,
        'hook_position' => TypeOfHookPosition::class,
        'blocking_mode' => TypeOfHookBlocking::class,
    ];


    public function hook_callplates() : HasMany {
        return $this->hasMany(ThingCallplate::class,'callplate_for_hook_id','id')
            /** @uses ThingCallplate::callplate_owning_hook() */
            ->with('callplate_owning_hook');
    }


    /**
     * @return ThingHooker[]
     * @throws \Exception
     */
    public static function makeHooksForThing(Thing $thing, ?TypeOfHookMode $mode = null) : array {

        try {
            $ret = [];
            DB::beginTransaction();
            //figure out node position
            if (!$thing->parent_thing_id) {
                $position = TypeOfHookPosition::ROOT;
            } elseif (count($thing->thing_children) > 0) {
                $position = TypeOfHookPosition::SUB_ROOT;
            } else {
                $position = TypeOfHookPosition::LEAF;
            }
            /** @var ThingHook[] $hooks */
            $hooks = ThingHook::buildHook(mode: $mode, action: $thing->getAction(), owner: $thing->getOwner(),
                position: $position,tags: $thing->thing_tags->getArrayCopy())->get();

            foreach ($hooks as $hook) {
                $hooker = new ThingHooker();
                $hooker->hooked_thing_id = $thing->id;
                $hooker->owning_hook_id = $hook->id;
                $hooker->save(); //callbacks are made from the pool of callplates when the hooker is run

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
            TypeOfHookBlocking::BLOCK,
            TypeOfHookBlocking::BLOCK_ADD_DATA_TO_CURRENT,
            TypeOfHookBlocking::BLOCK_ADD_DATA_TO_PARENT])
        ) {
            return true;
        }

        return false;
    }


    public static function buildHook(
        ?int                $id = null,
        ?TypeOfHookMode     $mode = null,
        ?IThingAction       $action = null,
        ?string             $action_type = null,
        ?int                $action_id = null,
        ?IThingOwner        $owner = null,
        ?string             $owner_type = null,
        ?int                $owner_id = null,
        array               $owners = [],

        ?TypeOfHookPosition $position = null,
        ?array              $tags = null
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

        if ($action_type) { $build->where('thing_hooks.action_type',$action_type); }
        if ($action_id) { $build->where('thing_hooks.action_type_id',$action_id); }
        if ($owner_type) { $build->where('thing_hooks.owner_type',$owner_type); }
        if ($owner_id) { $build->where('thing_hooks.owner_type_id',$owner_id); }

        if ($action) {
            $build->where(function (Builder $q) use($action) {
                $q->where(function (Builder $q) use($action) {
                    $q->where('thing_hooks.action_type',$action->getActionType());
                    $q->where('thing_hooks.action_type_id',$action->getActionId());
                })
                ->orWhere(function (Builder $q) {
                    $q->whereNull('thing_hooks.action_type')->orWhereNull('thing_hooks.action_type_id');
                });
            });
        }



        if ($owner) {
            $build->where(function (Builder $q) use($owner) {
                $q->where(function (Builder $q) use($owner) {
                    $q->where('thing_hooks.owner_type',$owner->getOwnerType());
                    $q->where('thing_hooks.owner_type_id',$owner->getOwnerId());
                })
                ->orWhere(function (Builder $q) {
                    $q->whereNull('thing_hooks.owner_type')->orWhereNull('thing_hooks.owner_type_id');
                });
            });
        }

        if (count($owners)) {
            $build->where(function (Builder $q) use($owners) {
                foreach ($owners as $some_owner) {
                    $q->orWhere(function (Builder $q) use($some_owner) {
                        $q->where('thing_hooks.owner_type',$some_owner->getOwnerType());
                        $q->where('thing_hooks.owner_type_id',$some_owner->getOwnerId());
                    });
                }
            });
        }


        if ($mode) {
            $build->where('thing_hooks.hook_mode',$mode);
        }

        if ($position) {
            $build->where(function (Builder $q) use($position) {
                $q->where('thing_hooks.hook_position',$position);
                $q->orWhere('thing_hooks.hook_position',TypeOfHookPosition::ANY_POSITION);
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

    public static function createHook(IHookParams $it)
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
        $hook->hook_constant_data = $it->getConstantData() ;
        $hook->hook_tags = $it->getHookTags() ;
        $hook->hook_notes = $it->getHookNotes() ;
        $hook->hook_name = $it->getHookName() ;

        if (!$it->getHookMode()) { throw new HbcThingException("Need hook mode");}
        $hook->hook_mode = $it->getHookMode() ;

        if (!$it->getHookBlocking()) { throw new HbcThingException("Need hook blocking");}
        $hook->blocking_mode = $it->getHookBlocking() ;

        if (!$it->getHookScope()) { throw new HbcThingException("Need hook scope");}
        $hook->hook_scope = $it->getHookScope() ;

        if (!$it->getHookPosition()) { throw new HbcThingException("Need hook position");}
        $hook->hook_position = $it->getHookPosition() ;

        $hook->save();

        foreach ($it->getCallplates() as $callplate_setup) {
            ThingCallplate::makeCallplate(hook: $hook,setup: $callplate_setup);
        }

        $hook->refresh();
        return $hook;
    }

}
