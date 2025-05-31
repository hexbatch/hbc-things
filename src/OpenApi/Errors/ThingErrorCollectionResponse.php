<?php

namespace Hexbatch\Things\OpenApi\Errors;


use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingError;
use Hexbatch\Things\OpenApi\Things\ThingResponse;

use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * A collection of hooks
 */
#[OA\Schema(schema: 'ThingErrorCollectionResponse',title: "Thing with errors")]
class ThingErrorCollectionResponse implements  JsonSerializable
{


    #[OA\Property( title: 'Thing')]
    public ThingResponse $thing;

    #[OA\Property( title: 'List of Errors')]
    /**
     * @var ThingErrorResponse[] $errors
     */
    public array $errors = [];





    public function __construct(Thing $thing, bool $b_include_hooks = false, bool $b_include_children = false)
    {
        $this->thing =  new ThingResponse(thing: $thing,b_include_hooks: $b_include_hooks,b_include_children: $b_include_children);

        $error_ids = $thing->getTreeErrorIds();
        $this->errors = [];
        if (count($error_ids)) {
            $errors = ThingError::buildError(error_ids: $error_ids,do_relations: true)->get();
            foreach ($errors as $error) {
                $this->errors[] = new ThingErrorResponse(error: $error);
            }
        }
    }

    public function jsonSerialize(): array
    {
        $arr = [];


        $arr['thing'] = $this->thing;
        $arr['errors'] = $this->errors;
        return $arr;
    }


}
