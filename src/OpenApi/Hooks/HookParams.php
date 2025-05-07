<?php

namespace Hexbatch\Things\OpenApi\Hooks;


use Hexbatch\Things\Enums\TypeOfHookBlocking;
use Hexbatch\Things\Enums\TypeOfHookMode;
use Hexbatch\Things\Enums\TypeOfHookPosition;
use Hexbatch\Things\Enums\TypeOfHookScope;
use Hexbatch\Things\Interfaces\IHookParams;
use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\OpenApi\Hooks\Callplates\CallplateParams;
use Illuminate\Http\Request;
use JsonSerializable;
use OpenApi\Attributes as OA;

/**
 * Create a new hook
 */
#[OA\Schema(schema: 'HookParams',title: "Hook creation data")]

class HookParams implements IHookParams, JsonSerializable
{


    protected ?IThingAction $action = null;

    public function __construct(
        #[OA\Property( title: 'Mode',description: 'Each hook must have a mode set',nullable: false)]
        protected ?TypeOfHookMode $mode = null,

        #[OA\Property( title: 'Position',description: 'Where the hook can be set at',nullable: false)]
        protected ?TypeOfHookPosition $position = null,

        #[OA\Property( title: 'Blocking',description: 'Must the hook be completed before the thing can complete?',nullable: false)]
        protected ?TypeOfHookBlocking $blocking = null,

        #[OA\Property( title: 'Scope',description: 'Scope of the hook action',nullable: false)]
        protected TypeOfHookScope $scope = TypeOfHookScope::CURRENT,


        #[OA\Property( title: 'Name',description: 'Hooks can have names',nullable: true)]
        protected ?string  $name = null,

        #[OA\Property( title: 'Notes',description: 'Any notes (text)',nullable: true)]
        protected ?string  $notes = null,

        //set by code
        protected ?IThingOwner $owner = null,

        #[OA\Property( title: 'Action type filter',description: 'Optional type of action used to filter. Type must exist when set',nullable: true)]
        protected ?string  $action_type = null,

        #[OA\Property( title: 'Action id filter',description: 'Optional action used to filter.',nullable: true)]
        protected ?string  $action_id = null,

        #[OA\Property( title: 'Callback constants to send',description: 'Optional data to send in each callback.',nullable: true)]
        protected array $constant_data = [],

        #[OA\Property( title: 'Tags filter',description: 'Optional tags used to filter.',nullable: true)]
        protected array $tags = [],


        #[OA\Property( title: 'Active',description: 'If false, the callback is not used until this is changed.',nullable: true)]
        protected bool $hook_on = true,

        #[OA\Property( title: 'Callback definitions',description: 'Hooks can have one or more callbacks.',nullable: true)]
        /** @var CallplateParams[] $callplate_setup */
        protected array $callplate_setup = [],

        protected ?array $from_array = null

    )
    {
    }


    public function jsonSerialize(): array
    {
        return [
            'mode' => $this->mode->value,
            'blocking' => $this->blocking->value,
            'position' => $this->position->value,
            'scope' => $this->scope->value,
            'name' => $this->name,
            'notes' => $this->notes,
            'owner_type' => $this->owner?->getOwnerType(),
            'owner_id' => $this->owner?->getOwnerId(),
            'action_type' => $this->action?->getActionType(),
            'action_id' => $this->action?->getActionId(),
            'hook_on' => $this->hook_on,
            'constant_data' => $this->constant_data,
            'tags' => $this->tags,

            'callplate_setup' => $this->callplate_setup,
        ];
    }

    public static function fromArray(array $source) : static {

        $node = new static();
        $node->fillFromArray(source: $source);
        return $node;
    }

    public function fillFromArray(array $source) {


        if ($mode = (string)$source['mode']??null) {
            $this->mode = TypeOfHookMode::tryFromInput($mode);
        }

        if ($blocking = (string)$source['blocking']??null) {
            $this->blocking = TypeOfHookBlocking::tryFromInput($blocking);
        }

        if ($position = (string)$source['position']??null) {
            $this->position = TypeOfHookPosition::tryFromInput($position);
        }

        if ($scope = (string)$source['scope']??null) {
            $this->scope = TypeOfHookScope::tryFromInput($scope);
        }

        if ($name = (string)$source['name']??null) {
            $this->name = $name;
        }

        if ($notes = (string)$source['notes']??null) {
            $this->notes = $notes;
        }

        if ($action_type = (string)$source['action_type']??null) {
            $this->action_type = $action_type;
        }

        if ($action_id = (int)$source['action_id']??null) {
            $this->action_id = $action_id;
        }

        $this->hook_on =  filter_var($source['hook_on']??false, FILTER_VALIDATE_BOOL, FILTER_REQUIRE_SCALAR);

        if ( ($const = $source['constant_data']??null ) && is_array($const)) {
            $this->constant_data = $const;
        }


        if ( ($ag = $source['tags']??null ) && is_array($ag)) {
            $this->tags = $ag;
        }

        if ( ($setup = $source['callplate_setup']??null ) && is_array($setup)) {
            foreach ($setup as $part) {
                if (is_array($part)) {
                    $this->callplate_setup[] = CallplateParams::fromArray(source: $part);
                }
            }
        }
    }

    public static function fromRequest(Request $request) : static {

        $node = new static();
        $node->fillFromArray(source: $request->all());
        return $node;
    }


    public function getHookOwner(): ?IThingOwner
    {
       return $this->owner;
    }

    public function getHookAction(): ?IThingAction
    {
        return $this->action;
    }

    public function getConstantData(): array
    {
        return $this->constant_data;
    }

    public function getHookTags(): array
    {
        return $this->tags;
    }


    public function isHookOn(): bool
    {
        return $this->hook_on;
    }

    public function getHookMode(): ?TypeOfHookMode
    {
        return $this->mode;
    }

    public function getHookBlocking(): ?TypeOfHookBlocking
    {
        return $this->blocking;
    }

    public function getHookScope(): TypeOfHookScope
    {
        return $this->scope;
    }

    public function getHookPosition(): ?TypeOfHookPosition
    {
        return $this->position;
    }

    public function getHookName(): ?string
    {
        return $this->name;
    }

    public function getHookNotes(): ?string
    {
        return $this->notes;
    }

    /** @return CallplateParams[] */
    public function getCallplates(): array
    {
        return $this->callplate_setup;
    }


    public function setHookOwner(?IThingOwner $owner): IHookParams
    {
        $this->owner = $owner;
        return $this;
    }
}
