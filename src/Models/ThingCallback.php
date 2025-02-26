<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Models\Enums\TypeOfThingCallback;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackStatus;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int owning_hooker_id
 * @property int callback_callplate_id
 * @property int callback_error_id
 *
 * @property int callback_http_code
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property string ref_uuid
 *
 * @property string  callback_outgoing_header
 * @property string  callback_url
 * @property string  callback_class
 * @property string  callback_function
 * @property string  callback_event
 * @property ArrayObject callback_outgoing_data
 * @property ArrayObject callback_incoming_data
 * @property TypeOfThingCallback thing_callback_type
 * @property TypeOfThingCallbackStatus thing_callback_status
 *
 * @property string callback_run_at
 * @property string created_at
 * @property string modified_at
 *
 * @property ThingHooker callback_owning_hooker
 *
 */
class ThingCallback extends Model
{
    use ThingOwnerHandler;

    protected $table = 'thing_callplates';
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
        'callback_outgoing_data' => AsArrayObject::class,
        'callback_incoming_data' => AsArrayObject::class,
        'thing_callback_type' => TypeOfThingCallback::class,
        'thing_callback_status' => TypeOfThingCallbackStatus::class,
    ];

    public function callback_owning_hooker() : BelongsTo {
        return $this->belongsTo(ThingHooker::class,'owning_hooker_id','id');
    }

    public static function buildCallback(
        ?int                  $id = null,
        ?int                  $hooker_id = null,
        ?int                  $callplate_id = null,
    )
    : Builder
    {

        /**
         * @var Builder $build
         */
        $build =  ThingCallback::select('thing_callbacks.*')
            ->selectRaw(" extract(epoch from  thing_callbacks.created_at) as created_at_ts,  extract(epoch from  thing_callbacks.updated_at) as updated_at_ts")
        ;

        if ($id) {
            $build->where('thing_callbacks.id',$id);
        }

        if ($hooker_id) {
            $build->where('thing_callbacks.owning_hooker_id',$hooker_id);
        }

        if ($callplate_id) {
            $build->where('thing_callbacks.callback_callplate_id',$callplate_id);
        }




        /** @uses ThingCallback::callback_owning_hooker() */
        $build->with('callback_owning_hooker');


        return $build;
    }


    public function runCallback() :void  {
        if ($this->thing_callback_type === TypeOfThingCallback::DISABLED) {
            return;
        }

        //todo get the data from the thing action, and root thing_constant_data, combine with the data , put in ref for callback
        $data = [];
        //todo fill in events (always filters) and php code calls

        //todo make headers process header placeholders
        $headers = [];

        try {
            //todo rm
            $process_part = [
                'ref' => $this->ref_uuid,
            ];

            /** @var Response|null $response */
            $response = null;


            switch ($this->thing_callback_type) {
                case TypeOfThingCallback::HTTP_GET:
                    $response = Http::get($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_POST:
                    $response = Http::post($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_PUT:
                    $response = Http::put($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_PATCH:
                    $response = Http::patch($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_DELETE:
                    $response = Http::delete($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_POST_FORM:
                    $response = Http::asForm()->post($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_PUT_FORM:
                    $response = Http::asForm()->put($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::HTTP_PATCH_FORM:
                    $response = Http::asForm()->patch($this->callback_url, [
                        'process' => $process_part,
                        'data' => $data
                    ]);
                    break;
                case TypeOfThingCallback::DISABLED:
                    throw new \LogicException("Was disabled");
                case TypeOfThingCallback::MANUAL:
                    throw new \Exception('To be implemented');

                case TypeOfThingCallback::CODE:
                    throw new \Exception('To be implemented');
                case TypeOfThingCallback::EVENT_CALL:
                    throw new \Exception('To be implemented');
            }




            if (!$response) {
                throw new HbcThingException("Response is null, which probably means a case was missed");
            }
            $this->callback_http_code = $response->status();
            if ($response->successful()) {
                $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_SUCCESSFUL;
            } else {
                $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_ERROR;
            }
        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->save();
            $this->callback_owning_hooker->maybeCallbacksDone();
        }


    }

}
