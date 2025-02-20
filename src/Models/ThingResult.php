<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Jobs\SendResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * this stores the user getting back the api info, as well as the outgoing and incoming remotes
 * when an api call is made, a row is created here to handle the results being printed out, or the polling, or pushing to a url
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int owner_thing_id
 * @property int result_http_status
 * @property ArrayObject result_response
 *
 *  @property string created_at
 *  @property string updated_at
 *
 *  @property Thing thing_owner
 *  @property ThingResultCallback[] result_callbacks
 */
class ThingResult extends Model
{

    protected $table = 'thing_results';
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
        'result_response' => AsArrayObject::class,
    ];


    public function thing_owner() : BelongsTo {
        return $this->belongsTo(Thing::class,'owner_thing_id','id');
    }

    public function result_callbacks() : HasMany {
        return $this->hasMany(ThingResultCallback::class,'thing_result_id','id')
            /** @uses ThingResultCallback::result_owner() */
            ->with('result_owner');
    }



    public function dispatchResult() : void {
        foreach ($this->result_callbacks as $callback) {
            SendResult::dispatch($callback);
        }
    }



}
