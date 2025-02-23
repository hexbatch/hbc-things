<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Models\Enums\TypeOfThingHookBlocking;
use Hexbatch\Things\Models\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Models\Enums\TypeOfThingHookScope;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


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
 * @property ArrayObject callback_constant_data
 * @property TypeOfThingHookMode hook_mode
 * @property TypeOfThingHookBlocking blocking_mode
 * @property TypeOfThingHookScope hook_scope
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
        'callback_constant_data' => AsArrayObject::class,
        'hook_mode' => TypeOfThingHookMode::class,
        'hook_scope' => TypeOfThingHookScope::class,
        'blocking_mode' => TypeOfThingHookBlocking::class,
    ];



    /**
     * @param Thing $thing
     * @return ThingHooker[]
     */
    public static function makeHooksForThing(Thing $thing) : array {
        Log::info('hooked');
        //todo see if tree matches any hooks, if so add the hookers
        return [];
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

}
