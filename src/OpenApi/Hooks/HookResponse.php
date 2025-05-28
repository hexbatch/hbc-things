<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\OpenApi\Callbacks\CallbackCollectionResponse;
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

    #[OA\Property( title: 'Callbacks', description: 'Callbacks generated', nullable: true)]
    protected ?CallbackCollectionResponse  $callbacks = null;


    public function __construct(
        protected ThingHook $hook,
        protected bool $b_include_callbacks = false,
        protected ?Thing $callbacks_scoped_to_thing = null,
    ) {

        parent::__construct(
            mode: $hook->hook_mode,
            name: $hook->hook_name,
            notes: $hook->hook_notes,
            action_type: $hook->action_type,
            action_id: $hook->action_type_id,
            tags: array_values($hook->hook_tags?->getArrayCopy()??[]),
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
            filter_owner_type: $this->hook->filter_owner_type,
            filter_owner_id: $this->hook->filter_owner_type_id,
        );

        $this->uuid = $hook->ref_uuid;

        $this->owner_id = $hook->getOwner()?->getOwnerUuid();
        $this->owner_type = $hook->getOwner()?->getOwnerType();

        if ($this->b_include_callbacks) {
            $laravel_callbacks = $this->hook->hook_callbacks();

            if ($this->callbacks_scoped_to_thing) {
                $laravel_callbacks->where('source_thing_id',$this->callbacks_scoped_to_thing->id);
            }

            $this->callbacks = new CallbackCollectionResponse(given_callbacks: $laravel_callbacks->get());
        }
    }

    public function jsonSerialize(): array
    {
        $arr = parent::jsonSerialize();
        $arr['uuid'] = $this->uuid;
        $arr['owner_id'] = $this?->owner_id;
        $arr['owner_type'] = $this?->owner_type;

        if ($this->b_include_callbacks) {
            $arr['callbacks'] = $this->callbacks;
        }
        return $arr;
    }
}
