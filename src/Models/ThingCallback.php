<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Models\Enums\TypeOfThingCallback;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackStatus;
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
 * @property int owning_hooker_id
 * @property int callback_callplate_id
 * @property int callback_error_id
 *
 * @property int callback_http_code
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property string ref_uuid
 *
 * @property string  callback_outgoing_header
 * @property string  callback_url
 * @property string  callback_class
 * @property string  callback_function
 * @property string  callback_event
 * @property ArrayObject callback_outgoing_data
 * @property ArrayObject callback_incoming_data
 * @property TypeOfThingCallback thing_callback_type
 * @property TypeOfThingCallbackStatus thing_callback_status
 *
 * @property string callback_run_at
 * @property string created_at
 * @property string modified_at
 *
 */
class ThingCallback extends Model
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
        'callback_outgoing_data' => AsArrayObject::class,
        'callback_incoming_data' => AsArrayObject::class,
        'thing_callback_type' => TypeOfThingCallback::class,
        'thing_callback_status' => TypeOfThingCallbackStatus::class,
    ];


}
