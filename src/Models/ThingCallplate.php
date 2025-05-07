<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackSharing;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Interfaces\ICallplateOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int callplate_for_hook_id
 * @property int ttl_shared
 *
 * @property string ref_uuid
 * @property string  address
 * @property ArrayObject callplate_data_template
 * @property ArrayObject callplate_header_template
 * @property ArrayObject callplate_tags
 * @property TypeOfCallback callplate_callback_type
 * @property TypeOfCallbackSharing callplate_sharing_type
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
        'callplate_header_template' => AsArrayObject::class,
        'callplate_tags' => AsArrayObject::class,
        'callplate_callback_type' => TypeOfCallback::class,
        'callplate_sharing_type' => TypeOfCallbackSharing::class,
    ];


    public function callplate_owning_hook() : BelongsTo {
        return $this->belongsTo(Thing::class,'callplate_for_hook_id','id');
    }

    public static function makeCallplate(ThingHook $hook,ICallplateOptions $setup) : ThingCallplate {
        $node = new ThingCallplate();
        $node->callplate_for_hook_id = $hook->id;


        $node->ttl_shared = $setup->getSharedTtl();
        $node->callplate_data_template = $setup->getDataTemplate();
        $node->callplate_header_template = $setup->getHeaderTemplate();
        $node->callplate_tags = $setup->getTags();

        if (!$setup->getCallbackSharing()) { throw new HbcThingException("Need sharing mode");}
        $node->callplate_sharing_type = $setup->getCallbackSharing();

        if (!$setup->getCallbackType()) { throw new HbcThingException("Need callback type");}
        $node->callplate_callback_type = $setup->getCallbackType();

        if (!$setup->getAddress()) { throw new HbcThingException("Need address");}
        $node->address = $setup->getAddress();

        $node->save();
        return $node;
    }

}
