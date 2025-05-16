<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;

use Hexbatch\Things\Enums\TypeOfOwnerGroup;
use Hexbatch\Things\Exceptions\HbcThingException;

use Hexbatch\Things\Helpers\CallResponse;
use Hexbatch\Things\Interfaces\ICallResponse;
use Hexbatch\Things\Interfaces\IHookCode;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\OpenApi\Callbacks\CallbackSearchParams;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as CodeOf;
use TorMorten\Eventy\Facades\Eventy;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int owning_hook_id
 * @property int source_thing_id
 * @property int callback_error_id
 * @property int source_shared_callback_id
 * @property int manual_alert_callback_id
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
 *
 * @property ThingHook owning_hook
 * @property Thing thing_source
 * @property ThingError|null callback_error
 * @property ThingCallback|null alert_target
 * @property ThingCallback|null alerted_by
 * @property ThingCallback|null shared_callback_source
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
    protected $fillable = [
        'is_signalling_when_done'
    ];

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

    public function alert_target() : BelongsTo {
        return $this->belongsTo(ThingCallback::class,'manual_alert_callback_id','id');
    }

    public function shared_callback_source() : BelongsTo {
        return $this->belongsTo(ThingCallback::class,'source_shared_callback_id','id');
    }

    public function alerted_by() : HasOne {
        return $this->hasOne(ThingCallback::class,'manual_alert_callback_id','id');
    }


    /**
     * @param TypeOfCallbackStatus[] $status_array
     */
    public static function buildCallback(
        ?int                  $me_id = null,
        ?int                  $hook_id = null,
        ?int                  $thing_id = null,
        ?IThingOwner          $owner_group = null,
        ?TypeOfOwnerGroup     $group_hint = null,
        array                 $status_array = [],
        ?bool                 $has_alert = null,
        ?int                  $alerted_by_callback_id = null,
        ?CallbackSearchParams $params = null
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

        if ($alerted_by_callback_id) {
            $build->where('thing_callbacks.manual_alert_callback_id',$alerted_by_callback_id);
        }

        if (count($status_array) ) {
            $build->whereIn('thing_callbacks.thing_callback_status',$status_array);
        }

        if ($thing_id) {
            $build->where('thing_callbacks.source_thing_id',$thing_id);
        }

        if ($has_alert !== null) {
            if ($has_alert) {
                $build->whereNotNull('thing_callbacks.manual_alert_callback_id');
            } else {
                $build->whereNull('thing_callbacks.manual_alert_callback_id');
            }
        }


        if($owner_group && $group_hint) {
            $build->join('thing_hooks as my_hook','thing_callbacks.owning_hook_id','=','my_hook.id');

            $owner_group->setReadGroupBuilding(builder: $build,connecting_table_name: 'my_hook',
                connecting_owner_type_column: 'owner_type',connecting_owner_id_column: 'owner_type_id',hint: $group_hint);
        }


        if ($params) {
            if ($params->getUuid()) {
                $build->where('thing_callbacks.ref_uuid',$params->getUuid());
            }

            if ($params->getCodeRangeMin() ) {
                $build->where('thing_callbacks.callback_http_code','>=',$params->getCodeRangeMin());
            }

            if ($params->getCodeRangeMax() ) {
                $build->where('thing_callbacks.callback_http_code','<=',$params->getCodeRangeMax());
            }

            if ($params->getStatus()) {
                $build->where('thing_callbacks.thing_callback_status',$params->getStatus());
            }

            if ($params->getRanAtMin() ) {
                $build->where('thing_callbacks.callback_run_at','>=',$params->getRanAtMin());
            }

            if ($params->getRanAtMax() ) {
                $build->where('thing_callbacks.callback_run_at','<=',$params->getRanAtMax());
            }

            if ($params->getCreatedAtMin() ) {
                $build->where('thing_callbacks.created_at','>=',$params->getCreatedAtMin());
            }

            if ($params->getCreatedAtMax() ) {
                $build->where('thing_callbacks.created_at','<=',$params->getCreatedAtMax());
            }

            if ($params->getHookUuid() || $params->getOwnerId() || $params->getOwnerType()) {
                $build->join('things as param_thing','param_thing.id','=','thing_callbacks.source_thing_id');
            }

            if ($params->getThingUuid() ) {
                $build->where('param_thing.ref_uuid',$params->getThingUuid());
            }

            if ($params->getOwnerId() ) {
                $build->where('param_thing.owner_type_id',$params->getOwnerId());
            }

            if ($params->getOwnerType() ) {
                $build->where('param_thing.owner_type',$params->getOwnerType());
            }

            if ($params->getErrorUuid() ) {
                $build->join('thing_errors as param_error','param_error.id','=','thing_callbacks.callback_error_id');
                $build->where('param_error.ref_uuid',$params->getErrorUuid());
            }

            if ($params->getAlertUuid() ) {
                $build->join('thing_callbacks as param_call','param_call.id','=','thing_callbacks.manual_alert_callback_id');
                $build->where('thing_callbacks.ref_uuid',$params->getAlertUuid());
            }

            if ($params->getHookUuid() ||  $params->isManual() ||  $params->isBlocking() ||  $params->isAfter()
                ||  $params->isSharing()||  $params->getHookCallbackType()
            )
            {
                $build->join('thing_hooks as param_hook','param_hook.id','=','thing_callbacks.owning_hook_id');

                if ($params->getHookUuid() ) {
                    $build->where('param_hook.ref_uuid', $params->getHookUuid());
                }

                if ($params->isManual() ) {
                    $build->where('param_hook.is_manual', $params->isManual());
                }

                if ($params->isBlocking() ) {
                    $build->where('param_hook.is_blocking', $params->isBlocking());
                }

                if ($params->isAfter() ) {
                    $build->where('param_hook.is_after', $params->isAfter());
                }

                if ($params->isSharing() ) {
                    $build->where('param_hook.is_sharing', $params->isSharing());
                }

                if ($params->getHookCallbackType() ) {
                    $build->where('param_hook.hook_callback_type', $params->getHookCallbackType());
                }
            }
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

        if ($action?->isActionComplete()) {
            $action_data = $action->getActionResult();
        } else {
            $action_data = $action->getPreRunData();
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


        try {
            /** @var IHookCode|string $callable */
            $callable = $this->owning_hook->address;
            $ret = $callable::runHook(header: $this->callback_outgoing_header?->getArrayCopy()??[],body: $this->callback_outgoing_data?->getArrayCopy()??[]  );

        } catch (\Exception|\Error $e) {
            Log::warning("Got error when calling $callable :".$e->getMessage());
            throw $e;
        }

        return $ret;
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
        $original_data = array_values($this->callback_outgoing_data?->getArrayCopy()??[]);
       /** @var ICallResponse|array|null $ret */
        $ret = Eventy::filter($this->owning_hook->address,$original_data );

        if (is_array($ret) ) {
            return new CallResponse(code: CodeOf::HTTP_NOT_FOUND,successful: false,data: $ret);
        } elseif (is_null($ret)) {
            return new CallResponse(code: CodeOf::HTTP_SERVICE_UNAVAILABLE,successful: false,data: $original_data);
        } else {
            if (is_object($ret)) {
                $interfaces = class_implements($ret);

                if (!isset($interfaces['Hexbatch\Things\Interfaces\ICallResponse'])) {
                    return new CallResponse(code: CodeOf::HTTP_BAD_GATEWAY,successful: false,data: $original_data);
                }
                return $ret;
            } else {
                return new CallResponse(code: CodeOf::HTTP_UNPROCESSABLE_ENTITY,successful: false,data: $ret);
            }

        }
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
        if ($this->owning_hook->hook_callback_type === TypeOfCallback::DISABLED || empty($this->owning_hook->address))
        {
            return;
        }



        try {
            if ($this->thing_callback_status === TypeOfCallbackStatus::WAITING )
            //run each callback only once to call the address, if no address then is manual
            {
                switch ($this->owning_hook->hook_callback_type) {


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
                    default: {
                        throw new \LogicException("should not get here");
                    }
                }


                $this->callback_http_code = $response->getCode();
                if ($this->callback_http_code >=200 && $this->callback_http_code < 300) {
                    $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_SUCCESSFUL;
                } else {
                    $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_ERROR;
                }
                $this->callback_incoming_data = $response->getData();
            } //end if this callback is waiting


            if (!($this->owning_hook->is_manual && $this->owning_hook->address) && $this->owning_hook->is_writing_data_to_thing)
            {
                //if not a jump start, then do data and maybe signalling
                if ($this->owning_hook->is_blocking) {
                    if ($this->owning_hook->is_after) {
                        $this->thing_source->thing_parent?->getAction()->addDataBeforeRun(data: $this->callback_incoming_data?->getArrayCopy() ?? []);
                    } else {
                        $this->thing_source->getAction()->addDataBeforeRun(data: $this->callback_incoming_data?->getArrayCopy() ?? []);
                    }
                }

                if ($this->is_signalling_when_done) {
                    $this->thing_source->signal_parent();
                }
            }

        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->callback_run_at = Carbon::now()->timezone('UTC')->toDateTime();
            $this->save();
        }

    }

    public static function createCallback(ThingHook $hook, Thing $thing) : ThingCallback {

        $current_shared_callback = null;
        if ($hook->is_sharing) {
            $current_shared_callback = $thing->getCurrentSharedCallbackFromDescendant(hook: $hook);
        }
        $node = new ThingCallback();
        $node->owning_hook_id = $hook->id;
        $node->source_thing_id = $thing->id;
        $node->save(); //save and refresh first time to get uuid
        $node->refresh();
        $node->callback_outgoing_data = $node->calculateData(source: $hook->hook_data_template?->getArrayCopy()??[], hook: $hook, thing: $thing);
        $node->callback_outgoing_header = $node->calculateData(source: $hook->hook_header_template?->getArrayCopy()??[],hook: $hook, thing: $thing);
        if ($current_shared_callback) {
            $node->callback_incoming_data = $current_shared_callback->callback_incoming_data;
            $node->thing_callback_status = $current_shared_callback->thing_callback_status;
            $node->source_shared_callback_id = $current_shared_callback->id;
        } else {
            $node->thing_callback_status = TypeOfCallbackStatus::WAITING;
        }

        $node->save();
        return $node;
    }

    public function createEmptyManual() : ?ThingCallback {
        if (!$this->owning_hook->is_manual) {return null;}
        if (!$this->owning_hook->address) {return $this;}
        $node = new ThingCallback();
        $node->owning_hook_id = $this->owning_hook_id;
        $node->source_thing_id = $this->source_thing_id;
        $node->manual_alert_callback_id = $this->id; //to tie them
        $node->save(); //save and refresh first time to get uuid
        $node->refresh();
        $node->callback_outgoing_data = $node->calculateData(source: $this->owning_hook->hook_data_template?->getArrayCopy()??[],
            hook: $this->owning_hook, thing: $this->thing_source);
        $node->callback_outgoing_header = $node->calculateData(source: $this->owning_hook->hook_header_template?->getArrayCopy()??[],
            hook: $this->owning_hook, thing: $this->thing_source);
        $node->thing_callback_status = TypeOfCallbackStatus::WAITING;

        $node->save();
        return $node;
    }

    public function isCompleted() : bool {
        return in_array($this->thing_callback_status,[TypeOfCallbackStatus::CALLBACK_SUCCESSFUL, TypeOfCallbackStatus::CALLBACK_ERROR]);
    }

    /**
     * @throws \Exception
     */
    public function setManualAnswer(ICallResponse $setter) {

        try {
            DB::beginTransaction();

            $this->callback_http_code = $setter->getCode();
            if ($setter->getData() !== null) {
                $this->callback_incoming_data = $setter->getData();
            }

            $this->save();
            //see if any remaining manual callbacks waiting for thing, and if its status is waiting. If not more, set status
            /** @var static[] $brothers */
            $brothers = static::buildCallback(thing_id: $this->source_thing_id, has_alert: true)->get();
            foreach ($brothers as $patter) {
                if ($patter->id === $this->id) {
                    continue;
                }
                if (!$patter->isCompleted()) {
                    return;
                }
            }
            //either no more, or all done
            $this->thing_source->continueThing();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function setSignalWhenDone() {
        $this->update(['is_signalling_when_done'=>true]);
    }


}
