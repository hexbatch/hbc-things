<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Interfaces\IHookParams;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;



/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 * @property bool is_on
 * @property bool is_manual
 * @property bool is_after
 * @property bool is_sharing
 *
 * @property bool is_blocking
 * @property bool is_writing_data_to_thing
 * @property int ttl_shared
 * @property int hook_priority
 *
 * @property string ref_uuid
 * @property string hook_name
 * @property string  address
 * @property string hook_notes
 * @property ArrayObject hook_tags
 * @property TypeOfHookMode hook_mode
 *
 * @property ArrayObject hook_data_template
 * @property ArrayObject hook_header_template
 * @property TypeOfCallback hook_callback_type
 * @property ThingCallback[] hook_callbacks
 *
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
    protected $fillable = [
        'action_type',
        'action_type_id',
        'owner_type',
        'owner_type_id',
        'is_on',
        'is_manual',
        'is_after',
        'is_sharing',
        'is_blocking',
        'is_writing_data_to_thing',
        'ttl_shared',
        'hook_priority',
        'hook_name',
        'address',
        'hook_notes',
        'hook_tags',
        'hook_mode',
        'hook_data_template',
        'hook_header_template',
    ];

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
        'is_manual' => 'boolean',
        'is_after' => 'boolean',
        'is_sharing' => 'boolean',
        'is_blocking' => 'boolean',
        'hook_mode' => TypeOfHookMode::class,
        'hook_callback_type' => TypeOfCallback::class,
        'hook_header_template' => AsArrayObject::class,
        'hook_tags' => AsArrayObject::class,
        'hook_data_template' => AsArrayObject::class,
    ];


    /**
     * @see static::hook_callbacks() used in laravel
     * @see static::$hook_callbacks as the bind point
     */
    public function hook_callbacks() : HasMany {
        return $this->hasMany(ThingCallback::class,'owning_hook_id','id');
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
                $ret = static::buildHook(me_id:$ret->id)->first();
            }
        } finally {
            if (empty($ret)) {
                throw new \RuntimeException(
                    "Did not find hook with $field $value"
                );
            }
        }
        return $ret;
    }

    public static function buildHook(
        ?int            $me_id = null,
        ?TypeOfHookMode $mode = null,
        ?IThingAction   $action = null,
        ?string         $action_type = null,
        ?int            $action_id = null,
        ?IThingOwner    $owner = null,
        ?string         $owner_type = null,
        ?int            $owner_id = null,
        array           $owners = [],
        ?array          $tags = null,
        ?bool           $is_on = null,
        ?bool           $is_after = null,
        ?bool           $is_blocking = null,
        ?TypeOfCallback $callback_type = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  ThingHook::select('thing_hooks.*')
            ->selectRaw(" extract(epoch from  thing_hooks.created_at) as created_at_ts,  extract(epoch from  thing_hooks.updated_at) as updated_at_ts")
        ;

        if ($me_id) {
            $build->where('thing_hooks.id',$me_id);
        }

        if ($is_on !== null) {
            $build->where('thing_hooks.is_on',$is_on);
        }
        if ($is_after !== null) {
            $build->where('thing_hooks.is_after',$is_after);
        }
        if ($is_blocking !== null) {
            $build->where('thing_hooks.is_blocking',$is_blocking);
        }

        if ($callback_type) { $build->where('thing_hooks.hook_callback_type',$callback_type); }
        if ($action_type) { $build->where('thing_hooks.action_type',$action_type); }
        if ($action_id) { $build->where('thing_hooks.action_type_id',$action_id); }
        if ($owner_type) { $build->where('thing_hooks.owner_type',$owner_type); }
        if ($owner_id) { $build->where('thing_hooks.owner_type_id',$owner_id); }

        $action_clause = function(Builder $build) use($action){
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
        };


        $owner_clause = function(Builder $build) use($owner) {
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
        };

        if ($action && $owner) {
            $build->where(function (Builder $q) use($action_clause,$owner_clause) {
                $q->where(function (Builder $q) use($owner_clause) {
                    $owner_clause(build: $q);
                });

                $q->orWhere(function (Builder $q) use($action_clause) {
                    $action_clause(build: $q);
                });
            });
        } elseif ($action) {
            $action_clause(build: $build);
        } elseif ($owner) {
            $owner_clause(build: $build);
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


        if ($tags !== null ) {
            if (count($tags) ) {
                $tags_json = json_encode(array_values($tags));
                $build->whereRaw("array(select jsonb_array_elements(thing_hooks.hook_tags) ) && array(select jsonb_array_elements(?) )", $tags_json);
            } else {
                $build->whereRaw("jsonb_array_length(thing_hooks.hook_tags) is null OR jsonb_array_length(thing_hooks.hook_tags) = 0");
            }
        }



        return $build;
    }

    public function updateHook(IHookParams $it)
    {
        $owner = $it->getHookOwner();
        $this->owner_type_id = $owner?->getOwnerId() ;
        $this->owner_type = $owner?->getOwnerType() ;

        $action = $it->getHookAction();
        $this->action_type_id = $action?->getActionId() ;
        $this->action_type = $action?->getActionType() ;


        if ($it->isHookOn() !== null) { $this->is_on = $it->isHookOn() ;}
        if ($it->isSharing() !== null) { $this->is_sharing = $it->isSharing() ; }
        if ($it->isAfter() !== null) { $this->is_after = $it->isAfter() ; }
        if ($it->isManual() !== null) { $this->is_manual = $it->isManual() ; }
        if ($it->isBlocking() !== null) { $this->is_blocking = $it->isBlocking() ; }
        if ($it->isWriting() !== null) { $this->is_writing_data_to_thing = $it->isWriting() ; }

        if ($it->getHookNotes() !== null) { $this->hook_notes = $it->getHookNotes() ; }
        if ($it->getHookName() !== null) { $this->hook_name = $it->getHookName() ;}
        if ($it->getHookMode() !== null) {  $this->hook_mode = $it->getHookMode() ;}
        if ($it->getSharedTtl() !== null) {  $this->ttl_shared = $it->getSharedTtl() ;}
        if ($it->getPriority() !== null) {  $this->hook_priority = $it->getPriority() ;}

        if ($it->getCallbackType() !== null) {  $this->hook_callback_type = $it->getCallbackType();}
        if ($it->getAddress() !== null) {  $this->address = $it->getAddress();}

        if ($it->getHookTags() !== null) { $this->hook_tags = $it->getHookTags() ; }
        if ($it->getDataTemplate() !== null) {   $this->hook_data_template = $it->getDataTemplate();}
        if ($it->getHeaderTemplate() !== null) {  $this->hook_header_template = $it->getHeaderTemplate();}
    }

    public static function createHook(IHookParams $it)
    : ThingHook
    {

        $hook = new ThingHook();
        $hook->updateHook($it);
        $hook->save();
        $hook->refresh();
        return $hook;
    }

}
