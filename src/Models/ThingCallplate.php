<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int callplate_for_hook_id
 *
 * @property string ref_uuid
 * @property string  callplate_url
 * @property string  callplate_class
 * @property string  callplate_event
 * @property ArrayObject callplate_data_template
 * @property ArrayObject callplate_outgoing_header
 * @property ArrayObject callplate_tags
 * @property TypeOfCallback callplate_callback_type
 *
 *
 * @property ThingHook callplate_owning_hook
 *
 */
class ThingCallplate extends Model
{

    protected $table = 'thing_callplates';
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
        'outgoing_constant_data' => AsArrayObject::class,
        'callplate_outgoing_header' => AsArrayObject::class,
        'callplate_tags' => AsArrayObject::class,
        'callplate_callback_type' => TypeOfCallback::class,
    ];


    public function callplate_owning_hook() : BelongsTo {
        return $this->belongsTo(Thing::class,'callplate_for_hook_id','id');
    }

    public function makeCallback(ThingHooker $hooker) : ThingCallback {
        if ($hooker->owning_hook_id !== $this->callplate_for_hook_id) {
            throw new \LogicException("hooks are different");
        }

        switch ($hooker->parent_hook->hook_scope) {
            case TypeOfHookScope::GLOBAL: {
                /** @var ThingHooker $global_hooker */
                $global_hooker = ThingHooker::buildHooker(hook_id: $hooker->id)->first();
                if ($global_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $global_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }
            case TypeOfHookScope::ALL_TREE: {
                /** @var ThingHooker $tree_hooker */
                $tree_hooker = ThingHooker::buildHooker(hook_id: $hooker->id,belongs_to_tree_thing_id: $hooker->hooker_thing->root_thing_id)->first();
                if ($tree_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $tree_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }

            case TypeOfHookScope::ANCESTOR_CHAIN:
            {
                /** @var ThingHooker $ancestor_hooker */
                $ancestor_hooker = ThingHooker::buildHooker(hook_id: $hooker->id,belongs_to_ancestor_of_thing_id: $hooker->hooker_thing->id)->first();
                if ($ancestor_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $ancestor_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }

            case TypeOfHookScope::CURRENT: {break;}
        }

        $c = new ThingCallback();
        $c->callback_callplate_id = $this->id;
        $c->thing_callback_status = TypeOfCallbackStatus::WAITING;
        $c->callback_outgoing_data = array_merge($this->callplate_data_template?->getArrayCopy()??[],
                                                        $hooker->parent_hook->hook_constant_data?->getArrayCopy()??[]);
        $c->callback_outgoing_header = $this->callplate_outgoing_header;
        $c->save();
        return $c;
    }


}
