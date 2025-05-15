<?php

namespace Hexbatch\Things\OpenApi\Things;

use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\OpenApi\Errors\ThingErrorResponse;
use Hexbatch\Things\OpenApi\Hooks\HookCollectionResponse;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'ThingResponse',title: "Callback")]

/**
 * Show a Hook
 */
class ThingResponse  implements  JsonSerializable
{

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;

    #[OA\Property( title:"Parent",format: 'uuid',nullable: true)]
    protected ?string $parent_uuid;


    #[OA\Property( title: "Error", nullable: true)]
    protected ?ThingErrorResponse $error;

    #[OA\Property( title:"Priority")]
    protected int $priority;

    #[OA\Property( title:"Tags",nullable: true)]
    /** @var string[] $tags */
    protected ?array $tags;

    #[OA\Property( title:"Action data",nullable: true)]
    /** @var mixed[] $action_data */
    protected ?array $action_data;

    #[OA\Property( title:"Status")]
    protected TypeOfThingStatus $status;

    #[OA\Property( title:"Action name")]
    protected string $action_name;

    #[OA\Property( title:"Action ref")]
    protected string $action_ref;

    #[OA\Property( title:"Hooks")]
    protected ?HookCollectionResponse $hooks = null;


    #[OA\Property( title:"Children")]
    protected ?ThingCollectionResponse $children = null;


    #[OA\Property( title:"Action html")]
    protected ?string $action_html;

    #[OA\Property( title: 'Started at',description: "Iso 8601 datetime string for when this was started", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $started_at = null;

    #[OA\Property( title: 'Ran at',description: "Iso 8601 datetime string for when this ran", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $ran_at = null;


    public function __construct(
        protected Thing $thing,
        protected bool $b_include_hooks = true,
        protected bool $b_include_children = true,
    ) {

        $this->uuid = $this->thing->ref_uuid;
        $this->parent_uuid = $this->thing->thing_parent?->ref_uuid;

        $this->error = null;
        /** @uses \Hexbatch\Things\Models\Thing::thing_error() */
        if ($this->thing->thing_error) {
            $this->error = new ThingErrorResponse(error: $this->thing->thing_error);
        }
        $this->priority = $this->thing->thing_priority;
        $this->status = $this->thing->thing_status;
        $action = $this->thing->getAction();
        $this->action_name = $action?->getActionType();
        $this->action_ref = $action?->getActionRef();
        $this->action_data = $action?->getDataSnapshot();
        $this->tags = $this->thing->thing_tags?->getArrayCopy()??[];

        if($this->thing->thing_started_at) {
            $this->started_at = Carbon::parse($this->thing->thing_started_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        }

        if($this->thing->thing_ran_at) {
            $this->ran_at = Carbon::parse($this->thing->thing_ran_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        }

        if ($this->b_include_hooks) {
            /** @uses  \Hexbatch\Things\Models\Thing::attached_hooks() */
            $this->hooks = new HookCollectionResponse(given_hooks: $this->thing->attached_hooks,b_include_callbacks: true,callbacks_scoped_to_thing: $this->thing);
        }

        if ($this->b_include_children) {
            $this->children = new ThingCollectionResponse(given_things: $this->thing->thing_children,
                b_include_hooks: $this->b_include_hooks,b_include_children: $this->b_include_children);
        }

        $this->action_html = $this->thing->getAction()->getRenderHtml();

    }

    public function jsonSerialize(): array
    {
        $arr = [];
        $arr['uuid'] = $this->uuid;
        $arr['parent_uuid'] = $this->parent_uuid;
        $arr['started_at'] = $this->started_at;
        $arr['ran_at'] = $this->ran_at;
        $arr['error'] = $this->error;
        $arr['priority'] = $this->priority;
        $arr['status'] = $this->status->value;
        $arr['action_name'] = $this->action_name;
        $arr['action_ref'] = $this->action_ref;
        if($this->action_data) {
            $arr['action_data'] = $this->action_data;
        }
        $arr['action_html'] = $this->action_html;
        $arr['tags'] = array_values($this->tags);
        if ($this->b_include_hooks) {
            $arr['hooks'] = $this->hooks;
        }
        if ($this->b_include_children) {
            $arr['children'] = $this->children;
        }

        return $arr;
    }
}
