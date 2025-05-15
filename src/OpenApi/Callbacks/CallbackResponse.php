<?php

namespace Hexbatch\Things\OpenApi\Callbacks;

use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\OpenApi\Errors\ThingErrorResponse;
use Hexbatch\Things\OpenApi\Hooks\HookResponse;
use Hexbatch\Things\OpenApi\Things\ThingResponse;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CallbackResponse',title: "Callback")]

/**
 * Show a callback
 */
class CallbackResponse  implements  JsonSerializable
{

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;

    #[OA\Property( title:"Hook",format: 'uuid')]
    protected string $hook_uuid;

    #[OA\Property( title:"Thing",format: 'uuid')]
    protected string $thing_uuid;

    #[OA\Property( title:"Error",nullable: true)]
    protected ?ThingErrorResponse $error;

    #[OA\Property( title:"Alert target",nullable: true)]
    protected ?CallbackResponse $alert_target = null;

    #[OA\Property( title:"Alerted by",nullable: true)]
    protected ?CallbackResponse $alerted_by = null;

    #[OA\Property( title:"Code of response")]
    protected int $code;

    #[OA\Property( title:"Response of callback",nullable: true)]
    /** @var mixed[] $response */
    protected ?array $response;

    #[OA\Property( title:"Headers sent",nullable: true )]
    /** @var mixed[] $headers_sent */
    protected ?array $headers_sent;

    #[OA\Property( title:"Data sent",nullable: true)]
    /** @var mixed[] $data_sent */
    protected ?array $data_sent;


    #[OA\Property( title:"Status of callback")]
    protected TypeOfCallbackStatus $status;


    #[OA\Property( title:"Hook")]
    protected ?HookResponse $hook = null;

    #[OA\Property( title:"Thing")]
    protected ?ThingResponse $thing = null;

    #[OA\Property( title: 'Ran at',description: "Iso 8601 datetime string for when this ran", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $ran_at = null;



    public function __construct(
        protected ThingCallback $callback,
        protected bool $b_include_hook = false,
        protected bool $b_include_thing = false,
    ) {

        $this->uuid = $this->callback->ref_uuid;
        $this->hook_uuid = $this->callback->owning_hook->ref_uuid;
        $this->thing_uuid = $this->callback->thing_source->ref_uuid;
        $this->error = null;
        /** @uses \Hexbatch\Things\Models\ThingCallback::callback_error() */
        if ($this->callback->callback_error) {
            $this->error = new ThingErrorResponse(error: $this->callback->callback_error);
        }
        $this->code = $this->callback->callback_http_code;
        $this->response = $this->callback->callback_incoming_data?->getArrayCopy()??null;
        $this->headers_sent = $this->callback->callback_outgoing_header?->getArrayCopy()??null;
        $this->data_sent = $this->callback->callback_outgoing_data?->getArrayCopy()??null;
        $this->status = $this->callback->thing_callback_status;
        $this->ran_at = Carbon::parse($this->callback->callback_run_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        if($b_include_hook) {
            $this->hook = new HookResponse(hook: $this->callback->owning_hook);
        }
        if($b_include_hook) {
            $this->thing = new ThingResponse(thing: $this->callback->thing_source);
        }

        /** @uses \Hexbatch\Things\Models\ThingCallback::alert_target() */
        if($this->callback->alert_target) {
            $this->alert_target = new CallbackResponse(callback: $this->callback->alert_target);
        }

        /** @uses \Hexbatch\Things\Models\ThingCallback::alerted_by() */
        if($this->callback->alerted_by) {
            $this->alerted_by = new CallbackResponse(callback: $this->callback->alerted_by);
        }
    }

    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['uuid'] = $this->uuid;
        $arr['hook_uuid'] = $this->hook_uuid;
        $arr['thing_uuid'] = $this->thing_uuid;

        $arr['code'] = $this->code;
        $arr['status'] = $this->status->value;
        $arr['ran_at'] = $this->ran_at;
        $arr['headers_sent'] = $this->headers_sent;
        $arr['data_sent'] = $this->data_sent;
        $arr['response'] = $this->response;
        if ($this->b_include_hook) {
            $arr['hook'] = $this->hook;
        }
        if ($this->b_include_thing) {
            $arr['thing'] = $this->thing;
        }
        if ($this->error) {
            $arr['error'] = $this->error;
        }

        if ($this->alert_target) {
            $arr['alert_target'] = $this->alert_target;
        }

        if ($this->alerted_by) {
            $arr['alerted_by'] = $this->alerted_by;
        }

        return $arr;
    }
}
