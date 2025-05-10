<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Helpers\CallResponse;
use Hexbatch\Things\Interfaces\ICallResponse;
use Hexbatch\Things\Interfaces\IHookCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use TorMorten\Eventy\Facades\Eventy;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int owning_hook_id
 * @property int source_thing_id
 * @property int callback_error_id
 *
 *
 * @property int callback_http_code
 * @property bool is_signalling_when_done
 *
 * @property string ref_uuid
 *
 * @property ArrayObject callback_outgoing_data
 * @property ArrayObject callback_incoming_data
 * @property ArrayObject  callback_outgoing_header
 * @property TypeOfCallbackStatus thing_callback_status
 *
 * @property string callback_run_at
 * @property string created_at
 * @property string modified_at
 *
 * @property string batch_string_id
 *
 * @property ThingHook owning_hook
 * @property Thing thing_source
 * @property ThingError|null callback_error
 *
 */
class ThingCallback extends Model
{


    protected $table = 'thing_callbacks';
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
        'is_signalling_when_done' => 'boolean',
        'callback_outgoing_data' => AsArrayObject::class,
        'callback_incoming_data' => AsArrayObject::class,
        'callback_outgoing_header' => AsArrayObject::class,
        'thing_callback_status' => TypeOfCallbackStatus::class,
    ];

    public function owning_hook() : BelongsTo {
        return $this->belongsTo(ThingHook::class,'owning_hook_id','id');
    }

    public function thing_source() : BelongsTo {
        return $this->belongsTo(Thing::class,'source_thing_id','id');
    }

    public function callback_error() : BelongsTo {
        return $this->belongsTo(ThingError::class,'callback_error_id','id');
    }



    public static function buildCallback(
        ?int $me_id = null,
        ?int $hook_id = null,
        ?int $thing_id = null,
    )
    : Builder
    {
        /**
         * @var Builder $build
         */
        $build =  ThingCallback::select('thing_callbacks.*')
            ->selectRaw(" extract(epoch from  thing_callbacks.created_at) as created_at_ts,  extract(epoch from  thing_callbacks.updated_at) as updated_at_ts")
        ;

        if ($me_id) {
            $build->where('thing_callbacks.id',$me_id);
        }

        if ($hook_id) {
            $build->where('thing_callbacks.owning_hook_id',$hook_id);
        }

        if ($thing_id) {
            $build->where('thing_callbacks.source_thing_id',$thing_id);
        }


        /** @uses ThingCallback::owning_hook() */
        $build->with('owning_hook');


        return $build;
    }


    /**
     * Retrieve the model for a bound value.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $ret = null;
        try {
            if ($field) {
                $ret = $this->where($field, $value)->first();
            } else {
                if (ctype_digit($value)) {
                    $ret = $this->where('id', $value)->first();
                } else {
                    $ret = $this->where('ref_uuid', $value)->first();
                }
            }
            if ($ret) {
                $ret = static::buildCallback(me_id:$ret->id)->first();
            }
        } finally {
            if (empty($ret)) {
                throw new \RuntimeException(
                    "Did not find callback with $field $value"
                );
            }
        }
        return $ret;
    }

    protected function getOutgoingDataAsArray(?ThingHook $hook = null, ?Thing $thing = null) : array {

        if (!$hook) {$hook = $this->owning_hook;}
        /** @uses static::thing_source() */
        if (!$thing) {$thing = $this->thing_source;}

        $action = $thing->getAction();

        $uuid_data = [
            'callback' => $this->ref_uuid,
            'hook' => $hook->ref_uuid,
            'thing' => $thing->ref_uuid,
            'action' => $action?->getActionRef()??null,
        ];

        $action_constants = $action?->getInitialConstantData()??[];

        $action_data = [];
        if ($action?->isActionComplete()) {
            $action_data = $action->getActionResult();
        }

        $template_data = $hook->hook_data_template?->getArrayCopy()??[];

        return array_merge($template_data,$uuid_data,$action_constants,$action_data);
    }

    /**
     * @throws \Exception
     */
    protected function callCode()
    :ICallResponse
    {
        if (class_exists($this->owning_hook->address)) {
            $interfaces = class_implements($this->owning_hook->address);

            if (!isset($interfaces['Hexbatch\Things\Interfaces\IHookCode'])) {
                throw new HbcThingException($this->owning_hook->address." does not implement IHookCode");
            }
        } else {
            throw new HbcThingException($this->owning_hook->address." is not a class, is this correct namespace?");
        }

        $code = 0;
        try {
            /** @var IHookCode|string $callable */
            $callable = $this->owning_hook->address;
            $ret = $callable::runHook(header: $this->callback_outgoing_header??[],body: $this->callback_outgoing_data??[],return_int: $code  );

        } catch (\Exception|\Error $e) {
            Log::warning("Got error when calling $callable :".$e->getMessage());
            throw $e;
        }

        return new CallResponse(code: $code,successful: true,data: $ret);
    }

    /**
     * @throws \Exception
     */
    protected function callEvent()
    : ICallResponse
    {
        if (!$this->owning_hook->address) {
            throw new HbcThingException("Callback event name not defined");
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $ret = Eventy::filter($this->owning_hook->address, array_values($this->callback_outgoing_data?->getArrayCopy()??[]));
        $success = true;
        $code = 200;
        if (empty($ret) ||(is_array($ret) && count($ret) === 0)) {
            $success = false;
            $code = 500;
        }
        return new CallResponse(code: $code,successful: $success,data: $ret);
    }




    protected function calculateData(array $source,?ThingHook $hook = null, ?Thing $thing = null) : array
    {
        if (!$hook) {$hook = $this->owning_hook;}
        /** @uses static::thing_source() */
        if (!$thing) {$thing = $this->thing_source;}

        $found_data = $this->getOutgoingDataAsArray(hook: $hook,thing: $thing);

        if (empty($found_data)) {
            $prep = $found_data;
        } else {
            $prep = [];
            foreach ($source as $key => $value) {
                if ($value === null && isset($found_data[$key])) {
                    $prep[$key] = $found_data[$key];
                } else {
                    $prep[$key] = $value;
                }
            }
        }

        foreach ($prep as $p_key => $p_val) {
            if ($p_val === null) { unset($prep[$p_key]);}
        }


        return $prep;
    }



    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    protected function callRemote() : ICallResponse {
        $params = $this->callback_outgoing_data?->getArrayCopy()??[];

        $headers = $this->callback_outgoing_header?->getArrayCopy()??[];
        $response = null;
        switch ($this->owning_hook->hook_callback_type) {

            case TypeOfCallback::DISABLED:
            case TypeOfCallback::CODE:
            case TypeOfCallback::EVENT_CALL:
            {
                throw new HbcThingException("Callback type is not a remote call");
            }

            case TypeOfCallback::HTTP_GET:
                $response = Http::withHeaders($headers)->get($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_POST:
                $response = Http::withHeaders($headers)->post($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT:
                $response = Http::withHeaders($headers)->put($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH:
                $response = Http::withHeaders($headers)->patch($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_DELETE:
                $response = Http::withHeaders($headers)->delete($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_POST_FORM:
                $response = Http::asForm()->withHeaders($headers)->post($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT_FORM:
                $response = Http::asForm()->withHeaders($headers)->put($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH_FORM:
                $response = Http::asForm()->withHeaders($headers)->patch($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_DELETE_FORM:
                $response = Http::asForm()->withHeaders($headers)->delete($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_POST_JSON:
                $response = Http::asJson()->withHeaders($headers)->post($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT_JSON:
                $response = Http::asJson()->withHeaders($headers)->put($this->owning_hook->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH_JSON:
                $response = Http::asJson()->withHeaders($headers)->patch($this->owning_hook->address,$params);
                break;
            case TypeOfCallback::HTTP_DELETE_JSON:
                $response = Http::asJson()->withHeaders($headers)->delete($this->owning_hook->address,$params);
                break;
        }


        switch ($this->owning_hook->hook_callback_type) {
            case TypeOfCallback::HTTP_POST_JSON:
            case TypeOfCallback::HTTP_PUT_JSON:
            case TypeOfCallback::HTTP_PATCH_JSON:
            case TypeOfCallback::HTTP_DELETE_JSON:
                $data = $response->json();
                break;
            default: {
                if (!$response) {$data = null;}
                else {
                    $string_ret = trim($response->body());
                    if ($string_ret) {
                        $maybe_was_json = json_decode($string_ret, true);
                        if ($maybe_was_json) {
                            $data = $maybe_was_json;
                    } else {
                            $data = explode("\n",$string_ret);
                    }
                    } else {
                        $data = null;
                    }
                }

            }
        }

        return new CallResponse(code: $response->getStatusCode(),successful: $response->successful(),data: $data);
    }

    public function runCallback() :void  {
        if ($this->owning_hook->hook_callback_type === TypeOfCallback::DISABLED) {
            return;
        }



        try {

            $response = null;
            switch ($this->owning_hook->hook_callback_type) {

                case TypeOfCallback::DISABLED:
                {
                    return;
                }

                case TypeOfCallback::CODE:
                {
                    $response = $this->callCode();
                    break;
                }
                case TypeOfCallback::EVENT_CALL:
                {
                    $response = $this->callEvent();
                    break;
                }

                case TypeOfCallback::HTTP_GET:
                case TypeOfCallback::HTTP_POST:
                case TypeOfCallback::HTTP_PUT:
                case TypeOfCallback::HTTP_PATCH:
                case TypeOfCallback::HTTP_DELETE:
                case TypeOfCallback::HTTP_POST_FORM:
                case TypeOfCallback::HTTP_PUT_FORM:
                case TypeOfCallback::HTTP_PATCH_FORM:
                case TypeOfCallback::HTTP_DELETE_FORM:
                case TypeOfCallback::HTTP_POST_JSON:
                case TypeOfCallback::HTTP_PUT_JSON:
                case TypeOfCallback::HTTP_PATCH_JSON:
                case TypeOfCallback::HTTP_DELETE_JSON:
                {
                    $response =  $this->callRemote();
                    break;
                }
            }

            if (!$response) {
                throw new HbcThingException("Response is null, which probably means a case was missed");
            }
            $this->callback_http_code = $response->getCode();
            if ($response->isSuccessful()) {
                $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_SUCCESSFUL;
            } else {
                $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_ERROR;
            }
            $this->callback_incoming_data = $response->getData();

            if ($this->owning_hook->is_blocking) {
                if ($this->owning_hook->is_after) {
                    $this->thing_source->thing_parent?->getAction()->addDataBeforeRun(data: $this->callback_incoming_data?->getArrayCopy()??[]);
                } else {
                    $this->thing_source->getAction()->addDataBeforeRun(data: $this->callback_incoming_data?->getArrayCopy()??[]);
                }
            }

            if ($this->is_signalling_when_done) {
                $this->thing_source->signal_parent();
            }

        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->callback_run_at = Carbon::now()->timezone('UTC')->toDateTime();
            $this->save();
        }

        //todo if this is a manual, and the address is empty, then somehow start the queue again with the things after it

    }


    public static function createFromHook(ThingHook $hook,Thing $thing) : ThingCallback {
        $node = new ThingCallback();
        $node->owning_hook_id = $hook->id;
        $node->source_thing_id = $thing->id;
        $node->save(); //save and refresh first time to get uuid
        $node->refresh();
        $node->callback_outgoing_data = $node->calculateData(source: $hook->hook_data_template?->getArrayCopy()??[], hook: $hook, thing: $thing);
        $node->callback_outgoing_header = $node->calculateData(source: $hook->hook_header_template?->getArrayCopy()??[],hook: $hook, thing: $thing);

        $node->save();
        return $node;
    }

}
