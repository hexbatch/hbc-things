<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Models\Enums\TypeHookedThingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int hooked_thing_id
 * @property int owning_thing_hook_id
 *
 * @property int hook_http_status
 *
 * @property string ref_uuid
 * @property TypeHookedThingStatus hooked_thing_status
 * @property ArrayObject outgoing_hook_data
 *
 * @property ThingHook hooker_parent
 * @property Thing hooker_thing
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
        'hooked_thing_status' => TypeHookedThingStatus::class,
    ];

    public function hooker_parent() : BelongsTo {
        return $this->belongsTo(ThingHook::class,'owning_thing_hook_id','id');
    }

    public function hooker_thing() : BelongsTo {
        return $this->belongsTo(Thing::class,'hooked_thing_id','id');
    }

    public function dispatchHooker() {
        //todo get the data from the thing action, but the thing tree needs some env too for context
    }




}
