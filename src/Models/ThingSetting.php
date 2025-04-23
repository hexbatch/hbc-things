<?php

namespace Hexbatch\Things\Models;




use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\Traits\ThingActionHandler;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int setting_about_thing_id
 * @property string action_type
 * @property int action_type_id
 * @property string owner_type
 * @property int owner_type_id
 * @property int setting_rank
 * @property int descendants_limit
 * @property int data_byte_row_limit
 * @property int tree_limit
 * @property int backoff_data_policy
 *
 * @property string created_at
 * @property string updated_at
 *

 */
class ThingSetting extends Model
{
    use ThingOwnerHandler,ThingActionHandler;
    protected $table = 'thing_settings';
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

    ];

    const DEFAULT_DATA_BYTE_ROWS_LIMIT = 100;
    const DEFAULT_ANCESTOR_LIMIT = 100;
    const DEFAULT_BACKOFF_DATA_POLICY = 100;
    const DEFAULT_TREE_LIMIT = 100;



    public static function isTreeOverflow(IThingAction $action, ?IThingOwner $owner,int &$limit = 0)
    : bool
    {
        $limit = 0;
        return false;
        //todo find rule to count trees, count them that way, and see if tree count limit reached
    }

    public static function makeStatFromSettings(Thing $thing) : ThingStat {
        $node = ThingStat::makeStatsWhileBuilding($thing);

        //todo fill in rest of stats rows based on settings or defaults
        /*
         * stat_limit_data_byte_rows: the minimum of this data_byte_row_limit, or default, among this and all ancestors
         * stat_descendants: the minimum of this tree_limit, or default, among this and ancestors
         */
        $node->save();
        return $node;
    }

}
