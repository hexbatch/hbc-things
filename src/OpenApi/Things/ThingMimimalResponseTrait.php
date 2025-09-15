<?php

namespace Hexbatch\Things\OpenApi\Things;

use Carbon\Carbon;
use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\OpenApi\Errors\ThingErrorResponse;
use OpenApi\Attributes as OA;

trait ThingMimimalResponseTrait
{
    #[OA\Property( title:"Self",format: 'uuid')]
    protected ?string $thing_uuid = null;

    #[OA\Property( title:"Parent",format: 'uuid',nullable: true)]
    protected ?string $thing_parent_uuid = null;


    #[OA\Property( title: "Error", nullable: true)]
    protected ?ThingErrorResponse $thing_error = null;

    #[OA\Property( title:"Status")]
    protected ?TypeOfThingStatus $thing_status = null;

    #[OA\Property( title:"Async")]
    protected ?bool $thing_async = null;

    #[OA\Property( title:"Action name")]
    protected ?string $thing_action_name = null;

    #[OA\Property( title:"Action ref")]
    protected ?string $thing_action_ref = null;


    #[OA\Property( title:"Owner name")]
    protected ?string $thing_owner_name = null;

    #[OA\Property( title:"Owner ref")]
    protected ?string $thing_owner_ref = null;

    #[OA\Property( title: 'Started at',description: "Iso 8601 datetime string for when this was started", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $thing_started_at = null;

    #[OA\Property( title: 'Ran at',description: "Iso 8601 datetime string for when this ran", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $thing_ran_at = null;

    #[OA\Property( title: 'Wait until ',description: "Iso 8601 datetime string for when this ran", format: 'datetime',example: "2025-01-25T15:00:59-06:00")]
    public ?string $thing_wait_until_at = null;

    #[OA\Property( title:"Tags",nullable: true)]
    /** @var string[] $tags */
    protected ?array $thing_tags = [];


    protected ?Thing $thing = null;
    protected ?IThingAction $action = null;
    protected ?IThingOwner $owner = null;

    protected function initThingFields(?Thing $thing) {
        if (!$thing) {return;}
        $this->thing = $thing;
        $this->thing_uuid = $this->thing->ref_uuid;
        $this->thing_parent_uuid = $this->thing->thing_parent?->ref_uuid;

        $this->thing_error = null;
        /** @uses Thing::thing_error() */
        if ($this->thing->thing_error) {
            $this->thing_error = new ThingErrorResponse(error: $this->thing->thing_error);
        }

        $this->action = $thing->getAction();
        $this->thing_action_name = $this->action?->getActionType();
        $this->thing_action_ref = $this->action?->getActionRef();

        $this->owner = $thing->getOwner();
        $this->thing_owner_name = $this->owner?->getName();
        $this->thing_owner_ref = $this->owner?->getOwnerUuid();

        $this->thing_status = $this->thing->thing_status;

        $this->thing_async = $this->thing->is_async;

        if($this->thing->thing_started_at) {
            $this->thing_started_at = Carbon::parse($this->thing->thing_started_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        }

        if($this->thing->thing_ran_at) {
            $this->thing_ran_at = Carbon::parse($this->thing->thing_ran_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        }

        if($this->thing->thing_wait_until_at) {
            $this->thing_wait_until_at = Carbon::parse($this->thing->thing_wait_until_at,'UTC')->timezone(config('app.timezone'))->toIso8601String();
        }

        $this->thing_tags = $this->thing->thing_tags?->getArrayCopy()??[];
    }

    public function getThingInfoArray(): ?array
    {
        if (!$this->thing) {return null;}
        $arr = [];
        $arr['thing_uuid'] = $this->thing_uuid;
        $arr['thing_parent_uuid'] = $this->thing_parent_uuid;
        $arr['thing_async'] = $this->thing_async;
        $arr['thing_started_at'] = $this->thing_started_at;
        $arr['thing_ran_at'] = $this->thing_ran_at;

        if ($this->thing_wait_until_at) {
            $arr['thing_wait_until_at'] = $this->thing_wait_until_at;
        }
        if ($this->thing_error) {
            $arr['thing_error'] = $this->thing_error;
        }

        $arr['thing_status'] = $this->thing_status->value;
        $arr['thing_action_name'] = $this->thing_action_name;
        $arr['thing_action_ref'] = $this->thing_action_ref;

        $arr['thing_owner_name'] = $this->thing_owner_name;
        $arr['thing_owner_ref'] = $this->thing_owner_ref;
        $arr['thing_tags'] = array_values($this->thing_tags);

        return $arr;
    }
}
