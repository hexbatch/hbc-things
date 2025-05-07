<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * thing is marked as done when all children done, and there is no pagination id
 */
/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int stat_thing_id
 * @property int stat_data_byte_rows
 * @property int stat_limit_data_byte_rows
 * @property int stat_limit_descendants
 * @property int stat_backoff_data_policy
 * @property int stat_back_offs_done
 * @property ArrayObject stat_constant_data
 *
 * @property Thing stat_thing
 * @property string created_at
 * @property string updated_at
 *

 */
class ThingStat extends Model
{
    use ThingOwnerHandler,ThingActionHandler;
    protected $table = 'thing_stats';
    public $timestamps = false;

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     *
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stat_constant_data' => AsArrayObject::class,
    ];


    /**
     * @uses static::stat_thing() nowhere now
     */
    public function stat_thing() : BelongsTo {
        return $this->belongsTo(Thing::class,'stat_thing_id','id');
    }



    /**
     * @return int  the limit that is over
     */
    public function checkForDataOverflow(int $extra_byte_rows = 0) : int {
        if ($this->stat_limit_data_byte_rows < $this->stat_data_byte_rows + $extra_byte_rows) {
            return 0;
        }
        return ($this->stat_data_byte_rows + $extra_byte_rows) - $this->stat_limit_data_byte_rows;
    }


    public function getBackoffFutureTime() : Carbon {
        return Carbon::now()->addSeconds(
            ThingSetting::getBackoffSeconds($this->stat_backoff_data_policy,$this->stat_back_offs_done));
    }

    public static function makeStatsWhileBuilding(Thing $thing) : ThingStat {
        if (!$thing->thing_stat) {
            $node = new ThingStat();
            $node->stat_thing_id = $thing->id;
        } else {
            $node = $thing->thing_stat;
        }
        $action = $thing->getAction();
        /**
         * stat_data_byte_rows: enter current value
         * stat_constant_data the merging of this and the parent data, with the last overwriting the first
         */
        $chain = $thing->getAncestorChain();
        $constants = $chain[0]->thing_constant_data?->getArrayCopy()??[];

        for($i = 1 ; $i < count($chain); $i++) {
            $constants = array_merge($constants,$chain[$i]->thing_constant_data?->getArrayCopy()??[]);
        }
        $node->stat_constant_data = $constants;


        $node->stat_data_byte_rows = $action?->getDataByteRowsUsed()??0;

        $node->save();
        return $node;
    }



}
