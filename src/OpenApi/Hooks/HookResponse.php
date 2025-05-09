<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\ThingHook;
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


    public function __construct(
        protected ThingHook $hook
    ) {

        parent::__construct(
            mode: $hook->hook_mode,
            name: $hook->hook_name,
            notes: $hook->hook_notes,
            action_type: $hook->getAction()?->getActionType(),
            action_id: $hook->getAction()?->getActionId(),
            constant_data: $hook->hook_constant_data?->getArrayCopy()??[],
            tags: $hook->hook_tags?->getArrayCopy()??[],
            hook_on: $hook->is_on,
            is_blocking: $hook->is_blocking,
            is_writing: $hook->is_writing_data_to_thing,
            is_sharing: $hook->is_sharing,
            is_manual: $hook->is_manual,
            is_after: $hook->is_after,
            callback_type: $hook->hook_callback_type,
            ttl_shared: $hook->ttl_shared,
            data_template: $hook->hook_data_template?->getArrayCopy()??[],
            header_template: $hook->hook_header_template?->getArrayCopy()??[],
            address: $hook->address,
        );

        $this->uuid = $hook->ref_uuid;

        $this->owner_id = $hook->getOwner()?->getOwnerId();
        $this->owner_type = $hook->getOwner()?->getOwnerType();
    }

    public function jsonSerialize(): array
    {
        $arr = parent::jsonSerialize();
        unset($arr['callplate_setup']);
        $arr['uuid'] = $this->uuid;
        $arr['owner_id'] = $this->owner_id;
        $arr['owner_type'] = $this->owner_type;
        return $arr;
    }
}
