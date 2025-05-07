<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfCallback;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Helpers\CallResponse;
use Hexbatch\Things\Interfaces\ICallResponse;
use Hexbatch\Things\Models\Traits\ThingOwnerHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use LaLit\Array2XML;
use LaLit\XML2Array;
use TorMorten\Eventy\Facades\Eventy;


/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int owning_hooker_id
 * @property int owning_callplate_id
 * @property int callback_error_id
 *
 * @property int callback_http_code
 *
 * @property string ref_uuid
 *
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
 * @property ThingHooker owning_hooker
 * @property ThingCallplate my_callplate
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
        'callback_outgoing_data' => AsArrayObject::class,
        'callback_incoming_data' => AsArrayObject::class,
        'callback_outgoing_header' => AsArrayObject::class,
        'thing_callback_status' => TypeOfCallbackStatus::class,
    ];

    public function owning_hooker() : BelongsTo {
        return $this->belongsTo(ThingHooker::class,'owning_hooker_id','id');
    }

    public function my_callplate() : BelongsTo {
        return $this->belongsTo(ThingCallplate::class,'owning_callplate_id','id');
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
            $build->where('thing_callbacks.owning_callplate_id',$callplate_id);
        }




        /** @uses ThingCallback::owning_hooker(),static::my_callplate() */
        $build->with('owning_hooker','my_callplate');


        return $build;
    }

    public function makeCallback(ThingHooker $hooker) : ThingCallback {
        //why do scope here?
        switch ($hooker->parent_hook->hook_scope) {
            case TypeOfHookScope::GLOBAL: {
                /** @var ThingHooker $global_hooker */
                $global_hooker = ThingHooker::buildHooker(hook_id: $hooker->id)->first();
                if ($global_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $global_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }
            case TypeOfHookScope::ALL_TREE: {
                /** @var ThingHooker $tree_hooker */
                $tree_hooker = ThingHooker::buildHooker(hook_id: $hooker->id,belongs_to_tree_thing_id: $hooker->hooker_thing->root_thing_id)->first();
                if ($tree_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $tree_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }

            case TypeOfHookScope::ANCESTOR_CHAIN:
            {
                /** @var ThingHooker $ancestor_hooker */
                $ancestor_hooker = ThingHooker::buildHooker(hook_id: $hooker->id,belongs_to_ancestor_of_thing_id: $hooker->hooker_thing->id)->first();
                if ($ancestor_hooker) {
                    $callback = ThingCallback::buildCallback(hooker_id: $ancestor_hooker->id)->first() ;
                    if ($callback) {return $callback;}
                }
                break;
            }

            case TypeOfHookScope::CURRENT: {break;}
        }

        $c = new ThingCallback();
        $c->owning_callplate_id = $this->id;
        $c->thing_callback_status = TypeOfCallbackStatus::WAITING;
        $c->callback_outgoing_data = array_merge($this->callplate_data_template?->getArrayCopy()??[],
            $hooker->parent_hook->hook_constant_data?->getArrayCopy()??[]);
        $c->callback_outgoing_header = $this->callplate_header_template;
        $c->save();
        return $c;
    }

    protected function getOutoingDataAsArray() : array {

        $fourth_data = [
            'callback' => $this->ref_uuid,
            'hook' => $this->owning_hooker->parent_hook->ref_uuid,
            'thing' => $this->owning_hooker->hooker_thing->ref_uuid,
            'action' => $this->owning_hooker->hooker_thing->getAction()?->getActionRef()??null,
        ];

        $third_data = $this->owning_hooker->hooker_thing->thing_stat->stat_constant_data?->getArrayCopy()??[];

        $action_data = [];
        $action = $this->owning_hooker->hooker_thing->getAction();
        if ($action?->isActionComplete()) {
            $action_data = $action->getActionResult();
        }

        $second_data = $this->owning_hooker->parent_hook->hook_constant_data?->getArrayCopy()??[];

        $first_data = $this->callback_outgoing_data?->getArrayCopy()??[];

        return array_merge($first_data,$second_data,$action_data,$third_data,$fourth_data);
    }

    /**
     * @throws \Exception
     */
    protected function callCode()
    :ICallResponse
    {
        //todo check if address is correct interface, what is that?
        $params = $this->maybeCastOutgoingData();
        if ($this->callback_class && $this->callback_function) {
            $method = "$this->callback_class::$this->callback_function";
        } elseif ($this->callback_function) {
            $method = $this->callback_function;
        } else {
            throw new HbcThingException("no class or method, or only class, in callback");
        }
        $code = 200;
        try {
            $ret = call_user_func_array($method, $params);

        } catch (\Exception|\Error $e) {
            Log::warning("Got error when calling $method :".$e->getMessage());
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
        if (!$this->my_callplate->address) {
            throw new HbcThingException("Callback event name not defined");
        }
        $params = $this->maybeCastOutgoingData();
        /** @noinspection PhpUndefinedMethodInspection */
        $ret = Eventy::filter($this->my_callplate->address, array_values($params));
        $success = true;
        $code = 200;
        if (empty($ret) ||(is_array($ret) && count($ret) === 0)) {
            $success = false;
            $code = 500;
        }
        return new CallResponse(code: $code,successful: $success,data: $ret);
    }

    /**
     * @throws \Exception
     */
    protected function maybeCastOutgoingData() : array|null|string
    {
        $params = $this->getOutoingDataAsArray();
        switch ($this->my_callplate->callplate_callback_type) {
            case TypeOfCallback::DISABLED:
            case TypeOfCallback::MANUAL:
            case TypeOfCallback::CODE:
            case TypeOfCallback::EVENT_CALL:
            case TypeOfCallback::HTTP_GET:
            case TypeOfCallback::HTTP_POST:
            case TypeOfCallback::HTTP_PUT:
            case TypeOfCallback::HTTP_PATCH:
            case TypeOfCallback::HTTP_DELETE:
            case TypeOfCallback::HTTP_POST_JSON:
            case TypeOfCallback::HTTP_PUT_JSON:
            case TypeOfCallback::HTTP_PATCH_JSON:
            case TypeOfCallback::HTTP_DELETE_JSON:
            case TypeOfCallback::HTTP_POST_FORM:
            case TypeOfCallback::HTTP_PUT_FORM:
            case TypeOfCallback::HTTP_PATCH_FORM:
            case TypeOfCallback::HTTP_DELETE_FORM:
            {
                return $params;
            }
            case TypeOfCallback::HTTP_POST_XML:
            case TypeOfCallback::HTTP_PUT_XML:
            case TypeOfCallback::HTTP_PATCH_XML:
            case TypeOfCallback::HTTP_DELETE_XML:
            {
                //todo make xml from body , need root?
                return Array2XML::createXML($this->callback_xml_root??'root', $params)->saveXML();
            }break;
            case TypeOfCallback::DUMP:
                throw new \Exception('To be implemented');
        }
        return null;
    }

    protected function getOutgoingHeaders() : array {
        $params = $this->getOutoingDataAsArray();
        $ret = [];
        $pattern = '/\$\{(\w+)}/';
        foreach ($this->callback_outgoing_header as $header_key => $header_val) {

            $has_lookup = preg_match($pattern,$header_val,$matches);
            if ($has_lookup) {
                if (!array_key_exists($matches[1],$params)) {
                    continue;
                }
                $modified_header_val = preg_replace_callback($pattern , function($matches) use ($params) {
                    return $params[$matches[1]]??null;
                }, $header_val);
            } else {
                $modified_header_val = $header_val;
            }
            $ret[$header_key] = $modified_header_val;
        }

        switch ($this->my_callplate->callplate_callback_type) {
            case TypeOfCallback::HTTP_POST_XML:
            case TypeOfCallback::HTTP_PUT_XML:
            case TypeOfCallback::HTTP_PATCH_XML:
            case TypeOfCallback::HTTP_DELETE_XML:
                $ret["Content-Type"] = "text/xml";
                break;
            default: {

            }
        }
       return $ret;
    }

    /**
     * @throws ConnectionException
     * @throws \Exception
     */
    protected function callRemote() : ICallResponse {
        $params = $this->maybeCastOutgoingData();
        $headers = $this->getOutgoingHeaders();
        $response = null;
        switch ($this->my_callplate->callplate_callback_type) {

            case TypeOfCallback::DISABLED:
            case TypeOfCallback::MANUAL:
            case TypeOfCallback::CODE:
            case TypeOfCallback::EVENT_CALL:
            {
                throw new HbcThingException("Callback type is not a remote call");
            }

            case TypeOfCallback::HTTP_GET:
                $response = Http::withHeaders($headers)->get($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_POST:
                $response = Http::withHeaders($headers)->post($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT:
                $response = Http::withHeaders($headers)->put($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH:
                $response = Http::withHeaders($headers)->patch($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_DELETE:
                $response = Http::withHeaders($headers)->delete($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_POST_FORM:
                $response = Http::asForm()->withHeaders($headers)->post($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT_FORM:
                $response = Http::asForm()->withHeaders($headers)->put($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH_FORM:
                $response = Http::asForm()->withHeaders($headers)->patch($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_DELETE_FORM:
                $response = Http::asForm()->withHeaders($headers)->delete($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_POST_JSON:
                $response = Http::asJson()->withHeaders($headers)->post($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT_JSON:
                $response = Http::asJson()->withHeaders($headers)->put($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH_JSON:
                $response = Http::asJson()->withHeaders($headers)->patch($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_DELETE_JSON:
                $response = Http::asJson()->withHeaders($headers)->delete($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_POST_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->post($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_PUT_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->put($this->my_callplate->address, $params);
                break;
            case TypeOfCallback::HTTP_PATCH_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->patch($this->my_callplate->address,$params);
                break;
            case TypeOfCallback::HTTP_DELETE_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->delete($this->my_callplate->address,$params);
                break;
        }


        switch ($this->my_callplate->callplate_callback_type) {
            case TypeOfCallback::HTTP_POST_XML:
            case TypeOfCallback::HTTP_PUT_XML:
            case TypeOfCallback::HTTP_PATCH_XML:
            case TypeOfCallback::HTTP_DELETE_XML:
                $string_xml = $response->body();
                $data = XML2Array::createArray($string_xml);
                break;
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
        if ($this->my_callplate->callplate_callback_type === TypeOfCallback::DISABLED) {
            return;
        }



        try {

            $response = null;
            switch ($this->my_callplate->callplate_callback_type) {

                case TypeOfCallback::DISABLED:
                case TypeOfCallback::MANUAL:
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
                case TypeOfCallback::HTTP_POST_XML:
                case TypeOfCallback::HTTP_PUT_XML:
                case TypeOfCallback::HTTP_PATCH_XML:
                case TypeOfCallback::HTTP_DELETE_XML:
                {
                    $response =  $this->callRemote();
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
        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfCallbackStatus::CALLBACK_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->callback_run_at = Carbon::now()->timezone('UTC')->toDateTime();
            $this->save();
            $this->owning_hooker->maybeCallbacksDone();
        }


    }

    public function isDone() {
        return ($this->thing_callback_status === TypeOfCallbackStatus::CALLBACK_ERROR || $this->thing_callback_status === TypeOfCallbackStatus::CALLBACK_SUCCESSFUL);
    }

}
