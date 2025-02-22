<?php

namespace Hexbatch\Things\Models;




use Hexbatch\Things\Exceptions\HbcThingException;
use Hexbatch\Things\Models\Enums\TypeOfThingCallback;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackEncoding;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackMethod;
use Hexbatch\Things\Models\Enums\TypeOfThingCallbackStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @mixin Builder
 * @mixin \Illuminate\Database\Query\Builder
 * @property int id
 * @property int thing_result_id
 * @property string caller_type
 * @property int caller_type_id
 * @property int http_code_callback
 * @property TypeOfThingCallbackStatus thing_callback_status
 * @property TypeOfThingCallback thing_callback_type
 * @property TypeOfThingCallbackMethod thing_callback_method
 * @property TypeOfThingCallbackEncoding thing_callback_encoding
 * @property string result_callback_url
 *
 *  @property string created_at
 *  @property string updated_at
 *
 * @property ThingResult result_owner
 */
class ThingResultCallback extends Model
{

    protected $table = 'thing_result_callbacks';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [];

    /**
     * The attributes that should be hidden for serialization.
     * @var array<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     * @var array<string, string>
     */
    protected $casts = [
        'thing_callback_status' => TypeOfThingCallbackStatus::class,
        'thing_callback_type' => TypeOfThingCallback::class,
        'thing_callback_method' => TypeOfThingCallbackMethod::class,
        'thing_callback_encoding' => TypeOfThingCallbackEncoding::class,
    ];

    public function result_owner() : BelongsTo {
        return $this->belongsTo(ThingResult::class,'thing_result_id','id')
            /** @uses ThingResult::thing_owner() */
            ->with('thing_owner');
    }

    public function callbackUrl() :void  {
        if ($this->thing_callback_type !== TypeOfThingCallback::HTTP || !$this->result_callback_url) {
            return;
        }

        try {
            $process_part = [
                'ref' => $this->result_owner->thing_owner->ref_uuid,
                'result_http_status' => $this->result_owner->result_http_status
            ];

            /** @var Response|null $response */
            $response = null;

            if ($this->thing_callback_encoding === TypeOfThingCallbackEncoding::REGULAR) {
                switch ($this->thing_callback_method) {
                    case TypeOfThingCallbackMethod::GET:
                        $response = Http::get($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::POST:
                        $response = Http::post($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::PUT:
                        $response = Http::put($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::PATCH:
                        $response = Http::patch($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::DELETE:
                        $response = Http::delete($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                }
            } else if ($this->thing_callback_encoding === TypeOfThingCallbackEncoding::FORM) {
                switch ($this->thing_callback_method) {
                    case TypeOfThingCallbackMethod::GET:
                        $response = Http::asForm()->get($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::POST:
                        $response = Http::asForm()->post($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::PUT:
                        $response = Http::asForm()->put($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::PATCH:
                        $response = Http::asForm()->patch($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                        break;
                    case TypeOfThingCallbackMethod::DELETE:
                        $response = Http::asForm()->delete($this->result_callback_url, [
                            'process' => $process_part,
                            'data' => $this->result_owner->result_response
                        ]);
                }
            }

            if (!$response) {
                throw new HbcThingException("Response is null, which probably means a case was missed");
            }
            $this->http_code_callback = $response->status();
            if ($response->successful()) {
                $this->thing_callback_status = TypeOfThingCallbackStatus::FOLLOWUP_CALLBACK_SUCCESSFUL;
            } else {
                $this->thing_callback_status = TypeOfThingCallbackStatus::FOLLOWUP_CALLBACK_ERROR;
            }
        } catch (\Exception $e) {
            $this->thing_callback_status = TypeOfThingCallbackStatus::FOLLOWUP_INTERNAL_ERROR;
            Log::error("Thing result callback had error: ". $e->getMessage());
        } finally {
            $this->save();
        }


    }

}
