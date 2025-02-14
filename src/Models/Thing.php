<?php

namespace Hexbatch\Things\Models;



use Exception;
use Hexbatch\Things\Jobs\RunThing;
use Hexbatch\Things\Models\Enums\TypeOfThingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 *
 * Summary :


 * thing is marked as done when all children done, and there is no pagination id
 *
 * When there is a full page for a container, the parent makes a cursor row in the data
 * A new child thing is made for using that next page of data , the child may start later according to the backoff
 * the child results combined or_all to the parent. Empty data for the last cursor is child success too.
 * the thing parent cannot complete until all the new children return success.

 *
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int parent_thing_id
 * @property int after_thing_id
 * @property string action_type
 * @property int action_type_id
 *
 *
 *
 * @property int thing_rank
 * @property int debugging_breakpoint
 * @property bool is_waiting_on_hook
 *
 * @property string thing_start_at
 * @property string thing_invalid_at
 * @property string process_started_at
 * @property string ref_uuid
 * @property TypeOfThingStatus thing_status
 *
 * @property string created_at
 * @property int created_at_ts
 * @property string updated_at
 *
 * @property ThingResult thing_result
 * @property Thing thing_parent
 * @property Thing[]|\Illuminate\Database\Eloquent\Collection thing_children
 */
class Thing extends Model
{

    protected $table = 'things';
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
        'thing_status' => TypeOfThingStatus::class
    ];



    public function thing_children() : HasMany {
        return $this->hasMany(Thing::class,'parent_thing_id','id');
    }

    public function thing_parent() : BelongsTo {
        return $this->belongsTo(Thing::class,'parent_thing_id','id');
    }

    public function thing_result() : HasOne {
        return $this->hasOne(ThingResult::class,'owner_thing_id','id')
            /** @uses ThingResult::thing_error() */
            ->with('thing_error');
    }




    public function isComplete() : bool {
        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS || $this->thing_status === TypeOfThingStatus::THING_ERROR) {
            return true;
        }
        return false;
    }


    public function isSuccess() : bool {

        if ($this->thing_status === TypeOfThingStatus::THING_SUCCESS) {
            return true;
        }
        return false;
    }



    /**
     * @return Thing[]
     */
    public function getLeaves() {
        return [];
        //todo get the leaves
    }

    public function changeStatusAll(TypeOfThingStatus $status) {
        //todo change for this and all descendents
    }

    public function setProcessedAt() {
        //todo update the process_started_at to now
    }

    public function setException(Exception $e) {
        //todo set this exception to the thing result (make one if not there already)
        $hex = ThingError::createFromException($e);
    }



    /**
     * @throws Exception
     */
    public function runThing() :void {
        try {
            DB::beginTransaction();
            //todo stuff
            $this->thing_status = TypeOfThingStatus::THING_SUCCESS;

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->thing_status = TypeOfThingStatus::THING_ERROR;
            $this->save();
            throw $e;
        }
    }

    public function maybeQueueParent() : bool {
        foreach ($this->thing_children as $thang) {
            if (!$thang->isComplete()) {return false;}
        }
        if ($this->thing_parent) {
            RunThing::dispatch($this->thing_parent);
        }
        return true;
    }



    public function pushLeavesToJobs() {
        if ($this->thing_status !== TypeOfThingStatus::THING_BUILDING) {
            throw new LogicException("Cannot push what is already built");
        }
        $this->changeStatusAll(TypeOfThingStatus::THING_PENDING);
        foreach ($this->getLeaves() as $leaf) {
            RunThing::dispatch($leaf);
        }

    }


    public static function makeThingTree(
                                           $action,
                                            int $start_at_ts = null,
                                            int $invalid_at_ts = null
    )
    : Thing {


        $root = new Thing();

        $root->save();
        static::makeAction($root,$action);
        return static::getThing(id:$root->id);

    }



    public static function makeAction(Thing $parent_thing,$action) : Thing {

        $node = new Thing();
        $node->parent_thing_id = $parent_thing->id;

        $node->save();
        foreach ($action->getActionChildren() as $action) {
            static::makeAction($node,$action);
        }
        return $node;

    }


    public static function getThing(
        ?int $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
    )
    : Thing
    {
        $ret = static::buildThing(id:$id, action_type_id: $action_type_id,action_type: $action_type)->first();

        if (!$ret) {
            $arg_types = [];
            $arg_vals = [];
            if ($id) { $arg_types[] = 'id'; $arg_vals[] = $id;}
            if ($action_type) { $arg_types[] = 'Action type'; $arg_vals[] = $action_type;}
            if ($action_type_id) { $arg_types[] = 'Action id'; $arg_vals[] = $action_type_id;}
            $arg_val = implode('|',$arg_vals);
            $arg_type = implode('|',$arg_types);
            throw new LogicException("Could not find thing via $arg_type : $arg_val");
        }
        return $ret;
    }

    public static function buildThing(
        ?int    $id = null,
        ?int    $action_type_id = null,
        ?string $action_type = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  Thing::select('things.*')
            ->selectRaw(" extract(epoch from  things.created_at) as created_at_ts,  extract(epoch from  things.updated_at) as updated_at_ts")
        ;

        if ($id) {
            $build->where('things.id',$id);
        }

        if ($action_type) {
            $build->where('things.action_type_id',$action_type);
        }

        if ($action_type_id) {
            $build->where('things.action_type',$action_type_id);
        }





        /**
         * @uses Thing::thing_collection()
         */
        $build->with('thing_collection');

        /**
         * @uses Thing::thing_result(),Thing::thing_parent(),Thing::thing_children()
         */
        $build->with('thing_result','thing_parent','thing_children');

        return $build;
    }

}
