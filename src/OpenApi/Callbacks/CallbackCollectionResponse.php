<?php

namespace Hexbatch\Things\OpenApi\Callbacks;

use Hexbatch\Things\Models\ThingCallback;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'CallbackCollectionResponse',title: "Callbacks")]
class CallbackCollectionResponse
{


    #[OA\Property( title: 'List of Callbacks')]
    /**
     * @var CallbackResponse[] $hooks
     */
    public array $hooks = [];


    /**
     * @param ThingCallback[]|\Illuminate\Database\Eloquent\Collection $callbacks
     */
    public function __construct($callbacks, bool $b_include_hook = false)
    {
        $this->hooks = [];
        foreach ($callbacks as $call) {
            $this->hooks[] = new CallbackResponse(callback: $call,b_include_hook: $b_include_hook);
        }
    }


}
