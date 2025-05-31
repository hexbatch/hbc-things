<?php

namespace Hexbatch\Things\Models;


use App\Exceptions\HexbatchCoreException;
use ArrayObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int thing_error_code
 * @property int thing_ref_code
 * @property int thing_error_line
 * @property string thing_error_url
 * @property string thing_code_version
 * @property string hbc_version
 * @property string thing_error_message
 * @property ArrayObject thing_error_trace
 * @property ArrayObject thing_previous_errors
 * @property ArrayObject related_tags
 * @property string thing_error_file
 * @property string ref_uuid
 *
 * @property string created_at
 * @property string updated_at
 *
 * @property Thing[] error_things
 * @property ThingCallback[] error_callbacks
 */
class ThingError extends Model
{

    protected $table = 'thing_errors';
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
        'thing_error_trace' => AsArrayObject::class,
        'thing_previous_errors' => AsArrayObject::class,
        'related_tags' => AsArrayObject::class,
    ];

    public function error_things() : HasMany {
        return $this->hasMany(Thing::class,'thing_error_id','id');
    }

    public function error_callbacks() : HasMany {
        return $this->hasMany(ThingCallback::class,'callback_error_id','id');
    }


    public static function buildError(
        ?int    $me_id = null,
        ?string $uuid = null,
        array   $error_ids = [],
        bool    $do_relations = null,

    )
    : Builder
    {
        /**
         * @var Builder $build
         */
        $build = ThingError::select('thing_errors.*')
            ->selectRaw(" extract(epoch from  thing_errors.created_at) as created_at_ts")
            ->selectRaw("extract(epoch from  thing_errors.updated_at) as updated_at_ts");

        if ($me_id) {
            $build->where('thing_errors.id', $me_id);
        }

        if (count($error_ids)) {
            $build->whereIn('thing_errors.id', $error_ids);
        }

        if ($uuid) {
            $build->where('thing_errors.ref_uuid', $uuid);
        }

        if ($do_relations) {
            /**
             * @uses static::error_things(),static::error_callbacks()
             */
            $build->with('error_things', 'error_callbacks');
        }

        return $build;
    }

    public static function createFromException(\Exception $exception, ?array $related_tags = null) : ?ThingError {
        try {
            $node = new ThingError();
            $extra = '';
            $node->thing_error_code = $exception->getCode()??0;
            if (!ctype_digit((string)$node->thing_error_code)) {
                $extra = $node->thing_error_code. ' '; //some exceptions throw non-numeric codes
                $node->thing_error_code = 0;

            }
            $node->thing_error_line = $exception->getLine();
            $node->thing_error_file = $exception->getFile();
            $node->thing_code_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: true);
            $node->hbc_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: false);
            $node->thing_error_message = $extra. $exception->getMessage();
            $node->thing_error_trace = $exception->getTraceAsString();

            $previous_errors = [];
            $prev = $exception;
            while ($prev = $prev->getPrevious()) {
                $x = [];
                $x['message'] = $prev->getMessage();
                $x['code'] = $prev->getCode();
                $x['line'] = $prev->getLine();
                $x['file'] = $prev->getFile();
           //     $x['trace'] = $prev->getTrace();
                $previous_errors[] = $x;
            }
            $node->thing_previous_errors = $previous_errors;

            if ($related_tags !== null) {
                $node->related_tags = $related_tags;
            }

            if ($exception instanceof HexbatchCoreException) {
                $node->thing_ref_code = $exception->getRefCode();
                $node->thing_error_url = $exception->getRefCodeUrl();
            }
            $node->save();
            return $node;
        } catch (\Exception $f) {
            Log::error("[createFromException] Stopping recursion error: ". $f->getMessage());
            return null;
        }
    }


}
