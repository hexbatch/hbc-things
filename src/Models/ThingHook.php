<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Models\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

/**
 * if the thing has a debugger, it will call this url with a data dump of it, and its data for each breakpoint or single-step
 *   if single step or breakpoint, then the children are collected, and the parent is collected but the parent is paused
 *   if step over, then just stops at each parent and not siblings
 * only one can be marked primary (turn others off)
 * if there is a primary, then each new thing is marked with this, and will not run automatically unless this is run to cursor or step over or off
 * if the hooked_thing_callback_url is null, then results logged

 */


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 * @property bool is_on
 * @property bool is_blocking
 *
 * @property string hooked_thing_callback_url
 * @property string ref_uuid
 * @property string hook_name
 * @property string hook_notes
 * @property ArrayObject extra_data
 * @property TypeOfThingHookMode thing_hook_mode
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
        'extra_data' => AsArrayObject::class,
        'thing_hook_mode' => TypeOfThingHookMode::class,
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

}
