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

/*
 * thing is marked as done when all children done, and there is no pagination id
 */
/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int stat_thing_id
 * @property int stat_data_byte_rows
 * @property int stat_descendants
 * @property int stat_limit_data_byte_rows
 * @property int stat_limit_descendants
 * @property int stat_backoff_data_policy
 * @property int stat_back_offs_done
 * @property ArrayObject stat_constant_data
 *
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
     * @param int $n_extra how many extra things to add
     * @return int  the limit that is over
     */
    public function checkForDescendantOverflow(int $n_extra) : int {
        return 0;
        //todo check this and ancestors to see if limit reached
    }


    /**
     * @return int  the limit that is over
     */
    public function checkForDataOverflow(int $extra_byte_rows = 0) : int {
        return 0;
        //todo check this and ancestors to see if limit reached
    }

    public function getDataLimit() : int {
        return 0;
        //todo check for the data limit based on ancestor and current used and limits
    }

    public function updateDataStats(IThingAction $action) {
        $this->stat_data_byte_rows += $action->getDataByteRowsUsed();
        $this->save();
    }

    public function getBackoffFutureTime() : Carbon {
        return Carbon::now();
        //todo count the back-offs in the tree, check the policy, calculate the next time to start again
        // sibling backoff counts not used
    }


}
