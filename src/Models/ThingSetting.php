<?php

namespace Hexbatch\Things\Models;




use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

/*
 * thing is marked as done when all children done, and there is no pagination id
 */
/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int setting_about_thing_id
 * @property string setting_action_type
 * @property int setting_action_type_id

 * @property int setting_rank
 * @property int thing_pagination_size
 * @property int thing_pagination_limit
 * @property int thing_depth_limit
 * @property int thing_rate_limit
 * @property int thing_backoff_page_policy
 * @property int thing_backoff_rate_policy
 * @property int thing_json_size_limit
 *
 *
 * @property string created_at
 * @property string updated_at
 *

 */
class ThingSetting extends Model
{

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

    const DEFAULT_PAGINATION_SIZE = 100;
    const DEFAULT_PAGINATION_LIMIT = 100;
    const DEFAULT_DEPTH_LIMIT = 100;
    const DEFAULT_BACKOFF_PAGE_POLICY = 100;
    const DEFAULT_BACKOFF_RATE_POLICY = 100;
    const DEFAULT_RATE_LIMIT = 10000;
    const DEFAULT_JSON_SIZE_LIMIT = 10000;


    public function checkSettingOwnership(Thing $thing) : ?ThingSettingCluster{
        /** @var ThingSettingCluster|null $bonding */
        return ThingSettingCluster::where('setting_cluster_thing_id',$thing->id)->where('owning_setting_id',$this->id)->first();
    }

    public function getName() : string {
        return "Setting Id # $this->id";
    }


}
