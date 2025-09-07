<?php

namespace Hexbatch\Things\OpenApi\Things;


use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Interfaces\ICallResponse;
use Hexbatch\Things\Models\Thing;

use Hexbatch\Things\OpenApi\Hooks\HookCollectionResponse;
use JsonSerializable;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response as CodeOf;

#[OA\Schema(schema: 'ThingResponse',title: "Callback")]

/**
 * Show a Hook
 */
class ThingResponse  implements  JsonSerializable,ICallResponse
{

    use ThingMimimalResponseTrait;


    #[OA\Property( title:"Action data",nullable: true)]
    /** @var mixed[] $action_info */
    protected ?array $action_info;

    #[OA\Property( title:"Hooks")]
    protected ?HookCollectionResponse $hooks = null;

    #[OA\Property( title:"Children")]
    protected ?ThingCollectionResponse $children = null;

    #[OA\Property( title:"Action html")]
    protected ?string $action_html;


    public function __construct(
        Thing $thing,
        protected bool $b_include_hooks = true,
        protected bool $b_include_children = true,
    ) {

        $this->initThingFields(thing:$thing);

        $this->action_info = $this->action?->getDataSnapshot();


        if ($this->b_include_hooks) {
            /** @uses Thing::attached_hooks() */
            $this->hooks = new HookCollectionResponse(given_hooks: $this->thing->attached_hooks,b_include_callbacks: true,callbacks_scoped_to_thing: $this->thing);
        }

        if ($this->b_include_children) {
            $this->children = new ThingCollectionResponse(given_things: $this->thing->thing_children,
                b_include_hooks: $this->b_include_hooks,b_include_children: $this->b_include_children);
        }

        $this->action_html = $this->thing->getAction()->getRenderHtml();

    }

    public function jsonSerialize(): array
    {
        $arr = $this->getThingInfoArray()??[];

        $arr['action_info'] = $this->action_info;
        $arr['action_html'] = $this->action_html;
        if ($this->b_include_hooks) {
            $arr['hooks'] = $this->hooks;
        }
        if ($this->b_include_children) {
            $arr['children'] = $this->children;
        }

        return $arr;
    }

    public function getCode(): int
    {
        return match ($this->thing->thing_status)
        {
          TypeOfThingStatus::THING_SUCCESS, TypeOfThingStatus::THING_SHORT_CIRCUITED => CodeOf::HTTP_OK,
          TypeOfThingStatus::THING_FAIL => CodeOf::HTTP_BAD_REQUEST,
          TypeOfThingStatus::THING_INVALID => CodeOf::HTTP_NOT_ACCEPTABLE,
          TypeOfThingStatus::THING_ERROR => CodeOf::HTTP_INTERNAL_SERVER_ERROR,
          TypeOfThingStatus::THING_RUNNING,TypeOfThingStatus::THING_PENDING,
          TypeOfThingStatus::THING_BUILDING,TypeOfThingStatus::THING_WAITING => CodeOf::HTTP_ACCEPTED
        };
//
    }

    public function getData(): ?array
    {
        return $this->jsonSerialize();
    }

    public function getWaitTimeoutInSeconds(): ?int
    {
        return null;
    }
}
