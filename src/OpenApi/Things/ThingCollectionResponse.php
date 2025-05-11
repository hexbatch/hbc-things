<?php

namespace Hexbatch\Things\OpenApi\Things;

use Hexbatch\Things\Models\Thing;
use Illuminate\Contracts\Pagination\CursorPaginator;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'ThingCollectionResponse',title: "Callbacks")]
class ThingCollectionResponse implements  JsonSerializable
{


    #[OA\Property( title: 'List of Things')]
    /**
     * @var ThingResponse[] $things
     */
    public array $things = [];


    #[OA\Property( title: 'Next results',format: 'url')]
    public ?string $next_page =null;

    #[OA\Property( title: 'Previous results',format: 'url')]
    public ?string $previous_page =null;


    /**
     * @param Thing[]|\Illuminate\Database\Eloquent\Collection|CursorPaginator $given_things
     */
    public function __construct($given_things, bool $b_include_hooks = false, bool $b_include_children = false)
    {
        $this->things = [];
        foreach ($given_things as $thung) {
            $this->things[] = new ThingResponse(thing: $thung,b_include_hooks: $b_include_hooks,b_include_children: $b_include_children);
        }

        if ($given_things instanceof CursorPaginator) {
            $this->next_page = $given_things->nextPageUrl();
            $this->previous_page = $given_things->previousPageUrl();
        }
    }

    public function jsonSerialize(): array
    {
        $arr = [];
        if ($this->next_page || $this->previous_page) {
            $arr['next_page'] = $this->next_page;
            $arr['previous_page'] = $this->previous_page;
        }

        $arr['things'] = $this->things;
        return $arr;
    }


}
