<?php

namespace Hexbatch\Things\Models;




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


    /**
     * @return int  the limit for trees
     */
    public static function checkForTreeOverflow(string $action_type,int $action_type_id,string $owner_type,int $owner_type_id)
    : int
    {
        return 0;
        //todo find rule to count trees, count them that way, and see if tree count limit reached
    }

    public static function makeStatFromSettings(Thing $thing) : ThingStat {
        $node = new ThingStat();
        $node->stat_thing_id = $thing;
        //todo fill in rest of stats rows based on settings or defaults
        return $node;
    }

}
