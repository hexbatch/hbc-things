<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\ThingHook;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'HookCollectionResponse',title: "Hooks")]
class HookCollectionResponse
{


    #[OA\Property( title: 'List of Hooks')]
    /**
     * @var HookResponse[] $hooks
     */
    public array $hooks = [];


    /**
     * @param ThingHook[] $hooks
     */
    public function __construct( $hooks)
    {
        $this->hooks = [];
        foreach ($hooks as $hook) {
            $this->hooks[] = new HookResponse(hook: $hook);
        }
    }


}
