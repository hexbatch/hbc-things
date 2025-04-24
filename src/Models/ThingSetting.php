<?php

namespace Hexbatch\Things\Models;




use Hexbatch\Things\Helpers\CalculatedSettings;
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
 * @property int created_at_ts
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

    const int DEFAULT_DATA_BYTE_ROWS_LIMIT = 100;
    const int DEFAULT_TREE_NODE_LIMIT = 100;
    const int DEFAULT_BACKOFF_DATA_POLICY = 100;
    const int DEFAULT_TREE_LIMIT = 100;


    public static function getTreeOverflowLimit(?IThingAction $action = null, ?IThingOwner $owner = null,int &$backoff_policy = 0)
    : int
    {

        /** @var static[] $rules */
        $rules = static::buildSetting(action: $action, owner: $owner, highest_rank: true, has_tree_limit: true)
            ->get();

        $backoff_policy = static::DEFAULT_BACKOFF_DATA_POLICY;
        if (count($rules) === 0) {
            return static::DEFAULT_TREE_LIMIT;
        }

        $min = 100000000;

        foreach ($rules as $rule) {
            if ($rule->tree_limit < $min) {
                $min = $rule->tree_limit;
                if ($rule->backoff_data_policy) {
                    $backoff_policy = $rule->backoff_data_policy;
                }
            }
        }

        return $min;
    }

    public static function getTreeNodeLimit(?IThingAction $action = null, ?IThingOwner $owner = null,?Thing $thing = null, int&$backoff_policy = 0)
    : int
    {

        /** @var static[] $rules */
        $rules = static::buildSetting(action: $action, owner: $owner, thing: $thing, highest_rank: true, has_descendants_limit: true)
            ->get();

        $backoff_policy = static::DEFAULT_BACKOFF_DATA_POLICY;
        if (count($rules) === 0) {
            return static::DEFAULT_TREE_NODE_LIMIT;
        }

        $min = 100000000;

        foreach ($rules as $rule) {
            if ($rule->descendants_limit < $min) {
                $min = $rule->descendants_limit;
                if ($rule->backoff_data_policy) {
                    $backoff_policy = $rule->backoff_data_policy;
                }
            }
        }

        return $min;
    }

    public static function getDataLimit(?IThingAction $action = null, ?IThingOwner $owner = null,?Thing $thing = null,&$backoff_policy = 0)
    : int
    {
        /** @var static[] $rules */
        $rules = static::buildSetting(action: $action, owner: $owner, thing: $thing, highest_rank: true, has_data_byte_row_limit: true)
            ->get();

        $backoff_policy = static::DEFAULT_BACKOFF_DATA_POLICY;
        if (count($rules) === 0) {
            return static::DEFAULT_DATA_BYTE_ROWS_LIMIT;
        }

        $min = 100000000;

        foreach ($rules as $rule) {
            if ($rule->data_byte_row_limit < $min) {
                $min = $rule->data_byte_row_limit;
                if ($rule->backoff_data_policy) {
                    $backoff_policy = $rule->backoff_data_policy;
                }
            }
        }

        return $min;
    }

    public static function isTreeOverflow(?IThingAction $action, ?IThingOwner $owner,int &$limit , int &$backoff_policy)
    : bool
    {
        $number_current_trees = Thing::buildThing(action_type_id: $action?->getActionId(), action_type: $action?->getActionType(),
            owner_type_id: $owner?->getOwnerId(), owner_type: $owner?->getOwnerType(), is_root: true)->count();

        $limit = static::getTreeOverflowLimit(action: $action,owner: $owner,backoff_policy: $backoff_policy);
        return ($limit <= $number_current_trees );
    }

    public static function isNodeOverflow(Thing $thing,int &$limit , int &$backoff_policy)
    : bool
    {
        $number_of_nodes = Thing::buildThing(me_id: $thing->id,include_my_descendants: true)->count();
        $limit = static::getTreeNodeLimit(action: $thing->getAction(),owner: $thing->getOwner(),thing: $thing,backoff_policy: $backoff_policy);
        return ($limit <= $number_of_nodes );
    }


    public static function makeStatFromSettings(Thing $thing,?CalculatedSettings $settings) : ThingStat {
        $node = ThingStat::makeStatsWhileBuilding($thing);
        $node->stat_limit_data_byte_rows = $settings?->getDataLimit();
        $node->stat_limit_descendants = $settings?->getDescendantLimit();
        $node->stat_backoff_data_policy = $settings?->getBackoffDataPolicy();
        $node->save();
        return $node;
    }

    const int REPEAT_OFFENDER_POLICY = 100;
    public static function getBackoffSeconds(int $policy, int $repeat) : int {
        return $policy + (min($repeat-1,0) * static::REPEAT_OFFENDER_POLICY);
    }


    public static function buildSetting(
        ?int    $me_id = null,
        ?IThingAction $action = null, ?IThingOwner $owner = null,?Thing $thing = null,
        bool $highest_rank = false,
        ?bool $has_descendants_limit = null,
        ?bool $has_data_byte_row_limit = null,
        ?bool $has_tree_limit = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  ThingSetting::select('thing_settings.*')
            ->selectRaw(" extract(epoch from  thing_settings.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  thing_settings.updated_at) as updated_at_ts")
        ;

        if ($me_id) {
            $build->where('thing_settings.id',$me_id);
        }

        if ($action) {
            $build->where('thing_settings.action_type',$action->getActionType());
            $build->where('thing_settings.action_type_id',$action->getActionId());
        }

        if ($owner) {
            $build->where('thing_settings.owner_type',$owner->getOwnerType());
            $build->where('thing_settings.owner_type_id',$owner->getOwnerId());
        }

        if ($thing) {
            $build->where('thing_settings.setting_about_thing_id',$thing->id);
        }

        if ($has_descendants_limit) {
            $build->where('thing_settings.descendants_limit','>',0);
        }

        if ($has_data_byte_row_limit) {
            $build->where('thing_settings.data_byte_row_limit','>',0);
        }

        if ($has_tree_limit) {
            $build->where('thing_settings.tree_limit','>',0);
        }


        if ($highest_rank) {
            $build->withExpression('ranker',
                /** @param \Illuminate\Database\Query\Builder $query */
                function ($query)
                {
                    $query->from('thing_settings as t')->selectRaw("t.id, max(t.setting_rank) OVER () as max_rank");
                }
            )->join('ranker', 'ranker.id', '=', 'thing_settings.id')
            ->whereRaw('thing_settings.setting_rank = ranker.max_rank')
            ;
        }

        return $build;
    }

}
