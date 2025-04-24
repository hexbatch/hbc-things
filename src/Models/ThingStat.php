<?php

namespace Hexbatch\Things\Models;




use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Interfaces\IThingAction;
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
 * @property bool stat_is_async
 * @property int stat_data_byte_rows
 * @property int stat_descendants
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


    public function stat_thing() : BelongsTo {
        return $this->belongsTo(Thing::class,'stat_thing_id','id');
    }

    /**
     * @param int $n_extra how many extra things to add
     * @return int  the limit that is over
     */
    public function checkForDescendantOverflow(int $n_extra) : int {
        if ($this->stat_descendants === 0) { return 0;}

        $count = $this->stat_thing->countDescendants();
        if ($this->stat_descendants >= $count + $n_extra) {
            return 0;
        }
        return $count + $n_extra - $this->stat_descendants;
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

    public function getDataLimit() : int {
        return $this->stat_limit_data_byte_rows;
    }

    public function updateDataStats(IThingAction $action) {
        $this->stat_data_byte_rows = $action->getDataByteRowsUsed();
        //todo update the parents
        $this->save();
    }

    public function getBackoffFutureTime() : Carbon {
        return Carbon::now();
        //todo count the back-offs in the tree, check the policy, calculate the next time to start again
        // sibling backoff counts not used
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
         * stat_is_async : if any parent is true this is true
         */
        $chain = $thing->getAncestorChain();
        $constants = $chain[0]->thing_constant_data?->getArrayCopy()??[];

        for($i = 1 ; $i < count($chain); $i++) {
            $constants = array_merge($constants,$chain[$i]->thing_constant_data?->getArrayCopy()??[]);
        }
        $node->stat_constant_data = $constants;


        $node->stat_data_byte_rows = $action->getDataByteRowsUsed();

        $node->save();
        return $node;
    }


    public static function updateStatsAfterBuilding(Thing $thing) : ThingStat {
        $node = $thing->thing_stat;
        /**
         * todo update stats with ideally one sql starting at the leaves and updating each parent above, iterating to root
         * stat_data_byte_rows:  add in all descendant values (start with leaves)
         *
         */
        $node->save();
        return $node;
    }


}
