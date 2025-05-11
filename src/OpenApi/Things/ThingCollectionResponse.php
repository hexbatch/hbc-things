<?php

namespace Hexbatch\Things\OpenApi\Things;

use Hexbatch\Things\Models\Thing;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'ThingCollectionResponse',title: "Callbacks")]
class ThingCollectionResponse
{


    #[OA\Property( title: 'List of Things')]
    /**
     * @var ThingResponse[] $things
     */
    public array $things = [];


    /**
     * @param Thing[]|\Illuminate\Database\Eloquent\Collection $things
     */
    public function __construct($things, bool $b_include_hooks = false, bool $b_include_children = false)
    {
        $this->things = [];
        foreach ($things as $thung) {
            $this->things[] = new ThingResponse(thing: $thung,b_include_hooks: $b_include_hooks,b_include_children: $b_include_children);
        }
    }


}
