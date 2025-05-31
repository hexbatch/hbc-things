<?php

namespace Hexbatch\Things\OpenApi\Errors;

use Carbon\Carbon;
use Hexbatch\Things\Models\ThingError;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ThingErrorResponse',title: "Error")]

/**
 * Show a Hook
 */
class ThingErrorResponse  implements  JsonSerializable {

    #[OA\Property( title:"Code")]
    protected int $code;

    #[OA\Property( title:"Reference Code")]
    protected ?int $ref_code = null;

    #[OA\Property( title:"Reference url")]
    protected ?string $ref_url = null;

    #[OA\Property( title:"Message")]
    protected string $message;

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;

    #[OA\Property( title:"Action Name")]
    protected ?string $action_name;


    #[OA\Property( title:"Thing uuid",format: 'uuid')]
    protected ?string $thing_uuid = null;

    #[OA\Property( title:"Callback uuid",format: 'uuid')]
    protected ?string $callback_uuid = null;

    #[OA\Property( title: 'Time',description: "Iso 8601 datetime string for when error happened", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $time = null;

    #[OA\Property( title:"Related tags",nullable: true)]
    /** @var string[] $tags */
    protected ?array $related_tags;

    public function __construct(
        protected ThingError $error
    ) {
        $this->code = $this->error->thing_error_code;
        $this->message = $this->error->thing_error_message;
        $this->uuid = $this->error->ref_uuid;
        $this->time = Carbon::parse($this->error->created_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        $this->related_tags = $this->error->related_tags?->getArrayCopy()??[];

        if (count($this->error->error_things)) {
            $this->thing_uuid = $this->error->error_things[0]->ref_uuid;
            $this->action_name = $this->error->error_things[0]->getAction()?->getActionName();
        }

        if (count($this->error->error_callbacks)) {
            $this->callback_uuid = $this->error->error_callbacks[0]->ref_uuid;
        }

        $this->ref_code = $this->error->thing_ref_code;
        $this->ref_url = $this->error->thing_error_url;
    }
    public function jsonSerialize(): array
    {
        $ret =  [
            'message' => $this->message,
            'code' => $this->code,
            'uuid' => $this->uuid,
            'time' => $this->time,
            'tags' => $this->related_tags,
        ];

        if ($this->ref_code) {
            $ret['ref_code'] = $this->ref_code;
        }

        if ($this->ref_url) {
            $ret['ref_url'] = $this->ref_url;
        }

        if ($this->thing_uuid) {
            $ret['thing_uuid'] = $this->thing_uuid;
        }

        if ($this->action_name) {
            $ret['action_name'] = $this->action_name;
        }

        if ($this->callback_uuid) {
            $ret['callback_uuid'] = $this->callback_uuid;
        }


        return $ret;
    }
}
