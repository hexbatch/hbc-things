<?php

namespace Hexbatch\Things\OpenApi\Callbacks;

use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\OpenApi\Errors\ErrorResponse;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'CallbackResponse',title: "Callback")]

/**
 * Show a Hook
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
    protected ?ErrorResponse $error;

    #[OA\Property( title:"Code of response")]
    protected int $code;

    #[OA\Property( title:"Response of callback",nullable: true)]
    protected ?array $response;



    public function __construct(
        protected ThingCallback $callback
    ) {

        $this->uuid = $this->callback->ref_uuid;
        $this->hook_uuid = $this->callback->owning_hook->ref_uuid;
        $this->thing_uuid = $this->callback->thing_source->ref_uuid;
        $this->error = null;
        /** @uses \Hexbatch\Things\Models\ThingCallback::callback_error() */
        if ($this->callback->callback_error) {
            $this->error = new ErrorResponse(error: $this->callback->callback_error);
        }
        $this->code = $this->callback->callback_http_code;
        $this->response = $this->callback->callback_incoming_data?->getArrayCopy()??null;
    }

    public function jsonSerialize(): array
    {
        $arr['uuid'] = $this->uuid;
        $arr['hook_uuid'] = $this->hook_uuid;
        $arr['thing_uuid'] = $this->thing_uuid;
        $arr['error'] = $this->error;
        $arr['code'] = $this->code;
        $arr['response'] = $this->response;
        return $arr;
    }
}
