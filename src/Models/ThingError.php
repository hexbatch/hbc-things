<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int thing_error_code
 * @property int thing_error_line
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

    public static function createFromException(\Exception $exception, ?array $related_tags = null) : ?ThingError {
        try {
            $node = new ThingError();
            $node->thing_error_code = $exception->getCode();
            $node->thing_error_line = $exception->getLine();
            $node->thing_error_file = $exception->getFile();
            $node->thing_code_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: true);
            $node->hbc_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: false);
            $node->thing_error_message = $exception->getMessage();
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
            $node->save();
            return $node;
        } catch (\Exception $f) {
            Log::error("[createFromException] Stopping recursion error: ". $f->getMessage());
            return null;
        }
    }


}
