<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\OpenApi\Hooks\Callplates\CallplateResponse;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'HookResponse',title: "Hook")]

/**
 * Show a Hook
 */
class HookResponse extends HookParams implements  JsonSerializable
{

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;


    #[OA\Property( title: 'Owner type',description: 'The type of hook owner',nullable: true)]
    protected ?string  $owner_type = null;

    #[OA\Property( title: 'Owner id',description: 'Hook owner id',nullable: true)]
    protected ?string  $owner_id = null;


    #[OA\Property( title: 'Callback templates',description: 'Callbacks are made from these.',nullable: false)]
    /** @var CallplateResponse[] $callplates */
    protected array $callplates = [];

    public function __construct(
        protected ThingHook $hook
    ) {

        parent::__construct(
            mode: $hook->hook_mode,
            position:  $hook->hook_position,
            blocking:  $hook->blocking_mode,
            scope:  $hook->hook_scope,
            name:  $hook->hook_name,
            notes:  $hook->hook_notes,
            action_type:  $hook->getAction()?->getActionType(),
            action_id:  $hook->getAction()?->getActionId(),
            constant_data: $hook->hook_constant_data?->getArrayCopy()??[],
            tags: $hook->hook_tags?->getArrayCopy()??[],
            hook_on: $hook->is_on,
        );

        $this->uuid = $this->hook->ref_uuid;

        $this->callplates = [];
        foreach ($hook->hook_callplates as $callplate) {
            $this->callplates[] = new CallplateResponse(callplate: $callplate);
        }

        $this->owner_id = $this->hook->getOwner()?->getOwnerId();
        $this->owner_type = $this->hook->getOwner()?->getOwnerType();
    }

    public function jsonSerialize(): array
    {
        $arr = parent::jsonSerialize();
        unset($arr['callplate_setup']);
        $arr['uuid'] = $this->uuid;
        $arr['owner_id'] = $this->owner_id;
        $arr['owner_type'] = $this->owner_type;
        $arr['callplates'] = $this->callplates;
        return $arr;
    }
}
