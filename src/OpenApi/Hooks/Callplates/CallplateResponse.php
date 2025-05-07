<?php

namespace Hexbatch\Things\OpenApi\Hooks\Callplates;


use Hexbatch\Things\Models\ThingCallplate;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * Show a callplate
 */
#[OA\Schema(schema: 'CallplateResponse',title: "Callplate")]

class CallplateResponse extends CallplateParams implements  JsonSerializable
{

    #[OA\Property( title:"Hook",format: 'uuid')]
    protected string $hook_uuid;

    #[OA\Property( title:"Self",format: 'uuid')]
    protected string $uuid;
    public function __construct(ThingCallplate $callplate)
    {
        parent::__construct(
            callback_type: $callplate->callplate_callback_type,
            sharing: $callplate->callplate_sharing_type,
            ttl_shared: $callplate->ttl_shared,
            data_template: $callplate->callplate_data_template?->getArrayCopy()??[],
            header_template: $callplate->callplate_header_template?->getArrayCopy()??[],
            tags: $callplate->callplate_tags?->getArrayCopy()??[],
            address: $callplate->address,
        );


        $this->hook_uuid = $callplate->callplate_owning_hook->ref_uuid;
        $this->uuid = $callplate->ref_uuid;
    }

    public function jsonSerialize(): array
    {
        $arr = parent::jsonSerialize();
        $arr['uuid'] = $this->uuid;
        $arr['hook_uuid'] = $this->hook_uuid;
        return $arr;
    }
}
