<?php

namespace Hexbatch\Things\Models;


use App\Exceptions\RefCodes;
use App\Helpers\Utilities;
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
 * @property float thing_code_version
 * @property string thing_error_message
 * @property ArrayObject thing_error_trace
 * @property string thing_error_file
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
    ];

    public static function createFromException(\Exception $e) : ThingError{
        $node = new ThingError();
        //todo fill in the props
        return $node;
    }
    public function getErrorJson() : array {

        return [
            'type' => $this->getRefCodeUrl(),
            'version' => Utilities::getVersionString(),
            'instance' => $this->thing_error_code,
            'message' => $this->thing_error_message,
            'file' => $this->thing_error_file,
            'line' => $this->thing_error_line,
            'trace' => $this->thing_error_trace->getArrayCopy(),

        ];
    }

    public function getRefCodeUrl(): ?string
    {
        return (RefCodes::URLS[$this->thing_error_code]??null);
    }

}
