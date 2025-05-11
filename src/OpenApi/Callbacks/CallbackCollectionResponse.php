<?php

namespace Hexbatch\Things\OpenApi\Callbacks;

use Hexbatch\Things\Models\ThingCallback;
use Illuminate\Contracts\Pagination\CursorPaginator;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'CallbackCollectionResponse',title: "Callbacks")]
class CallbackCollectionResponse implements  JsonSerializable
{


    #[OA\Property( title: 'List of Callbacks')]
    /**
     * @var CallbackResponse[] $callbacks
     */
    public array $callbacks = [];

    #[OA\Property( title: 'Next results',format: 'url')]
    public ?string $next_page =null;

    #[OA\Property( title: 'Previous results',format: 'url')]
    public ?string $previous_page =null;


    /**
     * @param ThingCallback[]|\Illuminate\Database\Eloquent\Collection|CursorPaginator $given_callbacks
     */
    public function __construct($given_callbacks, bool $b_include_hook = false)
    {
        $this->callbacks = [];
        foreach ($given_callbacks as $call) {
            $this->callbacks[] = new CallbackResponse(callback: $call,b_include_hook: $b_include_hook);
        }

        if ($given_callbacks instanceof CursorPaginator) {
            $this->next_page = $given_callbacks->nextPageUrl();
            $this->previous_page = $given_callbacks->previousPageUrl();
        }
    }

    public function jsonSerialize(): array
    {
        $arr = [];
        if ($this->next_page || $this->previous_page) {
            $arr['next_page'] = $this->next_page;
            $arr['previous_page'] = $this->previous_page;
        }

        $arr['callbacks'] = $this->callbacks;
        return $arr;
    }


}
