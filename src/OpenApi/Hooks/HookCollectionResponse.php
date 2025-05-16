<?php

namespace Hexbatch\Things\OpenApi\Hooks;

use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingHook;
use Illuminate\Contracts\Pagination\CursorPaginator;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'HookCollectionResponse',title: "Hooks")]
class HookCollectionResponse implements  JsonSerializable
{


    #[OA\Property( title: 'List of Hooks')]
    /**
     * @var HookResponse[] $hooks
     */
    public array $hooks = [];

    #[OA\Property( title: 'Next results',format: 'url')]
    public ?string $next_page =null;

    #[OA\Property( title: 'Previous results',format: 'url')]
    public ?string $previous_page =null;

    /**
     * @param ThingHook[]|\Illuminate\Database\Eloquent\Collection|CursorPaginator $given_hooks
     */
    public function __construct($given_hooks,
                                protected bool $b_include_callbacks = false,
                                protected ?Thing $callbacks_scoped_to_thing = null)
    {
        $this->hooks = [];
        foreach ($given_hooks as $hook) {
            $this->hooks[] = new HookResponse(hook: $hook,b_include_callbacks: $this->b_include_callbacks,callbacks_scoped_to_thing: $this->callbacks_scoped_to_thing);
        }

        if ($given_hooks instanceof CursorPaginator) {
            $this->next_page = $given_hooks->nextPageUrl();
            $this->previous_page = $given_hooks->previousPageUrl();
        }
    }

    public function jsonSerialize(): array
    {
        $arr = [];
        if ($this->next_page || $this->previous_page) {
            $arr['next_page'] = $this->next_page;
            $arr['previous_page'] = $this->previous_page;
        }

        $arr['hooks'] = $this->hooks;
        return $arr;
    }


}
