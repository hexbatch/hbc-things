<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;



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
    ];

    public static function createFromException(\Exception $e) : ThingError {
        $node = new ThingError();
        $node->thing_error_code = $e->getCode();
        $node->thing_error_line = $e->getLine();
        $node->thing_error_file = $e->getLine();
        $node->thing_code_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: true);
        $node->hbc_version = \Hexbatch\Things\Helpers\ThingUtilities::getVersionAsString(for_lib: false);
        $node->thing_error_message = $e->getMessage();
        $node->thing_error_trace = $e->getTrace();

        $previous_errors = [];
        while($prev = $e->getPrevious()) {
            $x = [];
            $x['message'] = $prev->getMessage();
            $x['code'] = $prev->getCode();
            $x['line'] = $prev->getLine();
            $x['file'] = $prev->getFile();
            $x['trace'] = $prev->getTrace();
            $previous_errors[] = $x;
        }
        $node->thing_previous_errors = $previous_errors;
        $node->save();
        return $node;
    }


}
