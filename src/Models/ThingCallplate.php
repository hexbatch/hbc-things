<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Models\Enums\TypeOfThingCallback;
use Hexbatch\Things\Models\Enums\TypeOfThingHookMode;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;



/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int callplate_for_hook_id
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property string ref_uuid
 * @property string  callplate_outgoing_header
 * @property string  callplate_url
 * @property string  callplate_class
 * @property string  callplate_function
 * @property string  callplate_event
 * @property ArrayObject callplate_constant_data
 * @property TypeOfThingCallback callplate_callback_type
 *
 */
class ThingCallplate extends Model
{
    use ThingOwnerHandler;

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
        'callplate_callback_type' => TypeOfThingCallback::class,
    ];


}
