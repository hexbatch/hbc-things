<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfOwnerGroup;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Hexbatch\Things\OpenApi\Hooks\HookParams;
use Hexbatch\Things\OpenApi\Hooks\HookSearchParams;
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
 * @property int filter_owner_type_id
 * @property string filter_owner_type
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
        'filter_owner_type_id',
        'filter_owner_type',
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
        ?int              $me_id = null,
        ?TypeOfHookMode   $mode = null,
        ?IThingAction     $action = null,
        ?IThingOwner      $hook_owner = null,
        ?IThingOwner      $hook_owner_group = null,
        ?TypeOfOwnerGroup $hook_group_hint = null,
        ?array            $tags = null,
        ?bool             $is_on = null,
        ?bool             $is_after = null,
        ?bool             $is_blocking = null,
        ?TypeOfCallback   $callback_type = null,
        ?HookSearchParams $params = null,
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

        if ($is_on !== null || ($params?->getHookOn() !== null)) {
            $build->where('thing_hooks.is_on',$is_on || $params?->getHookOn());
        }


        if ($is_after !== null || ($params?->getIsAfter() !== null)) {
            $build->where('thing_hooks.is_after',$is_after || $params?->getIsAfter());
        }


        if ($is_blocking !== null || ($params?->getIsBlocking() !== null)) {
            $build->where('thing_hooks.is_blocking',$is_blocking || $params?->getIsBlocking());
        }


        if ($callback_type || $params?->getCallbackType()) {
            $build->where('thing_hooks.hook_callback_type',$callback_type?:$params->getCallbackType());
        }

        if ($mode || $params?->getMode()) {
            $build->where('thing_hooks.hook_mode',$mode?:$params->getMode());
        }



        if ($hook_owner) {
            $build->where('thing_hooks.owner_type',$hook_owner->getOwnerType());
            $build->where('thing_hooks.owner_type_id',$hook_owner->getOwnerId());
        }





        if ($tags !== null ||($params->getTags() !== null)) {
            $use_tags = $tags?: $params->getTags();
            if (count($use_tags) ) {
                $tags_json = json_encode(array_values($use_tags));
                $build->whereRaw("array(select jsonb_array_elements(thing_hooks.hook_tags) ) && array(select jsonb_array_elements(?) )", $tags_json);
            } else {
                $build->whereRaw("(jsonb_array_length(thing_hooks.hook_tags) is null OR jsonb_array_length(thing_hooks.hook_tags) = 0)");
            }
        }


       if ($action && $hook_group_hint !== TypeOfOwnerGroup::HOOK_CALLBACK_CREATION) {
           $build->where('thing_hooks.action_type',$action->getActionType());
           $build->where('thing_hooks.action_type_id',$action->getActionId());
       }


        if ($hook_group_hint === TypeOfOwnerGroup::HOOK_CALLBACK_CREATION ) {

            if ($hook_owner_group) {
                $hook_owner_group->setReadGroupBuilding(builder: $build, connecting_table_name: 'thing_hooks',
                    connecting_owner_type_column: 'filter_owner_type', connecting_owner_id_column: 'filter_owner_type_id',
                    hint: $hook_group_hint,alias: 'gul');

                $build->where(function (Builder $q)  {
                    $q->where(function (Builder $q) {
                        $q->whereRaw('thing_hooks.filter_owner_type_id = gul.id');
                    })
                        ->orWhere(function (Builder $q) {
                            $q->whereNull('thing_hooks.filter_owner_type')->whereNull('thing_hooks.filter_owner_type_id');
                        });
                });
            }

            if ($action) {
                $build->where(function (Builder $q) use($action) {
                    $q->where(function (Builder $q) use($action) {
                        $q->where(function (Builder $q) use($action) {
                            $q->where('thing_hooks.action_type',$action->getActionType());
                            $q->where('thing_hooks.action_type_id',$action->getActionId());
                        })
                            ->orWhere(function (Builder $q) {
                                $q->whereNull('thing_hooks.action_type')->whereNull('thing_hooks.action_type_id');
                            });
                    });
                });
            }
        }
        elseif ( $hook_group_hint === TypeOfOwnerGroup::HOOK_LIST) {

            $hook_owner_group?->setReadGroupBuilding(builder: $build,connecting_table_name: 'thing_hooks',
                connecting_owner_type_column: 'owner_type',connecting_owner_id_column: 'owner_type_id',hint: $hook_group_hint);

        }

        if($params) {
            if ($params->getUuid()) { $build->where('thing_hooks.ref_uuid',$params->getUuid()); }
            if ($params->getActionType()) { $build->where('thing_hooks.action_type',$params->getActionType()); }
            if ($params->getActionId()) { $build->where('thing_hooks.action_type_id',$params->getActionId()); }
            if ($params->getOwnerType()) { $build->where('thing_hooks.owner_type',$params->getOwnerType()); }
            if ($params->getOwnerId()) { $build->where('thing_hooks.owner_type_id',$params->getOwnerId()); }
            if ($params->getFilterOwnerType()) { $build->where('thing_hooks.filter_owner_type',$params->getFilterOwnerType()); }
            if ($params->getFilterOwnerId()) { $build->where('thing_hooks.filter_owner_type_id',$params->getFilterOwnerId()); }
            if ($params->getIsWriting() !== null) { $build->where('thing_hooks.is_writing_data_to_thing',$params->getIsWriting()); }
            if ($params->getIsSharing() !== null) { $build->where('thing_hooks.is_sharing',$params->getIsSharing()); }
            if ($params->getIsManual() !== null) { $build->where('thing_hooks.is_manual',$params->getIsManual()); }
            if ($params->getTtlSharedMin() !== null) { $build->where('thing_hooks.ttl_shared','>=',$params->getTtlSharedMin()); }
            if ($params->getTtlSharedMax() !== null) { $build->where('thing_hooks.ttl_shared','<=',$params->getTtlSharedMax()); }
            if ($params->getPriorityMin() !== null) { $build->where('thing_hooks.hook_priority','>=',$params->getPriorityMin()); }
            if ($params->getPriorityMax() !== null) { $build->where('thing_hooks.hook_priority','<=',$params->getPriorityMax()); }
        }



        return $build;
    }

    public function updateHook(HookParams $it)
    {
        $owner = $it->getHookOwner();
        $this->owner_type_id = $owner?->getOwnerId() ;
        $this->owner_type = $owner?->getOwnerType() ;

        $this->filter_owner_type_id = $owner?->getOwnerId() ;
        $this->filter_owner_type = $owner?->getOwnerType() ;

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

        if ($it->getSharedTtl() !== null) {  $this->ttl_shared = $it->getSharedTtl() ;}
        if ($it->getPriority() !== null) {  $this->hook_priority = $it->getPriority() ;}

        if ($it->getCallbackType() !== null) {  $this->hook_callback_type = $it->getCallbackType();}
        if ($it->getAddress() !== null) {  $this->address = $it->getAddress();}

        if ($it->getHookTags() !== null) { $this->hook_tags = $it->getHookTags() ; }
        if ($it->getDataTemplate() !== null) {   $this->hook_data_template = $it->getDataTemplate();}
        if ($it->getHeaderTemplate() !== null) {  $this->hook_header_template = $it->getHeaderTemplate();}

        if ($it->getHookMode() !== null) {  $this->hook_mode = $it->getHookMode() ;}
        if (!$this->is_blocking && $this->is_after) {
            $this->hook_mode = TypeOfHookMode::NODE_FINALLY;
        }
        if (in_array($this->hook_mode,[TypeOfHookMode::NODE_FINALLY,TypeOfHookMode::NODE_FAILURE,TypeOfHookMode::NODE_SUCCESS])) {
            $this->is_after = true;
            $this->is_blocking = false;
            $this->is_writing_data_to_thing = false;
        }
    }

    public static function createHook(HookParams $it)
    : ThingHook
    {

        $hook = new ThingHook();
        $hook->updateHook($it);
        $hook->save();
        $hook->refresh();
        return $hook;
    }

}
