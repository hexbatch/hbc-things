<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\Thing;
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
    public function __construct( $hooks,
                                 protected bool $b_include_callbacks = false,
                                 protected ?Thing $callbacks_scoped_to_thing = null,)
    {
        $this->hooks = [];
        foreach ($hooks as $hook) {
            $this->hooks[] = new HookResponse(hook: $hook,b_include_callbacks: $this->b_include_callbacks,callbacks_scoped_to_thing: $this->callbacks_scoped_to_thing);
        }
    }


}
