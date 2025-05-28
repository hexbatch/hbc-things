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

    #[OA\Property( title:"Message")]
    protected string $message;

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;

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
    }
    public function jsonSerialize(): array
    {
        return [
            'message' => $this->message,
            'code' => $this->code,
            'uuid' => $this->uuid,
            'time' => $this->time,
            'tags' => $this->related_tags,
        ];
    }
}
