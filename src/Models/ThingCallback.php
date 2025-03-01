<?php

namespace Hexbatch\Things\Models;


use ArrayObject;
use Hexbatch\Things\Enums\TypeOfThingCallback;
use Hexbatch\Things\Enums\TypeOfThingCallbackStatus;
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
 * @property int callback_callplate_id
 * @property int callback_error_id
 *
 * @property int callback_http_code
 * @property string owner_type
 * @property int owner_type_id
 *
 * @property string ref_uuid
 *
 *
 * @property string  callback_url
 * @property string  callback_class
 * @property string  callback_function
 * @property string  callback_event
 * @property string  callback_xml_root
 * @property ArrayObject callback_outgoing_data
 * @property ArrayObject callback_incoming_data
 * @property ArrayObject  callback_outgoing_header
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
        'callback_outgoing_header' => AsArrayObject::class,
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

    protected function getOutoingDataAsArray() : array {

        $fourth_data = [
            'callback' => $this->ref_uuid,
            'hook' => $this->callback_owning_hooker->parent_hook->ref_uuid,
            'thing' => $this->callback_owning_hooker->hooker_thing->ref_uuid,
            'action' => $this->callback_owning_hooker->hooker_thing->getAction()->getActionRef(),
        ];
        $action = $this->callback_owning_hooker->hooker_thing->getAction();
        if ($action->isActionComplete()) {
            $third_data = $action->getActionResult()??[];
        } else {
            $third_data = $this->callback_owning_hooker->hooker_thing->thing_constant_data?->getArrayCopy()??[];
        }

        $second_data = $this->callback_owning_hooker->parent_hook->hook_constant_data?->getArrayCopy()??[];

        $first_data = $this->callback_outgoing_data?->getArrayCopy()??[];

        return array_merge($first_data,$second_data,$third_data,$fourth_data);
    }

    /**
     * @throws \Exception
     */
    protected function callCode()
    :ICallResponse
    {
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
        if (!$this->callback_event) {
            throw new HbcThingException("Callback event name not defined");
        }
        $params = $this->maybeCastOutgoingData();
        /** @noinspection PhpUndefinedMethodInspection */
        $ret = Eventy::filter($this->callback_event, array_values($params));
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
        switch ($this->thing_callback_type) {
            case TypeOfThingCallback::DISABLED:
            case TypeOfThingCallback::MANUAL:
            case TypeOfThingCallback::CODE:
            case TypeOfThingCallback::EVENT_CALL:
            case TypeOfThingCallback::HTTP_GET:
            case TypeOfThingCallback::HTTP_POST:
            case TypeOfThingCallback::HTTP_PUT:
            case TypeOfThingCallback::HTTP_PATCH:
            case TypeOfThingCallback::HTTP_DELETE:
            case TypeOfThingCallback::HTTP_POST_JSON:
            case TypeOfThingCallback::HTTP_PUT_JSON:
            case TypeOfThingCallback::HTTP_PATCH_JSON:
            case TypeOfThingCallback::HTTP_DELETE_JSON:
            case TypeOfThingCallback::HTTP_POST_FORM:
            case TypeOfThingCallback::HTTP_PUT_FORM:
            case TypeOfThingCallback::HTTP_PATCH_FORM:
            case TypeOfThingCallback::HTTP_DELETE_FORM:
            {
                return $params;
            }
            case TypeOfThingCallback::HTTP_POST_XML:
            case TypeOfThingCallback::HTTP_PUT_XML:
            case TypeOfThingCallback::HTTP_PATCH_XML:
            case TypeOfThingCallback::HTTP_DELETE_XML:
            {
                return Array2XML::createXML($this->callback_xml_root??'root', $params)->saveXML();
            }
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

        switch ($this->thing_callback_type) {
            case TypeOfThingCallback::HTTP_POST_XML:
            case TypeOfThingCallback::HTTP_PUT_XML:
            case TypeOfThingCallback::HTTP_PATCH_XML:
            case TypeOfThingCallback::HTTP_DELETE_XML:
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
        switch ($this->thing_callback_type) {

            case TypeOfThingCallback::DISABLED:
            case TypeOfThingCallback::MANUAL:
            case TypeOfThingCallback::CODE:
            case TypeOfThingCallback::EVENT_CALL:
            {
                throw new HbcThingException("Callback type is not a remote call");
            }

            case TypeOfThingCallback::HTTP_GET:
                $response = Http::withHeaders($headers)->get($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_POST:
                $response = Http::withHeaders($headers)->post($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_PUT:
                $response = Http::withHeaders($headers)->put($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_PATCH:
                $response = Http::withHeaders($headers)->patch($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_DELETE:
                $response = Http::withHeaders($headers)->delete($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_POST_FORM:
                $response = Http::asForm()->withHeaders($headers)->post($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_PUT_FORM:
                $response = Http::asForm()->withHeaders($headers)->put($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_PATCH_FORM:
                $response = Http::asForm()->withHeaders($headers)->patch($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_DELETE_FORM:
                $response = Http::asForm()->withHeaders($headers)->delete($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_POST_JSON:
                $response = Http::asJson()->withHeaders($headers)->post($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_PUT_JSON:
                $response = Http::asJson()->withHeaders($headers)->put($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_PATCH_JSON:
                $response = Http::asJson()->withHeaders($headers)->patch($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_DELETE_JSON:
                $response = Http::asJson()->withHeaders($headers)->delete($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_POST_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->post($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_PUT_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->put($this->callback_url, $params);
                break;
            case TypeOfThingCallback::HTTP_PATCH_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->patch($this->callback_url,$params);
                break;
            case TypeOfThingCallback::HTTP_DELETE_XML:
                $response = Http::withBody($params,'text/xml')->withHeaders($headers)->delete($this->callback_url,$params);
                break;
        }


        switch ($this->thing_callback_type) {
            case TypeOfThingCallback::HTTP_POST_XML:
            case TypeOfThingCallback::HTTP_PUT_XML:
            case TypeOfThingCallback::HTTP_PATCH_XML:
            case TypeOfThingCallback::HTTP_DELETE_XML:
                $string_xml = $response->body();
                $data = XML2Array::createArray($string_xml);
                break;
            case TypeOfThingCallback::HTTP_POST_JSON:
            case TypeOfThingCallback::HTTP_PUT_JSON:
            case TypeOfThingCallback::HTTP_PATCH_JSON:
            case TypeOfThingCallback::HTTP_DELETE_JSON:
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
        if ($this->thing_callback_type === TypeOfThingCallback::DISABLED) {
            return;
        }



        try {

            $response = null;
            switch ($this->thing_callback_type) {

                case TypeOfThingCallback::DISABLED:
                case TypeOfThingCallback::MANUAL:
                {
                    return;
                }

                case TypeOfThingCallback::CODE:
                {
                    $response = $this->callCode();
                    break;
                }
                case TypeOfThingCallback::EVENT_CALL:
                {
                    $response = $this->callEvent();
                    break;
                }

                case TypeOfThingCallback::HTTP_GET:
                case TypeOfThingCallback::HTTP_POST:
                case TypeOfThingCallback::HTTP_PUT:
                case TypeOfThingCallback::HTTP_PATCH:
                case TypeOfThingCallback::HTTP_DELETE:
                case TypeOfThingCallback::HTTP_POST_FORM:
                case TypeOfThingCallback::HTTP_PUT_FORM:
                case TypeOfThingCallback::HTTP_PATCH_FORM:
                case TypeOfThingCallback::HTTP_DELETE_FORM:
                case TypeOfThingCallback::HTTP_POST_JSON:
                case TypeOfThingCallback::HTTP_PUT_JSON:
                case TypeOfThingCallback::HTTP_PATCH_JSON:
                case TypeOfThingCallback::HTTP_DELETE_JSON:
                case TypeOfThingCallback::HTTP_POST_XML:
                case TypeOfThingCallback::HTTP_PUT_XML:
                case TypeOfThingCallback::HTTP_PATCH_XML:
                case TypeOfThingCallback::HTTP_DELETE_XML:
                {
                    $response =  $this->callRemote();
                }
            }

            if (!$response) {
                throw new HbcThingException("Response is null, which probably means a case was missed");
            }
            $this->callback_http_code = $response->getCode();
            if ($response->isSuccessful()) {
                $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_SUCCESSFUL;
            } else {
                $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_ERROR;
            }
            $this->callback_incoming_data = $response->getData();
        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfThingCallbackStatus::CALLBACK_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->save();
            $this->callback_owning_hooker->maybeCallbacksDone();
        }


    }

}
